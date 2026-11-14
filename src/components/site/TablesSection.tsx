"use client";

import { usePolling, useNow } from "@/hooks/usePolling";
import { CLUB, POLL_INTERVAL_MS } from "@/lib/config";
import { formatTime } from "@/lib/time";
import type { TableStatusPublic } from "@/lib/types";
import { CrownIcon, UsersIcon } from "../ui/icons";
import { StatusBadge } from "../ui/StatusBadge";

interface StatusResponse {
  ok: boolean;
  now: string;
  tables: TableStatusPublic[];
}

export const SELECT_TABLE_EVENT = "redshot:select-table";

function statusLabel(t: TableStatusPublic) {
  if (t.state === "busy" && t.busyUntil) return `Занят до ${formatTime(t.busyUntil)}`;
  if (t.state === "soon" && t.nextStart) return `Бронь с ${formatTime(t.nextStart)}`;
  return "Свободен";
}

export function TablesSection({ initial }: { initial: StatusResponse | null }) {
  const { data, error } = usePolling<StatusResponse>("/api/tables/status", POLL_INTERVAL_MS, initial ?? undefined);
  useNow(30_000);
  const tables = data?.tables ?? [];
  const freeCount = tables.filter((t) => t.state !== "busy").length;

  const pick = (id: string) => {
    window.dispatchEvent(new CustomEvent(SELECT_TABLE_EVENT, { detail: id }));
    document.getElementById("booking")?.scrollIntoView({ behavior: "smooth" });
  };

  return (
    <>
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p className="text-sm text-mute" aria-live="polite">
          {tables.length ? (
            <>
              Сейчас свободно: <span className="font-bold text-white">{freeCount}</span> из {tables.length}
            </>
          ) : error ? (
            "Статусы временно недоступны"
          ) : (
            "Загружаем статусы…"
          )}
        </p>
        <p className="flex items-center gap-2 text-xs text-neutral-400">
          <span className="h-1.5 w-1.5 animate-pulse-dot rounded-full bg-red-500" aria-hidden /> Обновляется автоматически
        </p>
      </div>

      <ul className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        {tables.map((t) => {
          const vip = t.type === "VIP";
          return (
            <li key={t.id}>
              <button
                type="button"
                onClick={() => pick(t.id)}
                className={`group card relative flex h-full w-full flex-col overflow-hidden p-4 text-left transition-all duration-300 hover:-translate-y-0.5 hover:border-red-500/40 hover:shadow-glow sm:p-6 ${
                  vip ? "bg-gradient-to-br from-ink-900 to-red-950/40" : ""
                }`}
              >
                {vip && <div aria-hidden className="absolute -top-10 -right-10 h-28 w-28 rounded-full bg-red-600/20 blur-2xl" />}
                <div className="mb-6 flex items-start justify-between gap-2">
                  <span className="text-xs font-bold tracking-[0.18em] text-neutral-400 uppercase">
                    {vip ? "VIP-комната" : "Пул / Русский"}
                  </span>
                  {vip && <CrownIcon className="h-4 w-4 text-red-500" />}
                </div>
                <span className="text-2xl font-extrabold tracking-tight sm:text-3xl">{t.name}</span>
                <span className="mt-1 flex items-center gap-1.5 text-xs text-mute">
                  <UsersIcon className="h-3.5 w-3.5" /> до {t.capacity} гостей
                </span>
                <span className="mt-0.5 text-xs text-mute">
                  {(vip ? CLUB.priceVip : CLUB.priceRegular).toLocaleString("ru-RU")} сом/час
                </span>
                <span className="mt-5 whitespace-nowrap">
                  <StatusBadge state={t.state} label={statusLabel(t)} />
                </span>
                <span className="mt-4 text-xs font-semibold text-red-400 transition-colors group-hover:text-red-300">
                  Забронировать<span className="sr-only"> {t.name}</span> <span aria-hidden>→</span>
                </span>
              </button>
            </li>
          );
        })}
        {!tables.length &&
          Array.from({ length: 8 }).map((_, i) => (
            <li key={i} className="card h-52 animate-pulse bg-ink-900/60" aria-hidden />
          ))}
      </ul>
    </>
  );
}
