import { getTableStatuses } from "@/lib/booking-service";
import { handleError, json } from "@/lib/http";

export const dynamic = "force-dynamic";

/** Protected by middleware. */
export async function GET() {
  try {
    return json({ ok: true, now: new Date().toISOString(), tables: await getTableStatuses() });
  } catch (e) {
    return handleError(e, "admin:overview");
  }
}
