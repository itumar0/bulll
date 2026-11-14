"use client";

import { useEffect, useId, useMemo, useState, type FormEvent } from "react";
import { usePolling, useNow } from "@/hooks/usePolling";
import { CLOSE_MIN, MAX_DURATION_H, OPEN_MIN, POLL_INTERVAL_MS, SLOT_MIN, TABLE_IDS, TABLE_META, type TableId } from "@/lib/config";
import { caretToEnd, formatPhoneMask, normalizePhone } from "@/lib/phone";
import { addDays, businessDate, businessMinutes, formatDateHuman, minutesLabel } from "@/lib/time";
import type { BusyInterval } from "@/lib/types";
import { Button } from "../ui/Button";
import { CheckIcon, WhatsAppIcon } from "../ui/icons";
import { SELECT_TABLE_EVENT } from "./TablesSection";

interface AvailabilityResponse {
  ok: boolean;
  busy: BusyInterval[];
}

const SLOTS = Array.from({ length: (CLOSE_MIN - 60 - OPEN_MIN) / SLOT_MIN + 1 }, (_, i) => OPEN_MIN + i * SLOT_MIN);
const DURATIONS = Array.from({ length: MAX_DURATION_H }, (_, i) => i + 1);
const PAST_GRACE_MIN = 25;

type Errors = Partial<Record<string, string>>;

export function BookingForm({ today: serverToday }: { today: string }) {
  const uid = useId();
  const now = useNow(30_000);
  const today = useMemo(() => {
    const t = businessDate(new Date(now));
    return t > serverToday ? t : serverToday;
  }, [now, serverToday]);

  const [name, setName] = useState("");
  const [phone, setPhone] = useState("+996 ");
  const [tableId, setTableId] = useState<TableId>("t1");
  const [date, setDate] = useState(serverToday);
  const [start, setStart] = useState<number | null>(null);
  const [duration, setDuration] = useState(2);
  const [guests, setGuests] = useState(2);
  const [comment, setComment] = useState("");
  const [website, setWebsite] = useState(""); // honeypot
  const [errors, setErrors] = useState<Errors>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [success, setSuccess] = useState<{ id: number; waUrl: string } | null>(null);

  const { data, reload } = usePolling<AvailabilityResponse>(`/api/availability?date=${date}`, POLL_INTERVAL_MS);

  // Table cards in the "Наши столы" section pre-select a table here.
  useEffect(() => {
    const onPick = (e: Event) => {
      const id = (e as CustomEvent<string>).detail;
      if ((TABLE_IDS as readonly string[]).includes(id)) setTableId(id as TableId);
    };
    window.addEventListener(SELECT_TABLE_EVENT, onPick);
    return () => window.removeEventListener(SELECT_TABLE_EVENT, onPick);
  }, []);

  const capacity = TABLE_META[tableId].capacity;
  useEffect(() => setGuests((g) => Math.min(g, capacity)), [capacity]);

  /** Busy intervals of the selected table, as business-day minutes. */
  const busy = useMemo(
    () =>
      (data?.busy ?? [])
        .filter((b) => b.tableId === tableId)
        .map((b) => [businessMinutes(new Date(b.start), date), businessMinutes(new Date(b.end), date)] as const),
    [data, tableId, date],
  );

  const nowMin = date === today ? businessMinutes(new Date(now), date) : date < today ? Infinity : -Infinity;
  const overlaps = (s: number, e: number) => busy.some(([bs, be]) => s < be && e > bs);
  const isPast = (s: number) => s < nowMin - PAST_GRACE_MIN;
  const slotDisabled = (s: number) => isPast(s) || s + duration * 60 > CLOSE_MIN || overlaps(s, s + duration * 60);
  const slotBusy = (s: number) => overlaps(s, s + SLOT_MIN);
  const durationDisabled = (d: number) => start !== null && (start + d * 60 > CLOSE_MIN || overlaps(start, start + d * 60));

  // Drop a start time that became unavailable (new data, table, date or duration).
  useEffect(() => {
    if (start !== null && slotDisabled(start)) setStart(null);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [busy, duration, date, tableId, nowMin]);

  const freeSlots = SLOTS.filter((s) => !slotDisabled(s)).length;

  function validate(): Errors {
    const e: Errors = {};
    if (name.trim().length < 2) e.name = "Укажите имя";
    if (!normalizePhone(phone)) e.phone = "Телефон в формате +996 XXX XXX XXX";
    if (start === null) e.startMinutes = "Выберите время начала";
    if (guests < 1 || guests > capacity) e.guests = `От 1 до ${capacity} гостей`;
    return e;
  }

  async function onSubmit(ev: FormEvent) {
    ev.preventDefault();
    setFormError(null);
    const v = validate();
    setErrors(v);
    if (Object.keys(v).length) {
      document.getElementById(`${uid}-${Object.keys(v)[0]}`)?.focus();
      return;
    }
    setSubmitting(true);
    try {
      const res = await fetch("/api/bookings", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name,
          phone,
          tableId,
          date,
          startMinutes: start,
          durationHours: duration,
          guests,
          comment: comment || undefined,
          website,
        }),
      });
      const json = await res.json().catch(() => ({}));
      if (!res.ok || !json.ok) {
        setErrors(json.fields ?? {});
        setFormError(json.error ?? "Не удалось создать бронь. Попробуйте ещё раз.");
        if (res.status === 409) reload();
        return;
      }
      setSuccess({ id: json.id, waUrl: json.waUrl });
      reload();
      // Redirect to WhatsApp with the prepared message.
      window.setTimeout(() => {
        window.location.href = json.waUrl;
      }, 900);
    } catch {
      setFormError("Нет соединения с сервером. Проверьте интернет.");
    } finally {
      setSubmitting(false);
    }
  }

  if (success) {
    return (
      <div className="card flex flex-col items-center p-8 text-center sm:p-12" role="status">
        <span className="grid h-16 w-16 place-items-center rounded-full bg-red-600 shadow-glow">
          <CheckIcon className="h-8 w-8" />
        </span>
        <h3 className="mt-6 text-2xl font-extrabold sm:text-3xl">Бронь №{success.id} создана</h3>
        <p className="mt-3 max-w-md text-mute">
          Открываем WhatsApp с готовым сообщением — отправьте его, чтобы мы подтвердили бронь.
        </p>
        <a href={success.waUrl} className="mt-8 inline-flex h-14 items-center gap-2.5 rounded-2xl bg-[#25D366] px-7 font-bold text-ink-950 transition hover:brightness-110">
          <WhatsAppIcon /> Открыть WhatsApp
        </a>
        <button
          type="button"
          onClick={() => {
            setSuccess(null);
            setStart(null);
            setComment("");
          }}
          className="mt-4 text-sm text-mute underline-offset-4 hover:text-white hover:underline"
        >
          Создать ещё одну бронь
        </button>
      </div>
    );
  }

  const err = (k: string) =>
    errors[k] ? (
      <p id={`${uid}-${k}-err`} className="mt-2 text-sm text-red-400" role="alert">
        {errors[k]}
      </p>
    ) : null;
  const errProps = (k: string) => ({
    id: `${uid}-${k}`,
    "aria-invalid": errors[k] ? true : undefined,
    "aria-describedby": errors[k] ? `${uid}-${k}-err` : undefined,
  });

  return (
    <form onSubmit={onSubmit} noValidate className="card relative grid gap-8 p-5 sm:p-8 lg:grid-cols-2 lg:gap-10" aria-describedby={formError ? `${uid}-form-err` : undefined}>
      {/* Honeypot — invisible for humans, tempting for bots */}
      <div aria-hidden className="absolute -left-[9999px] h-px w-px overflow-hidden">
        <label htmlFor={`${uid}-website`}>Ваш сайт</label>
        <input id={`${uid}-website`} name="website" tabIndex={-1} autoComplete="off" value={website} onChange={(e) => setWebsite(e.target.value)} />
      </div>

      {/* Left: contacts & table */}
      <div className="grid content-start gap-6">
        <div className="grid gap-5 sm:grid-cols-2">
          <div>
            <label htmlFor={`${uid}-name`} className="label">Имя</label>
            <input {...errProps("name")} className="field" autoComplete="given-name" placeholder="Как к вам обращаться" maxLength={60} value={name} onChange={(e) => setName(e.target.value)} required />
            {err("name")}
          </div>
          <div>
            <label htmlFor={`${uid}-phone`} className="label">Телефон</label>
            <input
              {...errProps("phone")}
              className="field tabular-nums"
              type="tel"
              inputMode="tel"
              autoComplete="tel"
              placeholder="+996 (___) ___-___"
              value={phone}
              onChange={(e) => setPhone(formatPhoneMask(e.target.value))}
              onFocus={caretToEnd}
              required
            />
            {err("phone")}
          </div>
        </div>

        <fieldset>
          <legend className="label">Стол</legend>
          <div role="radiogroup" aria-label="Выбор стола" className="grid grid-cols-6 gap-2">
            {TABLE_IDS.map((id) => (
              <button
                key={id}
                type="button"
                role="radio"
                aria-checked={tableId === id}
                onClick={() => setTableId(id)}
                className={`chip ${id.startsWith("vip") ? "col-span-3" : "col-span-2"}`}
              >
                {TABLE_META[id].name}
              </button>
            ))}
          </div>
        </fieldset>

        <div className="grid grid-cols-2 gap-5">
          <div>
            <label htmlFor={`${uid}-date`} className="label">Дата</label>
            <input
              {...errProps("date")}
              type="date"
              className="field"
              min={today}
              max={addDays(today, 60)}
              value={date}
              onChange={(e) => e.target.value && setDate(e.target.value)}
              required
            />
            <p className="mt-2 text-xs text-neutral-400">{formatDateHuman(date)}</p>
            {err("date")}
          </div>
          <div>
            <label htmlFor={`${uid}-guests`} className="label">Гостей</label>
            <div className="flex h-[54px] items-center rounded-xl border border-ink-700 bg-ink-900">
              <button type="button" className="h-full w-12 text-xl text-soft hover:text-white disabled:opacity-30" onClick={() => setGuests((g) => Math.max(1, g - 1))} disabled={guests <= 1} aria-label="Меньше гостей">−</button>
              <input
                {...errProps("guests")}
                type="number"
                inputMode="numeric"
                min={1}
                max={capacity}
                className="h-full w-full min-w-0 bg-transparent text-center font-bold tabular-nums outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none"
                value={guests}
                onChange={(e) => setGuests(Math.max(1, Math.min(capacity, Number(e.target.value) || 1)))}
              />
              <button type="button" className="h-full w-12 text-xl text-soft hover:text-white disabled:opacity-30" onClick={() => setGuests((g) => Math.min(capacity, g + 1))} disabled={guests >= capacity} aria-label="Больше гостей">+</button>
            </div>
            <p className="mt-2 text-xs text-neutral-400">максимум {capacity}</p>
            {err("guests")}
          </div>
        </div>

        <div>
          <label htmlFor={`${uid}-comment`} className="label">
            Комментарий <span className="font-normal text-neutral-400">— необязательно</span>
          </label>
          <textarea {...errProps("comment")} className="field min-h-24 resize-y" maxLength={500} placeholder="День рождения, нужен кий определённого веса…" value={comment} onChange={(e) => setComment(e.target.value)} />
          {err("comment")}
        </div>
      </div>

      {/* Right: time */}
      <div className="grid content-start gap-6">
        <fieldset>
          <legend className="label">Длительность</legend>
          <div role="radiogroup" aria-label="Длительность" className="grid grid-cols-5 gap-2">
            {DURATIONS.map((d) => (
              <button key={d} type="button" role="radio" aria-checked={duration === d} disabled={durationDisabled(d)} onClick={() => setDuration(d)} className="chip">
                {d} ч
              </button>
            ))}
          </div>
          {err("durationHours")}
        </fieldset>

        <fieldset>
          <legend className="label flex w-full items-center justify-between" id={`${uid}-startMinutes`} tabIndex={-1}>
            <span>Время начала</span>
            <span className="text-xs font-normal text-neutral-400" aria-live="polite">свободно слотов: {freeSlots}</span>
          </legend>
          <div role="radiogroup" aria-label="Время начала" className="grid grid-cols-4 gap-2 sm:grid-cols-5">
            {SLOTS.map((s) => {
              const disabled = slotDisabled(s);
              const busyNow = slotBusy(s);
              return (
                <button
                  key={s}
                  type="button"
                  role="radio"
                  aria-checked={start === s}
                  disabled={disabled}
                  onClick={() => setStart(s)}
                  className="chip tabular-nums"
                  aria-label={`${minutesLabel(s)}${busyNow ? ", занято" : disabled ? ", недоступно" : ""}`}
                  title={busyNow ? "Занято" : undefined}
                >
                  {minutesLabel(s)}
                </button>
              );
            })}
          </div>
          {err("startMinutes")}
          {freeSlots === 0 && <p className="mt-3 text-sm text-amber-200">На эту дату нет свободного времени для выбранного стола и длительности. Попробуйте другой стол или уменьшите длительность.</p>}
        </fieldset>

        <div className="rounded-2xl border border-white/[0.07] bg-ink-950/60 p-5">
          <p className="text-sm text-mute">Ваша бронь</p>
          <p className="mt-1 text-lg font-bold">
            {TABLE_META[tableId].name} · {formatDateHuman(date)}
          </p>
          <p className="mt-0.5 text-2xl font-extrabold tabular-nums text-red-500">
            {start !== null ? `${minutesLabel(start)} – ${minutesLabel(start + duration * 60)}` : "—:— – —:—"}
          </p>
        </div>

        {formError && (
          <p id={`${uid}-form-err`} role="alert" className="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
            {formError}
          </p>
        )}

        <Button type="submit" size="lg" disabled={submitting} className="w-full">
          {submitting ? "Сохраняем…" : "Забронировать и отправить в WhatsApp"}
        </Button>
        <p className="-mt-3 text-center text-xs text-neutral-400">Нажимая кнопку, вы соглашаетесь на обработку персональных данных для связи по брони.</p>
      </div>
    </form>
  );
}
