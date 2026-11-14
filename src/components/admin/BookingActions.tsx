"use client";

import { useState } from "react";
import { prettyPhone } from "@/lib/phone";
import { clientMessage, waLink } from "@/lib/whatsapp";
import { WhatsAppIcon } from "../ui/icons";
import { bookingAction, type BookingAction } from "./api";

interface Props {
  booking: { id: number; name: string; phone: string; tableName: string; startAt: string; endAt: string; status: string };
  onDone: (message?: string) => void;
  compact?: boolean;
}

const btn =
  "inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border px-3 text-xs font-semibold transition disabled:opacity-40 disabled:pointer-events-none";

export function BookingActions({ booking, onDone, compact }: Props) {
  const [busy, setBusy] = useState(false);
  const active = booking.status === "PENDING" || booking.status === "CONFIRMED";
  const live = active && new Date(booking.startAt) <= new Date() && new Date(booking.endAt) > new Date();

  async function run(body: BookingAction, confirmText?: string) {
    if (confirmText && !window.confirm(confirmText)) return;
    setBusy(true);
    const res = await bookingAction(booking.id, body);
    setBusy(false);
    onDone(res.ok ? undefined : res.error);
  }

  const wa = waLink(booking.phone.replace("+", ""), clientMessage({ ...booking }));

  return (
    <div className="flex flex-wrap gap-1.5">
      {booking.status === "PENDING" && (
        <button className={`${btn} border-emerald-500/40 text-emerald-300 hover:bg-emerald-500/10`} disabled={busy} onClick={() => run({ action: "confirm" })}>
          Подтвердить
        </button>
      )}
      {active && (
        <>
          <button className={`${btn} border-white/15 text-soft hover:bg-white/5`} disabled={busy} onClick={() => run({ action: "extend", minutes: 30 })} title="Продлить на 30 минут">
            +30 мин
          </button>
          {!compact && (
            <button className={`${btn} border-white/15 text-soft hover:bg-white/5`} disabled={busy} onClick={() => run({ action: "extend", minutes: 60 })} title="Продлить на 1 час">
              +1 ч
            </button>
          )}
          {live && (
            <button className={`${btn} border-amber-400/40 text-amber-200 hover:bg-amber-400/10`} disabled={busy} onClick={() => run({ action: "complete" }, `Завершить бронь №${booking.id} досрочно? Стол освободится сейчас.`)}>
              Завершить
            </button>
          )}
          <button className={`${btn} border-red-500/40 text-red-300 hover:bg-red-500/10`} disabled={busy} onClick={() => run({ action: "cancel" }, `Отменить бронь №${booking.id} (${booking.name})?`)}>
            Отменить
          </button>
        </>
      )}
      <a href={wa} target="_blank" rel="noopener noreferrer" className={`${btn} border-[#25D366]/40 text-[#25D366] hover:bg-[#25D366]/10`} aria-label={`Написать клиенту ${booking.name} (${prettyPhone(booking.phone)}) в WhatsApp`}>
        <WhatsAppIcon className="h-3.5 w-3.5" /> WhatsApp
      </a>
    </div>
  );
}
