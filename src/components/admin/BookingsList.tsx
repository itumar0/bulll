"use client";

import { STATUS_LABEL, TABLE_IDS, TABLE_META, BOOKING_STATUSES, type BookingStatus } from "@/lib/config";
import { prettyPhone } from "@/lib/phone";
import { formatDate, formatTime } from "@/lib/time";
import type { AdminBooking } from "@/lib/types";
import { BookingActions } from "./BookingActions";

export interface Filters {
  date: string;
  tableId: string;
  status: string;
  q: string;
}

const statusStyle: Record<BookingStatus, string> = {
  PENDING: "bg-amber-400/15 text-amber-200",
  CONFIRMED: "bg-emerald-500/15 text-emerald-300",
  COMPLETED: "bg-white/10 text-mute",
  CANCELLED: "bg-red-500/10 text-red-300 line-through",
};

export function BookingsList({
  bookings,
  filters,
  setFilters,
  loading,
  onChanged,
}: {
  bookings: AdminBooking[];
  filters: Filters;
  setFilters: (f: Filters) => void;
  loading: boolean;
  onChanged: (err?: string) => void;
}) {
  const set = (k: keyof Filters) => (e: { target: { value: string } }) => setFilters({ ...filters, [k]: e.target.value });

  return (
    <div>
      <div className="mb-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-[auto_auto_auto_1fr_auto]">
        <label className="sr-only" htmlFor="f-date">Дата</label>
        <input id="f-date" type="date" className="field py-2.5" value={filters.date} onChange={set("date")} />
        <label className="sr-only" htmlFor="f-table">Стол</label>
        <select id="f-table" className="field py-2.5" value={filters.tableId} onChange={set("tableId")}>
          <option value="">Все столы</option>
          {TABLE_IDS.map((id) => (
            <option key={id} value={id}>{TABLE_META[id].name}</option>
          ))}
        </select>
        <label className="sr-only" htmlFor="f-status">Статус</label>
        <select id="f-status" className="field py-2.5" value={filters.status} onChange={set("status")}>
          <option value="">Все статусы</option>
          {BOOKING_STATUSES.map((s) => (
            <option key={s} value={s}>{STATUS_LABEL[s]}</option>
          ))}
        </select>
        <label className="sr-only" htmlFor="f-q">Поиск</label>
        <input id="f-q" type="search" className="field py-2.5" placeholder="Поиск по имени или телефону" value={filters.q} onChange={set("q")} maxLength={60} />
        <button type="button" className="h-11 rounded-xl px-4 text-sm text-mute hover:bg-white/5 hover:text-white" onClick={() => setFilters({ date: "", tableId: "", status: "", q: "" })}>
          Сбросить
        </button>
      </div>

      <p className="mb-3 text-sm text-mute" aria-live="polite">
        {loading ? "Загрузка…" : `Найдено: ${bookings.length}`}
      </p>

      <div className="overflow-hidden rounded-2xl border border-white/[0.07]">
        <div className="hidden grid-cols-[60px_90px_1.2fr_130px_140px_60px_110px_2fr] gap-3 bg-ink-900 px-4 py-3 text-xs font-bold tracking-wider text-neutral-400 uppercase lg:grid">
          <span>№</span><span>Стол</span><span>Клиент</span><span>Дата</span><span>Время</span><span>Гост.</span><span>Статус</span><span>Действия</span>
        </div>
        <ul className="divide-y divide-white/[0.06]">
          {bookings.map((b) => (
            <li key={b.id} className="grid gap-2 px-4 py-4 text-sm lg:grid-cols-[60px_90px_1.2fr_130px_140px_60px_110px_2fr] lg:items-center lg:gap-3">
              <span className="font-mono text-neutral-400">#{b.id}</span>
              <span className="font-bold">{b.tableName}</span>
              <span>
                <span className="block font-semibold">{b.name}</span>
                <span className="block tabular-nums text-mute">{prettyPhone(b.phone)}</span>
                {b.comment && <span className="mt-1 block text-xs text-neutral-400 italic">«{b.comment}»</span>}
              </span>
              <span className="tabular-nums text-soft">{formatDate(b.startAt)}</span>
              <span className="tabular-nums font-semibold">{formatTime(b.startAt)} – {formatTime(b.endAt)}</span>
              <span className="tabular-nums">{b.guests}</span>
              <span>
                <span className={`inline-block rounded-full px-2.5 py-1 text-xs font-semibold ${statusStyle[b.status]}`}>{STATUS_LABEL[b.status]}</span>
                {b.source === "ADMIN" && <span className="ml-1 text-[10px] text-neutral-400">вручную</span>}
              </span>
              <BookingActions booking={b} onDone={onChanged} />
            </li>
          ))}
          {!bookings.length && !loading && <li className="px-4 py-12 text-center text-mute">Броней не найдено</li>}
        </ul>
      </div>
    </div>
  );
}
