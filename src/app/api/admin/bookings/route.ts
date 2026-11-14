import { NextRequest } from "next/server";
import { BookingError, createBooking } from "@/lib/booking-service";
import { CLOSE_MIN } from "@/lib/config";
import { handleError, json, readJson } from "@/lib/http";
import { prisma } from "@/lib/prisma";
import { bishkekToUtc, businessDate } from "@/lib/time";
import { adminBookingCreateSchema, adminBookingsQuerySchema } from "@/lib/validation";

export const dynamic = "force-dynamic";

/** List bookings. Filters: date (business day), tableId, status, q (name/phone). */
export async function GET(req: NextRequest) {
  try {
    const sp = req.nextUrl.searchParams;
    const query = adminBookingsQuerySchema.parse({
      date: sp.get("date") || undefined,
      tableId: sp.get("tableId") || undefined,
      status: sp.get("status") || undefined,
      q: sp.get("q") || undefined,
    });

    const rows = await prisma.booking.findMany({
      where: {
        ...(query.tableId ? { tableId: query.tableId } : {}),
        ...(query.status ? { status: query.status } : {}),
        ...(query.date
          ? { startAt: { gte: bishkekToUtc(query.date, 6 * 60), lt: bishkekToUtc(query.date, 30 * 60) } }
          : {}),
      },
      include: { table: { select: { name: true } } },
      orderBy: { startAt: query.date ? "asc" : "desc" },
      take: 500,
    });

    // Case-insensitive search for Cyrillic is done in JS (SQLite LIKE is ASCII-only).
    const q = query.q?.toLowerCase();
    const qDigits = query.q?.replace(/\D/g, "");
    const filtered = q
      ? rows.filter(
          (b) => b.name.toLowerCase().includes(q) || (qDigits && qDigits.length >= 3 && b.phone.includes(qDigits)),
        )
      : rows;

    return json({
      ok: true,
      bookings: filtered.map((b) => ({
        id: b.id,
        tableId: b.tableId,
        tableName: b.table.name,
        name: b.name,
        phone: b.phone,
        guests: b.guests,
        comment: b.comment,
        startAt: b.startAt.toISOString(),
        endAt: b.endAt.toISOString(),
        status: b.status,
        source: b.source,
        createdAt: b.createdAt.toISOString(),
      })),
    });
  } catch (e) {
    return handleError(e, "admin:bookings:list");
  }
}

/** Manual booking (walk-in guests). Confirmed immediately. */
export async function POST(req: Request) {
  try {
    const input = adminBookingCreateSchema.parse(await readJson(req));
    const now = new Date();
    let startAt: Date;
    if (input.startNow || input.startMinutes === undefined) {
      startAt = new Date(Math.floor(now.getTime() / 60_000) * 60_000);
    } else {
      const date = input.date ?? businessDate(now);
      startAt = bishkekToUtc(date, input.startMinutes);
      if (startAt > bishkekToUtc(date, CLOSE_MIN)) throw new BookingError("Время за пределами рабочего дня");
    }
    const endAt = new Date(startAt.getTime() + input.durationHours * 3_600_000);

    const booking = await createBooking({
      tableId: input.tableId,
      name: input.name,
      phone: input.phone,
      guests: input.guests,
      comment: input.comment,
      startAt,
      endAt,
      status: "CONFIRMED",
      source: "ADMIN",
    });
    return json({ ok: true, id: booking.id }, { status: 201 });
  } catch (e) {
    return handleError(e, "admin:bookings:create");
  }
}
