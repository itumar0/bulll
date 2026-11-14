import "server-only";
import { Prisma, type Booking, type PoolTable } from "@prisma/client";
import { prisma } from "./prisma";
import { ACTIVE_STATUSES, CLOSE_MIN, TABLE_META, type TableId } from "./config";
import { bishkekToUtc, businessDate, dayWindow } from "./time";
import type { TableState, TableStatusAdmin, TableStatusPublic } from "./types";

export type { TableState, TableStatusAdmin, TableStatusPublic };

export class BookingError extends Error {
  constructor(
    message: string,
    public status: number = 400,
    public field?: string,
  ) {
    super(message);
  }
}

const SOON_MS = 60 * 60 * 1000;

/* ---------- per-table in-process lock (prevents check-then-insert races) ---------- */
const locks = new Map<string, Promise<unknown>>();
async function withTableLock<T>(tableId: string, fn: () => Promise<T>): Promise<T> {
  const prev = locks.get(tableId) ?? Promise.resolve();
  const run = prev.then(fn, fn);
  const tail = run.catch(() => undefined);
  locks.set(tableId, tail);
  try {
    return await run;
  } finally {
    if (locks.get(tableId) === tail) locks.delete(tableId);
  }
}

type Tx = Prisma.TransactionClient;

/**
 * Run `fn` in a SERIALIZABLE transaction: PostgreSQL then guarantees that two concurrent
 * "check overlap → insert" transactions cannot both succeed, even across server instances.
 * Serialization failures (P2034) are retried.
 */
async function serializable<T>(fn: (tx: Tx) => Promise<T>): Promise<T> {
  for (let attempt = 1; ; attempt++) {
    try {
      return await prisma.$transaction(fn, {
        isolationLevel: Prisma.TransactionIsolationLevel.Serializable,
        maxWait: 5_000,
        timeout: 10_000,
      });
    } catch (e) {
      const retryable = e instanceof Prisma.PrismaClientKnownRequestError && e.code === "P2034";
      if (!retryable || attempt >= 4) throw e;
      await new Promise((r) => setTimeout(r, 20 * attempt + Math.random() * 30));
    }
  }
}

/** Any active booking on `tableId` that overlaps [start, end). Half-open intervals: back-to-back is OK. */
export async function findConflict(tx: Tx, tableId: string, start: Date, end: Date, excludeId?: number) {
  return tx.booking.findFirst({
    where: {
      tableId,
      status: { in: ACTIVE_STATUSES },
      startAt: { lt: end },
      endAt: { gt: start },
      ...(excludeId ? { id: { not: excludeId } } : {}),
    },
    select: { id: true, startAt: true, endAt: true },
  });
}

interface CreateArgs {
  tableId: TableId;
  name: string;
  phone: string;
  guests: number;
  comment?: string | null;
  startAt: Date;
  endAt: Date;
  status: "PENDING" | "CONFIRMED";
  source: "WEBSITE" | "ADMIN";
}

export async function createBooking(args: CreateArgs): Promise<Booking & { table: PoolTable }> {
  const meta = TABLE_META[args.tableId];
  if (args.guests > meta.capacity) {
    throw new BookingError(`${meta.name}: максимум ${meta.capacity} гостей`, 400, "guests");
  }
  if (args.endAt <= args.startAt) throw new BookingError("Некорректный интервал", 400);

  return withTableLock(args.tableId, () =>
    serializable(async (tx) => {
      const conflict = await findConflict(tx, args.tableId, args.startAt, args.endAt);
      if (conflict) {
        throw new BookingError("Это время уже занято. Выберите другой слот или стол.", 409, "startMinutes");
      }
      return tx.booking.create({
        data: {
          tableId: args.tableId,
          name: args.name,
          phone: args.phone,
          guests: args.guests,
          comment: args.comment || null,
          startAt: args.startAt,
          endAt: args.endAt,
          status: args.status,
          source: args.source,
        },
        include: { table: true },
      });
    }),
  );
}

/** Website booking: date + startMinutes (Asia/Bishkek business day). */
export function websiteInterval(date: string, startMinutes: number, durationHours: number, now = new Date()) {
  const startAt = bishkekToUtc(date, startMinutes);
  const endAt = new Date(startAt.getTime() + durationHours * 3_600_000);
  if (endAt > bishkekToUtc(date, CLOSE_MIN)) throw new BookingError("Бронь должна закончиться до 02:00", 400, "durationHours");
  // Allow booking the current slot (started up to 25 min ago), not older ones.
  if (startAt.getTime() < now.getTime() - 25 * 60_000) {
    throw new BookingError("Нельзя забронировать прошедшее время", 400, "startMinutes");
  }
  const maxAhead = 60 * 24 * 3_600_000;
  if (startAt.getTime() - now.getTime() > maxAhead) {
    throw new BookingError("Бронирование доступно максимум на 60 дней вперёд", 400, "date");
  }
  return { startAt, endAt };
}

export async function extendBooking(id: number, minutes: number) {
  const booking = await prisma.booking.findUnique({ where: { id } });
  if (!booking) throw new BookingError("Бронь не найдена", 404);
  if (!ACTIVE_STATUSES.includes(booking.status as never)) throw new BookingError("Можно продлить только активную бронь", 400);
  const newEnd = new Date(booking.endAt.getTime() + minutes * 60_000);
  return withTableLock(booking.tableId, () =>
    serializable(async (tx) => {
      const conflict = await findConflict(tx, booking.tableId, booking.endAt, newEnd, booking.id);
      if (conflict) throw new BookingError("Продление пересекается со следующей бронью", 409);
      return tx.booking.update({ where: { id }, data: { endAt: newEnd } });
    }),
  );
}

export async function setBookingStatus(id: number, action: "confirm" | "cancel" | "complete") {
  const booking = await prisma.booking.findUnique({ where: { id } });
  if (!booking) throw new BookingError("Бронь не найдена", 404);
  const active = ACTIVE_STATUSES.includes(booking.status as never);
  const now = new Date();

  if (action === "confirm") {
    if (booking.status !== "PENDING") throw new BookingError("Подтвердить можно только ожидающую бронь");
    return prisma.booking.update({ where: { id }, data: { status: "CONFIRMED" } });
  }
  if (action === "cancel") {
    if (!active) throw new BookingError("Бронь уже неактивна");
    return prisma.booking.update({ where: { id }, data: { status: "CANCELLED" } });
  }
  // complete (early finish): cut the interval at "now" so the table frees immediately.
  if (!active) throw new BookingError("Бронь уже неактивна");
  const endAt = booking.startAt < now && booking.endAt > now ? now : booking.endAt;
  return prisma.booking.update({ where: { id }, data: { status: "COMPLETED", endAt } });
}

/* ------------------------------- live statuses ------------------------------- */

/**
 * Status is derived purely from the clock: a booking makes a table "busy" only while
 * startAt <= now < endAt, so it frees itself automatically once the time is over.
 * Consecutive bookings are merged to show the real "busy until".
 */
export async function getTableStatuses(now = new Date()): Promise<TableStatusAdmin[]> {
  const horizon = new Date(now.getTime() + 24 * 3_600_000);
  const [tables, bookings] = await Promise.all([
    prisma.poolTable.findMany({ orderBy: { sortOrder: "asc" } }),
    prisma.booking.findMany({
      where: { status: { in: ACTIVE_STATUSES }, endAt: { gt: now }, startAt: { lt: horizon } },
      orderBy: { startAt: "asc" },
    }),
  ]);

  return tables.map((t) => {
    const own = bookings.filter((b) => b.tableId === t.id);
    const current = own.find((b) => b.startAt <= now && b.endAt > now) ?? null;
    let busyUntil: Date | null = null;
    if (current) {
      busyUntil = current.endAt;
      for (const b of own) {
        if (b.startAt <= busyUntil && b.endAt > busyUntil) busyUntil = b.endAt;
      }
    }
    const next = own.find((b) => b.startAt > (busyUntil ?? now)) ?? null;
    const state: TableState = current
      ? "busy"
      : next && next.startAt.getTime() - now.getTime() <= SOON_MS
        ? "soon"
        : "free";

    return {
      id: t.id,
      name: t.name,
      type: t.type,
      capacity: t.capacity,
      state,
      busyUntil: busyUntil?.toISOString() ?? null,
      nextStart: next?.startAt.toISOString() ?? null,
      current: current && {
        id: current.id,
        name: current.name,
        phone: current.phone,
        guests: current.guests,
        startAt: current.startAt.toISOString(),
        endAt: current.endAt.toISOString(),
        status: current.status,
      },
      next: next && {
        id: next.id,
        name: next.name,
        phone: next.phone,
        startAt: next.startAt.toISOString(),
        endAt: next.endAt.toISOString(),
        status: next.status,
      },
    };
  });
}

export function toPublicStatus(s: TableStatusAdmin): TableStatusPublic {
  return {
    id: s.id,
    name: s.name,
    type: s.type,
    capacity: s.capacity,
    state: s.state,
    busyUntil: s.busyUntil,
    nextStart: s.nextStart,
  };
}

/** Busy intervals (no personal data) for the public booking form. */
export async function getBusyIntervals(date: string) {
  const { from, to } = dayWindow(date);
  const rows = await prisma.booking.findMany({
    where: { status: { in: ACTIVE_STATUSES }, startAt: { lt: to }, endAt: { gt: from } },
    select: { tableId: true, startAt: true, endAt: true },
    orderBy: { startAt: "asc" },
  });
  return rows.map((r) => ({ tableId: r.tableId, start: r.startAt.toISOString(), end: r.endAt.toISOString() }));
}

export { businessDate };
