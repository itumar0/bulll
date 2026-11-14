"use client";

import { useEffect, useMemo, useState } from "react";
import { usePolling, useNow } from "@/hooks/usePolling";
import { POLL_INTERVAL_MS } from "@/lib/config";
import { formatDateHuman, formatTime } from "@/lib/time";
import type { AdminBooking, TableStatusAdmin } from "@/lib/types";
import { Logo } from "../site/Header";
import { Button } from "../ui/Button";
import { BookingsList, type Filters } from "./BookingsList";
import { NewBookingModal } from "./NewBookingModal";
import { TablesGrid } from "./TablesGrid";
import { Timeline } from "./Timeline";

type Tab = "tables" | "bookings" | "timeline";

interface Overview {
  ok: boolean;
  now: string;
  tables: TableStatusAdmin[];
}
interface BookingsResponse {
  ok: boolean;
  bookings: AdminBooking[];
}

export function AdminDashboard({ today }: { today: string }) {
  const now = useNow(15_000);
  const [tab, setTab] = useState<Tab>("tables");
  const [toast, setToast] = useState<{ text: string; error?: boolean } | null>(null);
  const [modal, setModal] = useState(false);
  const [filters, setFilters] = useState<Filters>({ date: today, tableId: "", status: "", q: "" });
  const [q, setQ] = useState("");
  const [timelineDate, setTimelineDate] = useState(today);

  // Debounce search input.
  useEffect(() => {
    const t = window.setTimeout(() => setQ(filters.q), 300);
    return () => window.clearTimeout(t);
  }, [filters.q]);

  const listUrl = useMemo(() => {
    const p = new URLSearchParams();
    if (filters.date) p.set("date", filters.date);
    if (filters.tableId) p.set("tableId", filters.tableId);
    if (filters.status) p.set("status", filters.status);
    if (q.trim()) p.set("q", q.trim());
    return `/api/admin/bookings?${p}`;
  }, [filters.date, filters.tableId, filters.status, q]);

  const overview = usePolling<Overview>("/api/admin/overview", POLL_INTERVAL_MS);
  const list = usePolling<BookingsResponse>(tab === "bookings" ? listUrl : null, POLL_INTERVAL_MS);
  const timeline = usePolling<BookingsResponse>(tab === "timeline" ? `/api/admin/bookings?date=${timelineDate}` : null, POLL_INTERVAL_MS);

  useEffect(() => {
    if (!toast) return;
    const t = window.setTimeout(() => setToast(null), 3500);
    return () => window.clearTimeout(t);
  }, [toast]);

  const refresh = (err?: string) => {
    setToast(err ? { text: err, error: true } : { text: "Готово" });
    overview.reload();
    if (tab === "bookings") list.reload();
    if (tab === "timeline") timeline.reload();
  };

  async function logout() {
    await fetch("/api/admin/logout", { method: "POST" });
    window.location.href = "/admin/login";
  }

  const tables = overview.data?.tables ?? [];
  const counts = {
    free: tables.filter((t) => t.state === "free").length,
    busy: tables.filter((t) => t.state === "busy").length,
    soon: tables.filter((t) => t.state === "soon").length,
  };

  const tabs: { id: Tab; label: string }[] = [
    { id: "tables", label: "Столы" },
    { id: "bookings", label: "Брони" },
    { id: "timeline", label: "Таймлайн" },
  ];

  return (
    <>
      <header className="sticky top-0 z-40 border-b border-white/[0.06] bg-ink-950/85 backdrop-blur-xl">
        <div className="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6">
          <div className="flex items-center gap-3">
            <Logo />
            <span className="hidden rounded-md bg-white/5 px-2 py-0.5 text-xs text-mute sm:inline">admin</span>
          </div>
          <div className="flex items-center gap-2">
            <span className="hidden text-sm tabular-nums text-mute sm:inline">
              {formatDateHuman(today)} · {formatTime(new Date(now))}
            </span>
            <Button size="sm" onClick={() => setModal(true)}>+ Бронь</Button>
            <Button size="sm" variant="ghost" onClick={logout}>Выйти</Button>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8">
        <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
          <div role="tablist" aria-label="Разделы" className="inline-flex rounded-xl border border-white/10 bg-ink-900 p-1">
            {tabs.map((t) => (
              <button
                key={t.id}
                role="tab"
                aria-selected={tab === t.id}
                aria-controls={`panel-${t.id}`}
                id={`tab-${t.id}`}
                onClick={() => setTab(t.id)}
                className={`rounded-lg px-4 py-2 text-sm font-semibold transition ${tab === t.id ? "bg-accent-600 text-white shadow-glow-sm" : "text-mute hover:text-white"}`}
              >
                {t.label}
              </button>
            ))}
          </div>
          <div className="flex gap-4 text-sm" aria-live="polite">
            <span className="text-emerald-300">● свободно {counts.free}</span>
            <span className="text-red-300">● занято {counts.busy}</span>
            <span className="text-amber-200">● скоро {counts.soon}</span>
          </div>
        </div>

        {overview.error && <p className="mb-4 text-sm text-amber-200">{overview.error}. Повторим через 30 сек.</p>}

        <section role="tabpanel" id={`panel-${tab}`} aria-labelledby={`tab-${tab}`}>
          {tab === "tables" &&
            (tables.length ? (
              <TablesGrid tables={tables} now={now} onChanged={refresh} />
            ) : (
              <p className="text-mute">{overview.loading ? "Загрузка…" : "Нет данных"}</p>
            ))}
          {tab === "bookings" && (
            <BookingsList bookings={list.data?.bookings ?? []} filters={filters} setFilters={setFilters} loading={list.loading} onChanged={refresh} />
          )}
          {tab === "timeline" && (
            <Timeline date={timelineDate} setDate={setTimelineDate} bookings={timeline.data?.bookings ?? []} now={now} onChanged={refresh} />
          )}
        </section>
      </main>

      <NewBookingModal
        open={modal}
        today={today}
        onClose={() => setModal(false)}
        onCreated={(id) => {
          setModal(false);
          refresh();
          setToast({ text: `Бронь №${id} создана` });
        }}
      />

      {toast && (
        <div
          role="status"
          className={`fixed bottom-5 left-1/2 z-50 -translate-x-1/2 rounded-xl border px-5 py-3 text-sm font-semibold shadow-2xl ${
            toast.error ? "border-red-500/40 bg-red-950 text-red-100" : "border-white/10 bg-ink-800 text-white"
          }`}
        >
          {toast.text}
        </div>
      )}
    </>
  );
}
