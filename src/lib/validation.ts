import { z } from "zod";
import { BOOKING_STATUSES, FIRST_START_MIN, LAST_START_MIN, MAX_DURATION_H, MIN_DURATION_H, SLOT_MIN, TABLE_IDS } from "./config";
import { normalizePhone } from "./phone";
import { isValidDateStr } from "./time";

/** Strip control chars and angle brackets, collapse whitespace. React escapes output too — defense in depth. */
export function sanitizeText(s: string): string {
  return s
    .replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F\u007F]/g, "")
    .replace(/[<>]/g, "")
    .replace(/[ \t]+/g, " ")
    .trim();
}

const nameField = z
  .string({ required_error: "Укажите имя" })
  .transform(sanitizeText)
  .pipe(
    z
      .string()
      .min(2, "Имя слишком короткое")
      .max(60, "Имя слишком длинное")
      .regex(/^[\p{L}\p{M}\s'’.-]+$/u, "Имя может содержать только буквы"),
  );

const phoneField = z
  .string({ required_error: "Укажите телефон" })
  .max(32)
  .transform((v, ctx) => {
    const p = normalizePhone(v);
    if (!p) {
      ctx.addIssue({ code: z.ZodIssueCode.custom, message: "Телефон в формате +996 XXX XXX XXX" });
      return z.NEVER;
    }
    return p;
  });

const commentField = z
  .string()
  .max(500, "Комментарий до 500 символов")
  .transform((v) => sanitizeText(v.replace(/\r\n?/g, "\n")))
  .optional()
  .nullable();

const dateField = z.string().refine(isValidDateStr, "Некорректная дата");

const startMinutesField = z.coerce
  .number()
  .int()
  .min(FIRST_START_MIN, "Некорректное время")
  .max(LAST_START_MIN, "Некорректное время")
  .refine((m) => m % SLOT_MIN === 0, "Время кратно 30 минутам");

export const bookingCreateSchema = z
  .object({
    name: nameField,
    phone: phoneField,
    tableId: z.enum(TABLE_IDS, { errorMap: () => ({ message: "Выберите стол" }) }),
    date: dateField,
    startMinutes: startMinutesField,
    durationHours: z.coerce
      .number()
      .int()
      .min(MIN_DURATION_H, "Минимум 1 час")
      .max(MAX_DURATION_H, "Максимум 5 часов"),
    guests: z.coerce.number().int().min(1, "Минимум 1 гость").max(10, "Максимум 10 гостей"),
    comment: commentField,
    /** Honeypot: must stay empty. */
    website: z.string().max(200).optional(),
  })
  .strict();

export type BookingCreateInput = z.infer<typeof bookingCreateSchema>;

/** Admin: manual booking (walk-in). `startMinutes` optional → starts now. */
export const adminBookingCreateSchema = z
  .object({
    name: nameField,
    phone: phoneField,
    tableId: z.enum(TABLE_IDS),
    date: dateField.optional(),
    startMinutes: z.coerce.number().int().min(FIRST_START_MIN).max(LAST_START_MIN).optional(),
    startNow: z.boolean().optional(),
    durationHours: z.coerce.number().min(0.5).max(MAX_DURATION_H).refine((h) => (h * 2) % 1 === 0, "Шаг 30 минут"),
    guests: z.coerce.number().int().min(1).max(10),
    comment: commentField,
  })
  .strict();

export const adminBookingActionSchema = z.discriminatedUnion("action", [
  z.object({ action: z.literal("confirm") }).strict(),
  z.object({ action: z.literal("cancel") }).strict(),
  z.object({ action: z.literal("complete") }).strict(),
  z
    .object({
      action: z.literal("extend"),
      minutes: z.coerce.number().int().min(30).max(300).refine((m) => m % 30 === 0, "Шаг 30 минут"),
    })
    .strict(),
]);

export const adminBookingsQuerySchema = z.object({
  date: dateField.optional(),
  tableId: z.enum(TABLE_IDS).optional(),
  status: z.enum(BOOKING_STATUSES).optional(),
  q: z.string().max(60).transform(sanitizeText).optional(),
});

export const loginSchema = z.object({ password: z.string().min(1).max(128) }).strict();

export const quickContactSchema = z.object({
  name: nameField,
  phone: phoneField,
  message: z.string().transform(sanitizeText).pipe(z.string().min(2, "Напишите сообщение").max(1000)),
});

export function zodFieldErrors(err: z.ZodError): Record<string, string> {
  const out: Record<string, string> = {};
  for (const issue of err.issues) {
    const key = issue.path.join(".") || "_";
    if (!out[key]) out[key] = issue.message;
  }
  return out;
}
