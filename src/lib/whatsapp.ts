import { formatDate, formatTime } from "./time";

export function waLink(number: string, text?: string): string {
  const clean = number.replace(/\D/g, "");
  return text ? `https://wa.me/${clean}?text=${encodeURIComponent(text)}` : `https://wa.me/${clean}`;
}

export interface BookingMessageInput {
  id: number;
  name: string;
  phone: string;
  tableName: string;
  startAt: Date | string;
  endAt: Date | string;
  guests: number;
  comment?: string | null;
}

export function bookingMessage(b: BookingMessageInput): string {
  return [
    "Новая бронь 🎱",
    `Имя: ${b.name}`,
    `Телефон: ${b.phone}`,
    `Стол: ${b.tableName}`,
    `Дата: ${formatDate(b.startAt)}`,
    `Время: ${formatTime(b.startAt)} – ${formatTime(b.endAt)}`,
    `Гостей: ${b.guests}`,
    `Комментарий: ${b.comment?.trim() ? b.comment : "—"}`,
    `№ брони: ${b.id}`,
  ].join("\n");
}

export function quickContactMessage(name: string, phone: string, message: string): string {
  return [`Здравствуйте! Меня зовут ${name}.`, `Телефон: ${phone}`, "", message].join("\n");
}

/** Message the admin sends to a client from the dashboard. */
export function clientMessage(b: Omit<BookingMessageInput, "phone" | "guests" | "comment">): string {
  return `Здравствуйте, ${b.name}! Это бильярдный клуб RED SHOT. По вашей брони №${b.id}: ${b.tableName}, ${formatDate(
    b.startAt,
  )}, ${formatTime(b.startAt)} – ${formatTime(b.endAt)}.`;
}
