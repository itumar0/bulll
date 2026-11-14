import { BookingError, extendBooking, setBookingStatus } from "@/lib/booking-service";
import { handleError, json, readJson } from "@/lib/http";
import { adminBookingActionSchema } from "@/lib/validation";

export const dynamic = "force-dynamic";

export async function PATCH(req: Request, { params }: { params: Promise<{ id: string }> }) {
  try {
    const { id: rawId } = await params;
    const id = Number(rawId);
    if (!Number.isInteger(id) || id <= 0) throw new BookingError("Некорректный номер брони", 400);

    const body = adminBookingActionSchema.parse(await readJson(req, 1024));
    const updated =
      body.action === "extend" ? await extendBooking(id, body.minutes) : await setBookingStatus(id, body.action);

    return json({ ok: true, booking: { id: updated.id, status: updated.status, endAt: updated.endAt.toISOString() } });
  } catch (e) {
    return handleError(e, "admin:bookings:update");
  }
}
