import "server-only";
import { NextResponse } from "next/server";
import { ZodError } from "zod";
import { BookingError } from "./booking-service";
import { zodFieldErrors } from "./validation";

const NO_STORE = { "Cache-Control": "no-store" };

export function json<T>(data: T, init: ResponseInit = {}) {
  return NextResponse.json(data, { ...init, headers: { ...NO_STORE, ...(init.headers || {}) } });
}

export function jsonError(message: string, status = 400, extra: Record<string, unknown> = {}) {
  return json({ ok: false, error: message, ...extra }, { status });
}

/** Map known errors to responses. Never logs request bodies (personal data). */
export function handleError(err: unknown, where: string) {
  if (err instanceof ZodError) {
    return jsonError("Проверьте правильность заполнения формы", 422, { fields: zodFieldErrors(err) });
  }
  if (err instanceof BookingError) {
    return jsonError(err.message, err.status, err.field ? { fields: { [err.field]: err.message } } : {});
  }
  if (err instanceof SyntaxError) return jsonError("Некорректный запрос", 400);
  console.error(`[${where}] unexpected error:`, err instanceof Error ? err.name + ": " + err.message : "unknown");
  return jsonError("Внутренняя ошибка сервера. Попробуйте позже.", 500);
}

/** Read JSON with a hard size cap. */
export async function readJson(req: Request, maxBytes = 8 * 1024): Promise<unknown> {
  const text = await req.text();
  if (text.length > maxBytes) throw new SyntaxError("Body too large");
  return JSON.parse(text);
}
