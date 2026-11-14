"use client";

export type BookingAction = { action: "confirm" | "cancel" | "complete" } | { action: "extend"; minutes: number };

export async function adminFetch(url: string, init: RequestInit = {}): Promise<{ ok: boolean; error?: string; [k: string]: unknown }> {
  const res = await fetch(url, {
    ...init,
    credentials: "same-origin",
    headers: { "Content-Type": "application/json", ...(init.headers || {}) },
  });
  if (res.status === 401) {
    window.location.href = "/admin/login";
    return { ok: false, error: "Сессия истекла" };
  }
  const json = await res.json().catch(() => ({}));
  if (!res.ok) return { ok: false, error: json.error ?? `Ошибка ${res.status}` };
  return { ok: true, ...json };
}

export function bookingAction(id: number, body: BookingAction) {
  return adminFetch(`/api/admin/bookings/${id}`, { method: "PATCH", body: JSON.stringify(body) });
}
