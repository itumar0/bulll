import "server-only";
import { z } from "zod";

const schema = z.object({
  DATABASE_URL: z.string().min(1),
  WHATSAPP_NUMBER: z
    .string()
    .regex(/^\d{10,15}$/, "WHATSAPP_NUMBER: digits only, e.g. 996506056556")
    .default("996506056556"),
  ADMIN_PASSWORD_HASH: z.string().min(20, "ADMIN_PASSWORD_HASH must be a bcrypt hash"),
  JWT_SECRET: z.string().min(32, "JWT_SECRET must be at least 32 characters"),
});

export type Env = z.infer<typeof schema>;
let cached: Env | null = null;

/** Validated server env. Lazy so `next build` does not require secrets. */
export function env(): Env {
  if (cached) return cached;
  const parsed = schema.safeParse(process.env);
  if (!parsed.success) {
    const issues = parsed.error.issues.map((i) => `${i.path.join(".")}: ${i.message}`).join("; ");
    throw new Error(`Invalid environment configuration: ${issues}`);
  }
  cached = parsed.data;
  return cached;
}

/** Public WhatsApp number (non-secret, but kept in env as required). */
export function whatsappNumber(): string {
  return process.env.WHATSAPP_NUMBER?.replace(/\D/g, "") || "996506056556";
}

/**
 * bcrypt hashes contain `$`, which Next.js' dotenv-expand would treat as variables.
 * We accept either the escaped form (\$2b\$12\$...) — already unescaped by the loader —
 * or a base64-encoded hash as a fallback.
 */
export function adminPasswordHash(): string {
  const raw = env().ADMIN_PASSWORD_HASH.trim();
  if (raw.startsWith("$2")) return raw;
  const decoded = Buffer.from(raw, "base64").toString("utf8");
  if (decoded.startsWith("$2")) return decoded;
  throw new Error("ADMIN_PASSWORD_HASH is not a valid bcrypt hash");
}
