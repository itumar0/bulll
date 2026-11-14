import { getTableStatuses, toPublicStatus } from "@/lib/booking-service";
import { handleError, json } from "@/lib/http";

export const dynamic = "force-dynamic";

export async function GET() {
  try {
    const statuses = await getTableStatuses();
    return json({ ok: true, now: new Date().toISOString(), tables: statuses.map(toPublicStatus) });
  } catch (e) {
    return handleError(e, "tables/status");
  }
}
