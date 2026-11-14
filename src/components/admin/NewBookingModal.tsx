"use client";

import { useEffect, useRef, useState, type FormEvent } from "react";
import { CLOSE_MIN, OPEN_MIN, SLOT_MIN, TABLE_IDS, TABLE_META, type TableId } from "@/lib/config";
import { caretToEnd, formatPhoneMask } from "@/lib/phone";
import { minutesLabel } from "@/lib/time";
import { Button } from "../ui/Button";
import { CloseIcon } from "../ui/icons";
import { adminFetch } from "./api";

const DURATIONS = [0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5];
const SLOTS = Array.from({ length: (CLOSE_MIN - 30 - OPEN_MIN) / SLOT_MIN + 1 }, (_, i) => OPEN_MIN + i * SLOT_MIN);

export function NewBookingModal({
  open,
  onClose,
  onCreated,
  today,
  defaultTable,
}: {
  open: boolean;
  onClose: () => void;
  onCreated: (id: number) => void;
  today: string;
  defaultTable?: TableId;
}) {
  const ref = useRef<HTMLDialogElement>(null);
  const [tableId, setTableId] = useState<TableId>(defaultTable ?? "t1");
  const [name, setName] = useState("Гость");
  const [phone, setPhone] = useState("+996 ");
  const [guests, setGuests] = useState(2);
  const [startNow, setStartNow] = useState(true);
  const [date, setDate] = useState(today);
  const [startMinutes, setStartMinutes] = useState(18 * 60);
  const [duration, setDuration] = useState(1);
  const [comment, setComment] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    const d = ref.current;
    if (!d) return;
    if (open && !d.open) {
      setError(null);
      if (defaultTable) setTableId(defaultTable);
      d.showModal();
    }
    if (!open && d.open) d.close();
  }, [open, defaultTable]);

  async function submit(e: FormEvent) {
    e.preventDefault();
    setSaving(true);
    setError(null);
    const res = await adminFetch("/api/admin/bookings", {
      method: "POST",
      body: JSON.stringify({
        tableId,
        name,
        phone,
        guests,
        durationHours: duration,
        comment: comment || undefined,
        ...(startNow ? { startNow: true } : { date, startMinutes }),
      }),
    });
    setSaving(false);
    if (!res.ok) return setError(res.error ?? "Ошибка");
    onCreated(res.id as number);
    setComment("");
    setPhone("+996 ");
    setName("Гость");
  }

  return (
    <dialog
      ref={ref}
      onClose={onClose}
      aria-labelledby="nb-title"
      className="m-auto w-[min(560px,calc(100vw-24px))] rounded-2xl border border-white/10 bg-ink-900 p-0 text-white backdrop:bg-black/70 backdrop:backdrop-blur-sm"
    >
      <form onSubmit={submit} className="grid gap-4 p-5 sm:p-6">
        <div className="flex items-center justify-between">
          <h2 id="nb-title" className="text-xl font-extrabold">Новая бронь вручную</h2>
          <button type="button" onClick={onClose} className="rounded-lg p-2 text-mute hover:bg-white/5 hover:text-white" aria-label="Закрыть">
            <CloseIcon />
          </button>
        </div>

        <div className="grid grid-cols-2 gap-3">
          <div>
            <label htmlFor="nb-table" className="label">Стол</label>
            <select id="nb-table" className="field" value={tableId} onChange={(e) => setTableId(e.target.value as TableId)}>
              {TABLE_IDS.map((id) => (
                <option key={id} value={id}>{TABLE_META[id].name}</option>
              ))}
            </select>
          </div>
          <div>
            <label htmlFor="nb-guests" className="label">Гостей</label>
            <input id="nb-guests" type="number" min={1} max={TABLE_META[tableId].capacity} className="field" value={guests} onChange={(e) => setGuests(Number(e.target.value))} />
          </div>
          <div>
            <label htmlFor="nb-name" className="label">Имя</label>
            <input id="nb-name" className="field" value={name} onChange={(e) => setName(e.target.value)} maxLength={60} required />
          </div>
          <div>
            <label htmlFor="nb-phone" className="label">Телефон</label>
            <input id="nb-phone" type="tel" className="field" value={phone} onChange={(e) => setPhone(formatPhoneMask(e.target.value))}
              onFocus={caretToEnd} required />
          </div>
        </div>

        <fieldset className="grid gap-3">
          <legend className="label">Начало</legend>
          <div className="flex gap-2">
            <button type="button" role="radio" aria-checked={startNow} className="chip flex-1" onClick={() => setStartNow(true)}>Сейчас (гость в зале)</button>
            <button type="button" role="radio" aria-checked={!startNow} className="chip flex-1" onClick={() => setStartNow(false)}>Выбрать время</button>
          </div>
          {!startNow && (
            <div className="grid grid-cols-2 gap-3">
              <label className="sr-only" htmlFor="nb-date">Дата</label>
              <input id="nb-date" type="date" className="field" value={date} onChange={(e) => setDate(e.target.value)} />
              <label className="sr-only" htmlFor="nb-time">Время</label>
              <select id="nb-time" className="field" value={startMinutes} onChange={(e) => setStartMinutes(Number(e.target.value))}>
                {SLOTS.map((s) => (
                  <option key={s} value={s}>{minutesLabel(s)}</option>
                ))}
              </select>
            </div>
          )}
        </fieldset>

        <div>
          <label htmlFor="nb-dur" className="label">Длительность</label>
          <select id="nb-dur" className="field" value={duration} onChange={(e) => setDuration(Number(e.target.value))}>
            {DURATIONS.map((d) => (
              <option key={d} value={d}>{d < 1 ? "30 мин" : `${d} ч`}</option>
            ))}
          </select>
        </div>

        <div>
          <label htmlFor="nb-comment" className="label">Комментарий</label>
          <input id="nb-comment" className="field" value={comment} onChange={(e) => setComment(e.target.value)} maxLength={500} />
        </div>

        {error && <p role="alert" className="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">{error}</p>}

        <Button type="submit" disabled={saving}>{saving ? "Сохраняем…" : "Создать бронь"}</Button>
      </form>
    </dialog>
  );
}
