import { NextRequest } from "next/server";
import { getBusyIntervals } from "@/lib/booking-service";
import { handleError, json, jsonError } from "@/lib/http";
import { isValidDateStr } from "@/lib/time";

export const dynamic = "force-dynamic";

export async function GET(req: NextRequest) {
  const date = req.nextUrl.searchParams.get("date") ?? "";
  if (!isValidDateStr(date)) return jsonError("Некорректная дата", 400);
  try {
    return json({ ok: true, date, now: new Date().toISOString(), busy: await getBusyIntervals(date) });
  } catch (e) {
    return handleError(e, "availability");
  }
}
