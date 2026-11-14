"use client";

import { prettyPhone } from "@/lib/phone";
import { formatDuration, formatTime } from "@/lib/time";
import type { TableStatusAdmin } from "@/lib/types";
import { CrownIcon } from "../ui/icons";
import { BookingActions } from "./BookingActions";

const theme = {
  free: { card: "border-emerald-500/30 bg-emerald-500/[0.06]", dot: "bg-emerald-400", label: "Свободен", text: "text-emerald-300" },
  busy: { card: "border-red-500/40 bg-red-600/[0.10]", dot: "bg-red-500", label: "Занят", text: "text-red-300" },
  soon: { card: "border-amber-400/40 bg-amber-400/[0.07]", dot: "bg-amber-400", label: "Скоро бронь", text: "text-amber-200" },
};

export function TablesGrid({ tables, now, onChanged }: { tables: TableStatusAdmin[]; now: number; onChanged: (err?: string) => void }) {
  return (
    <ul className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      {tables.map((t) => {
        const th = theme[t.state];
        const b = t.current;
        return (
          <li key={t.id} className={`flex flex-col rounded-2xl border p-4 ${th.card}`}>
            <div className="flex items-center justify-between">
              <h3 className="flex items-center gap-2 text-xl font-extrabold">
                {t.type === "VIP" && <CrownIcon className="h-4 w-4 text-red-500" />} {t.name}
              </h3>
              <span className={`flex items-center gap-1.5 text-xs font-bold ${th.text}`}>
                <span className={`h-2 w-2 rounded-full ${th.dot} ${t.state !== "free" ? "animate-pulse-dot" : ""}`} aria-hidden />
                {th.label}
              </span>
            </div>

            {b ? (
              <div className="mt-4 space-y-1 text-sm">
                <p className="font-bold text-white">
                  {b.name} <span className="font-normal text-mute">· {b.guests} чел.</span>
                </p>
                <p className="tabular-nums text-soft">{prettyPhone(b.phone)}</p>
                <p className="tabular-nums text-mute">
                  {formatTime(b.startAt)} – {formatTime(b.endAt)} · №{b.id}
                </p>
                <p className="pt-1 text-lg font-extrabold tabular-nums text-red-300">
                  осталось {formatDuration(new Date(t.busyUntil ?? b.endAt).getTime() - now)}
                </p>
              </div>
            ) : (
              <p className="mt-4 text-sm text-mute">Стол свободен</p>
            )}

            {t.next && (
              <p className={`mt-3 rounded-lg bg-black/25 px-3 py-2 text-xs ${t.state === "soon" ? "text-amber-200" : "text-mute"}`}>
                Следующая: <b className="tabular-nums">{formatTime(t.next.startAt)}</b> — {t.next.name}
                {t.state === "soon" && <> (через {formatDuration(new Date(t.next.startAt).getTime() - now)})</>}
              </p>
            )}

            {b && (
              <div className="mt-4 border-t border-white/10 pt-3">
                <BookingActions compact booking={{ ...b, tableName: t.name }} onDone={onChanged} />
              </div>
            )}
          </li>
        );
      })}
    </ul>
  );
}
