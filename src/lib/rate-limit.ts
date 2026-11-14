import "server-only";

/**
 * In-memory fixed-window rate limiter with lockout.
 * Fine for a single Node process (VPS / `next start`). For multi-instance or
 * serverless deployments, swap the store for Redis/Upstash (same interface).
 */
interface Entry {
  count: number;
  windowStart: number;
  blockedUntil: number;
}

const store = new Map<string, Entry>();
let lastSweep = Date.now();

function sweep(now: number) {
  if (now - lastSweep < 60_000) return;
  lastSweep = now;
  for (const [k, e] of store) {
    if (e.blockedUntil < now && now - e.windowStart > 24 * 60 * 60 * 1000) store.delete(k);
  }
}

export interface LimitResult {
  allowed: boolean;
  retryAfterSec: number;
  remaining: number;
}

export function rateLimit(key: string, limit: number, windowMs: number, blockMs = windowMs): LimitResult {
  const now = Date.now();
  sweep(now);
  let e = store.get(key);
  if (e && e.blockedUntil > now) {
    return { allowed: false, retryAfterSec: Math.ceil((e.blockedUntil - now) / 1000), remaining: 0 };
  }
  if (!e || now - e.windowStart >= windowMs) {
    e = { count: 0, windowStart: now, blockedUntil: 0 };
    store.set(key, e);
  }
  e.count += 1;
  if (e.count > limit) {
    e.blockedUntil = now + blockMs;
    return { allowed: false, retryAfterSec: Math.ceil(blockMs / 1000), remaining: 0 };
  }
  return { allowed: true, retryAfterSec: 0, remaining: limit - e.count };
}

export function resetRateLimit(key: string) {
  store.delete(key);
}

/** Best-effort client IP. Behind a reverse proxy make sure it sets X-Forwarded-For / X-Real-IP. */
export function clientIp(req: Request): string {
  const xff = req.headers.get("x-forwarded-for");
  if (xff) return xff.split(",")[0].trim();
  return req.headers.get("x-real-ip")?.trim() || "unknown";
}
