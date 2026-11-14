import bcrypt from "bcryptjs";
import { cookies } from "next/headers";
import { SESSION_COOKIE, createSessionToken, sessionCookieOptions } from "@/lib/auth";
import { adminPasswordHash } from "@/lib/env";
import { handleError, json, jsonError, readJson } from "@/lib/http";
import { clientIp, rateLimit, resetRateLimit } from "@/lib/rate-limit";
import { loginSchema } from "@/lib/validation";

export const dynamic = "force-dynamic";
export const runtime = "nodejs";

const WINDOW_MS = 15 * 60_000;

export async function POST(req: Request) {
  const key = `login:${clientIp(req)}`;
  // Max 5 attempts per 15 min per IP, then a 15 min lockout.
  const rl = rateLimit(key, 5, WINDOW_MS, WINDOW_MS);
  if (!rl.allowed) {
    const min = Math.ceil(rl.retryAfterSec / 60);
    return jsonError(`Слишком много попыток. Повторите через ${min} мин.`, 429, { retryAfter: rl.retryAfterSec });
  }

  try {
    const { password } = loginSchema.parse(await readJson(req, 1024));
    const ok = await bcrypt.compare(password, adminPasswordHash());
    if (!ok) {
      return jsonError(
        rl.remaining > 0 ? `Неверный пароль. Осталось попыток: ${rl.remaining}` : "Неверный пароль. Вход временно заблокирован.",
        401,
      );
    }
    resetRateLimit(key);
    const token = await createSessionToken();
    (await cookies()).set(SESSION_COOKIE, token, sessionCookieOptions());
    return json({ ok: true });
  } catch (e) {
    return handleError(e, "admin:login");
  }
}
