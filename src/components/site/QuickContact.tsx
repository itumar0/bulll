"use client";

import { useId, useState, type FormEvent } from "react";
import { quickContactSchema, zodFieldErrors } from "@/lib/validation";
import { caretToEnd, formatPhoneMask } from "@/lib/phone";
import { quickContactMessage, waLink } from "@/lib/whatsapp";
import { WhatsAppIcon } from "../ui/icons";

export function QuickContact({ whatsapp }: { whatsapp: string }) {
  const uid = useId();
  const [name, setName] = useState("");
  const [phone, setPhone] = useState("+996 ");
  const [message, setMessage] = useState("");
  const [errors, setErrors] = useState<Record<string, string>>({});

  function onSubmit(e: FormEvent) {
    e.preventDefault();
    const parsed = quickContactSchema.safeParse({ name, phone, message });
    if (!parsed.success) {
      const errs = zodFieldErrors(parsed.error);
      setErrors(errs);
      document.getElementById(`${uid}-${Object.keys(errs)[0]}`)?.focus();
      return;
    }
    setErrors({});
    const { name: n, phone: p, message: m } = parsed.data;
    const url = waLink(whatsapp, quickContactMessage(n, p, m));
    const w = window.open(url, "_blank");
    if (w) w.opener = null;
    else window.location.href = url; // popup blocked → open in the same tab
  }

  const field = (k: string) => ({
    id: `${uid}-${k}`,
    "aria-invalid": errors[k] ? true : undefined,
    "aria-describedby": errors[k] ? `${uid}-${k}-err` : undefined,
  });
  const err = (k: string) =>
    errors[k] && (
      <p id={`${uid}-${k}-err`} role="alert" className="mt-2 text-sm text-red-400">
        {errors[k]}
      </p>
    );

  return (
    <form onSubmit={onSubmit} noValidate className="card grid gap-5 p-5 sm:p-8">
      <div className="grid gap-5 sm:grid-cols-2">
        <div>
          <label htmlFor={`${uid}-name`} className="label">Имя</label>
          <input {...field("name")} className="field" autoComplete="given-name" maxLength={60} value={name} onChange={(e) => setName(e.target.value)} placeholder="Ваше имя" />
          {err("name")}
        </div>
        <div>
          <label htmlFor={`${uid}-phone`} className="label">Телефон</label>
          <input {...field("phone")} className="field tabular-nums" type="tel" inputMode="tel" autoComplete="tel" value={phone} onChange={(e) => setPhone(formatPhoneMask(e.target.value))}
              onFocus={caretToEnd} />
          {err("phone")}
        </div>
      </div>
      <div>
        <label htmlFor={`${uid}-message`} className="label">Сообщение</label>
        <textarea {...field("message")} className="field min-h-32 resize-y" maxLength={1000} value={message} onChange={(e) => setMessage(e.target.value)} placeholder="Вопрос о ценах, турнирах, корпоративе…" />
        {err("message")}
      </div>
      <button type="submit" className="inline-flex h-14 items-center justify-center gap-2.5 rounded-2xl bg-[#25D366] px-7 font-bold text-ink-950 transition hover:brightness-110 active:scale-[0.98]">
        <WhatsAppIcon /> Отправить в WhatsApp
      </button>
    </form>
  );
}
