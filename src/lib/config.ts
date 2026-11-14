/** Public, non-secret club configuration (safe to ship to the browser). */
export const CLUB = {
  name: "Бильярд Старт",
  fullName: "Бильярд Старт",
  slogan: "Твоя игра начинается здесь.",
  description:
    "«Бильярд Старт» — бильярдный клуб в Караколе: 6 профессиональных столов и 2 VIP-комнаты. Онлайн-бронирование за минуту, ответ в WhatsApp.",
  city: "Каракол",
  address: "г. Каракол",
  /** 2GIS: search in Karakol. Replace with the club's exact 2GIS card link when available. */
  mapUrl: "https://2gis.kg/karakol/search/%D0%91%D0%B8%D0%BB%D1%8C%D1%8F%D1%80%D0%B4%20%D0%A1%D1%82%D0%B0%D1%80%D1%82",
  phoneDisplay: "+996 (506) 056-556",
  phoneHref: "tel:+996506056556",
  instagram: "https://www.instagram.com/starbilliard.karakol/",
  instagramHandle: "@starbilliard.karakol",
  hoursLabel: "Ежедневно 12:00 – 02:00",
  priceRegular: 600,
  priceVip: 1500,
} as const;

/** Working hours, in minutes from the business day's midnight (Asia/Bishkek). */
export const OPEN_MIN = 12 * 60; // 12:00
export const CLOSE_MIN = 26 * 60; // 02:00 next day
export const SLOT_MIN = 30;
export const MAX_DURATION_H = 5;
export const MIN_DURATION_H = 1;

export const TABLE_IDS = ["t1", "t2", "t3", "t4", "t5", "t6", "vip1", "vip2"] as const;
export type TableId = (typeof TABLE_IDS)[number];

export const TABLE_META: Record<TableId, { name: string; type: "REGULAR" | "VIP"; capacity: number }> = {
  t1: { name: "Стол 1", type: "REGULAR", capacity: 6 },
  t2: { name: "Стол 2", type: "REGULAR", capacity: 6 },
  t3: { name: "Стол 3", type: "REGULAR", capacity: 6 },
  t4: { name: "Стол 4", type: "REGULAR", capacity: 6 },
  t5: { name: "Стол 5", type: "REGULAR", capacity: 6 },
  t6: { name: "Стол 6", type: "REGULAR", capacity: 6 },
  vip1: { name: "VIP 1", type: "VIP", capacity: 10 },
  vip2: { name: "VIP 2", type: "VIP", capacity: 10 },
};

export const BOOKING_STATUSES = ["PENDING", "CONFIRMED", "COMPLETED", "CANCELLED"] as const;
export type BookingStatus = (typeof BOOKING_STATUSES)[number];
export const ACTIVE_STATUSES: BookingStatus[] = ["PENDING", "CONFIRMED"];

export const STATUS_LABEL: Record<BookingStatus, string> = {
  PENDING: "Ожидает",
  CONFIRMED: "Подтверждена",
  COMPLETED: "Завершена",
  CANCELLED: "Отменена",
};

export const POLL_INTERVAL_MS = 30_000;
