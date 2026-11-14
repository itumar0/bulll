"use client";

import { useState } from "react";
import { STATUS_LABEL, TABLE_IDS, TABLE_META, type BookingStatus } from "@/lib/config";
import { prettyPhone } from "@/lib/phone";
import { businessMinutes, formatTime, minutesLabel } from "@/lib/time";
import type { AdminBooking } from "@/lib/types";
import { BookingActions } from "./BookingActions";

// 24/7 club: the timeline covers the whole calendar day.
const OPEN_MIN = 0;
const CLOSE_MIN = 24 * 60;
const SPAN = CLOSE_MIN - OPEN_MIN;
const HOURS = Array.from({ length: SPAN / 60 + 1 }, (_, i) => OPEN_MIN + i * 60);

const barStyle: Record<BookingStatus, string> = {
  PENDING: "bg-amber-400/25 border-amber-400/60 text-amber-100",
  CONFIRMED: "bg-accent-600/40 border-accent-500/70 text-white",
  COMPLETED: "bg-white/10 border-white/20 text-mute",
  CANCELLED: "hidden",
};

export function Timeline({
  date,
  setDate,
  bookings,
  now,
  onChanged,
}: {
  date: string;
  setDate: (d: string) => void;
  bookings: AdminBooking[];
  now: number;
  onChanged: (err?: string) => void;
}) {
  const [selected, setSelected] = useState<number | null>(null);
  const sel = bookings.find((b) => b.id === selected) ?? null;
  const nowMin = businessMinutes(new Date(now), date);
  const pct = (m: number) => `${((Math.min(Math.max(m, OPEN_MIN), CLOSE_MIN) - OPEN_MIN) / SPAN) * 100}%`;

  return (
    <div>
      <div className="mb-4 flex flex-wrap items-center gap-3">
        <label htmlFor="tl-date" className="text-sm text-mute">Дата</label>
        <input id="tl-date" type="date" className="field w-auto py-2.5" value={date} onChange={(e) => e.target.value && setDate(e.target.value)} />
        <span className="flex items-center gap-3 text-xs text-mute">
          <span className="flex items-center gap-1"><i className="h-3 w-3 rounded bg-accent-600/60" /> подтверждена</span>
          <span className="flex items-center gap-1"><i className="h-3 w-3 rounded bg-amber-400/50" /> ожидает</span>
          <span className="flex items-center gap-1"><i className="h-3 w-3 rounded bg-white/20" /> завершена</span>
        </span>
      </div>

      <div className="overflow-x-auto rounded-2xl border border-white/[0.07] bg-ink-900">
        <div className="relative min-w-[1440px] p-4">
          {/* hour scale */}
          <div className="relative ml-20 h-6 text-[11px] text-neutral-400">
            {HOURS.map((h) => (
              <span key={h} className="absolute -translate-x-1/2 tabular-nums" style={{ left: pct(h) }}>
                {minutesLabel(h)}
              </span>
            ))}
          </div>
          <div className="relative">
            {TABLE_IDS.map((id) => (
              <div key={id} className="flex h-12 items-center border-t border-white/[0.05]">
                <span className="sticky left-0 z-10 w-20 shrink-0 self-stretch bg-ink-900 pl-1 text-sm leading-[3rem] font-bold">{TABLE_META[id].name}</span>
                <div className="relative h-9 flex-1">
                  {HOURS.map((h) => (
                    <span key={h} className="absolute inset-y-0 w-px bg-white/[0.05]" style={{ left: pct(h) }} aria-hidden />
                  ))}
                  {bookings
                    .filter((b) => b.tableId === id && b.status !== "CANCELLED")
                    .map((b) => {
                      const s = businessMinutes(new Date(b.startAt), date);
                      const e = businessMinutes(new Date(b.endAt), date);
                      if (e <= OPEN_MIN || s >= CLOSE_MIN) return null;
                      return (
                        <button
                          key={b.id}
                          type="button"
                          onClick={() => setSelected(b.id)}
                          className={`absolute inset-y-0 overflow-hidden rounded-md border px-2 text-left text-[11px] leading-tight font-semibold whitespace-nowrap transition hover:brightness-125 ${barStyle[b.status]} ${selected === b.id ? "ring-2 ring-white" : ""}`}
                          style={{ left: pct(s), width: `calc(${pct(e)} - ${pct(s)})` }}
                          title={`${b.name} · ${formatTime(b.startAt)}–${formatTime(b.endAt)} · ${STATUS_LABEL[b.status]}`}
                        >
                          <span className="block truncate">{b.name}</span>
                          <span className="block truncate opacity-75 tabular-nums">{formatTime(b.startAt)}–{formatTime(b.endAt)}</span>
                        </button>
                      );
                    })}
                </div>
              </div>
            ))}
            {nowMin >= OPEN_MIN && nowMin <= CLOSE_MIN && (
              <div className="pointer-events-none absolute inset-y-0 right-0 left-20" aria-hidden>
                <div className="absolute inset-y-0 w-0.5 bg-accent-500 shadow-glow-sm" style={{ left: pct(nowMin) }} />
              </div>
            )}
          </div>
        </div>
      </div>

      {sel && (
        <div className="card mt-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="text-sm">
            <p className="font-bold">
              №{sel.id} · {sel.tableName} · {sel.name} · {prettyPhone(sel.phone)}
            </p>
            <p className="text-mute tabular-nums">
              {formatTime(sel.startAt)} – {formatTime(sel.endAt)} · {sel.guests} чел. · {STATUS_LABEL[sel.status]}
            </p>
          </div>
          <BookingActions booking={sel} onDone={onChanged} />
        </div>
      )}
    </div>
  );
}
