import { createBooking, websiteInterval } from "@/lib/booking-service";
import { whatsappNumber } from "@/lib/env";
import { handleError, json, jsonError, readJson } from "@/lib/http";
import { clientIp, rateLimit } from "@/lib/rate-limit";
import { bookingCreateSchema } from "@/lib/validation";
import { bookingMessage, waLink } from "@/lib/whatsapp";

export const dynamic = "force-dynamic";

export async function POST(req: Request) {
  const ip = clientIp(req);
  // Anti-spam: 5 bookings / 10 min per IP, then 30 min cooldown.
  const rl = rateLimit(`booking:${ip}`, 5, 10 * 60_000, 30 * 60_000);
  if (!rl.allowed) {
    return jsonError("Слишком много заявок. Попробуйте позже или напишите нам в WhatsApp.", 429, {
      retryAfter: rl.retryAfterSec,
    });
  }

  try {
    const input = bookingCreateSchema.parse(await readJson(req));

    // Honeypot: bots fill hidden fields. Pretend success, store nothing.
    if (input.website && input.website.trim() !== "") {
      return json({ ok: true, id: 0, waUrl: waLink(whatsappNumber()) }, { status: 201 });
    }

    const { startAt, endAt } = websiteInterval(input.date, input.startMinutes, input.durationHours);
    const booking = await createBooking({
      tableId: input.tableId,
      name: input.name,
      phone: input.phone,
      guests: input.guests,
      comment: input.comment,
      startAt,
      endAt,
      status: "PENDING",
      source: "WEBSITE",
    });

    const text = bookingMessage({
      id: booking.id,
      name: booking.name,
      phone: booking.phone,
      tableName: booking.table.name,
      startAt: booking.startAt,
      endAt: booking.endAt,
      guests: booking.guests,
      comment: booking.comment,
    });

    return json({ ok: true, id: booking.id, waUrl: waLink(whatsappNumber(), text) }, { status: 201 });
  } catch (e) {
    return handleError(e, "bookings:create");
  }
}
