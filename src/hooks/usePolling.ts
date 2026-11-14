"use client";

import { useCallback, useEffect, useRef, useState } from "react";

/**
 * Polls `url` every `intervalMs`, pauses while the tab is hidden and refetches on focus.
 * Returns `reload` to refresh manually after mutations.
 */
export function usePolling<T>(url: string | null, intervalMs: number, initial?: T) {
  const [data, setData] = useState<T | undefined>(initial);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(!initial);
  const ctrl = useRef<AbortController | null>(null);

  const load = useCallback(async () => {
    if (!url) return;
    ctrl.current?.abort();
    const ac = new AbortController();
    ctrl.current = ac;
    try {
      const res = await fetch(url, { cache: "no-store", signal: ac.signal, credentials: "same-origin" });
      if (res.status === 401 && url.startsWith("/api/admin")) {
        window.location.href = "/admin/login";
        return;
      }
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      setData((await res.json()) as T);
      setError(null);
    } catch (e) {
      if ((e as Error).name !== "AbortError") setError("Не удалось обновить данные");
    } finally {
      if (!ac.signal.aborted) setLoading(false);
    }
  }, [url]);

  useEffect(() => {
    if (!url) return;
    setLoading(true);
    load();
    let timer = window.setInterval(load, intervalMs);
    const onVis = () => {
      window.clearInterval(timer);
      if (document.visibilityState === "visible") {
        load();
        timer = window.setInterval(load, intervalMs);
      }
    };
    document.addEventListener("visibilitychange", onVis);
    return () => {
      window.clearInterval(timer);
      document.removeEventListener("visibilitychange", onVis);
      ctrl.current?.abort();
    };
  }, [url, intervalMs, load]);

  return { data, error, loading, reload: load };
}

/** Re-render every `ms` so countdowns stay fresh between polls. */
export function useNow(ms = 15_000) {
  const [now, setNow] = useState(() => Date.now());
  useEffect(() => {
    const t = window.setInterval(() => setNow(Date.now()), ms);
    return () => window.clearInterval(t);
  }, [ms]);
  return now;
}
