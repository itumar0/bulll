/** Public, non-secret club configuration (safe to ship to the browser). */
export const CLUB = {
  name: "Бильярд Старт",
  fullName: "Бильярд Старт",
  slogan: "Твоя игра начинается здесь.",
  description:
    "«Бильярд Старт» — бильярдный клуб в Караколе: 6 профессиональных столов и 2 VIP-комнаты. Онлайн-бронирование за минуту, ответ в WhatsApp.",
  city: "Каракол",
  address: "г. Каракол",
  mapUrl: "https://2gis.kg/bishkek/geo/70030076631151014/78.390321,42.489646",
  phoneDisplay: "+996 (506) 056-556",
  phoneHref: "tel:+996506056556",
  instagram: "https://www.instagram.com/starbilliard.karakol/",
  instagramHandle: "@starbilliard.karakol",
  hoursLabel: "Круглосуточно, 24/7",
  priceRegular: 250,
  priceVip: 350,
} as const;

/** The club works 24/7. Times are minutes from local midnight (Asia/Bishkek). */
export const SLOT_MIN = 30;
export const MAX_DURATION_H = 5;
export const MIN_DURATION_H = 1;
export const FIRST_START_MIN = 0; // 00:00
export const LAST_START_MIN = 24 * 60 - SLOT_MIN; // 23:30
/** Latest possible end of a booking that starts on a given date (may cross midnight). */
export const MAX_END_MIN = LAST_START_MIN + MAX_DURATION_H * 60;

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
