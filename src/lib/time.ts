/**
 * Time helpers for Asia/Bishkek (UTC+6, no DST since 2005).
 * All DB timestamps are UTC. The club works 24/7, so a booking date is simply the
 * local calendar date of its start; bookings may run past midnight.
 * Pure functions — safe on both server and client.
 */
import { MAX_END_MIN } from "./config";

export const TZ = "Asia/Bishkek";
const OFFSET_MS = 6 * 60 * 60 * 1000;
const DAY_MS = 24 * 60 * 60 * 1000;

const pad = (n: number) => String(n).padStart(2, "0");

/** UTC instant for `date` (YYYY-MM-DD) + `minutes` after local midnight (may exceed 1440). */
export function bishkekToUtc(date: string, minutes: number): Date {
  const [y, m, d] = date.split("-").map(Number);
  return new Date(Date.UTC(y, m - 1, d, 0, 0) + minutes * 60_000 - OFFSET_MS);
}

/** Local calendar date and minutes-since-midnight for an instant. */
export function bishkekParts(instant: Date): { date: string; minutes: number } {
  const local = new Date(instant.getTime() + OFFSET_MS);
  return {
    date: `${local.getUTCFullYear()}-${pad(local.getUTCMonth() + 1)}-${pad(local.getUTCDate())}`,
    minutes: local.getUTCHours() * 60 + local.getUTCMinutes(),
  };
}

export function addDays(date: string, days: number): string {
  const t = bishkekToUtc(date, 12 * 60).getTime() + days * DAY_MS;
  return bishkekParts(new Date(t)).date;
}

/** Local (Asia/Bishkek) calendar date for an instant. */
export function businessDate(instant: Date = new Date()): string {
  return bishkekParts(instant).date;
}

/** Minutes since the date's local midnight (e.g. 01:00 next day → 1500, 23:00 day before → -60). */
export function businessMinutes(instant: Date, date: string): number {
  return Math.round((instant.getTime() - bishkekToUtc(date, 0).getTime()) / 60_000);
}

/** Everything that can affect slots of `date`: from its midnight to the latest possible end. */
export function dayWindow(date: string): { from: Date; to: Date } {
  return { from: bishkekToUtc(date, 0), to: bishkekToUtc(date, MAX_END_MIN) };
}

/** The calendar day itself: [00:00, 24:00). */
export function calendarDay(date: string): { from: Date; to: Date } {
  return { from: bishkekToUtc(date, 0), to: bishkekToUtc(date, 24 * 60) };
}

/** "HH:mm" for minutes (wraps past midnight). */
export function minutesLabel(minutes: number): string {
  const m = ((minutes % 1440) + 1440) % 1440;
  return `${pad(Math.floor(m / 60))}:${pad(m % 60)}`;
}

export function formatTime(instant: Date | string): string {
  return minutesLabel(bishkekParts(new Date(instant)).minutes);
}

export function formatDate(instant: Date | string): string {
  const [y, m, d] = bishkekParts(new Date(instant)).date.split("-");
  return `${d}.${m}.${y}`;
}

export function formatDateStr(date: string): string {
  const [y, m, d] = date.split("-");
  return `${d}.${m}.${y}`;
}

const WEEKDAYS = ["вс", "пн", "вт", "ср", "чт", "пт", "сб"];
const MONTHS = ["янв", "фев", "мар", "апр", "мая", "июн", "июл", "авг", "сен", "окт", "ноя", "дек"];

export function formatDateHuman(date: string): string {
  const [y, m, d] = date.split("-").map(Number);
  const wd = new Date(Date.UTC(y, m - 1, d)).getUTCDay();
  return `${d} ${MONTHS[m - 1]}, ${WEEKDAYS[wd]}`;
}

/** "1 ч 25 мин" style remaining duration. */
export function formatDuration(ms: number): string {
  const total = Math.max(0, Math.round(ms / 60_000));
  const h = Math.floor(total / 60);
  const m = total % 60;
  if (h && m) return `${h} ч ${m} мин`;
  if (h) return `${h} ч`;
  return `${m} мин`;
}

export function isValidDateStr(s: string): boolean {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(s)) return false;
  const [y, m, d] = s.split("-").map(Number);
  const dt = new Date(Date.UTC(y, m - 1, d));
  return dt.getUTCFullYear() === y && dt.getUTCMonth() === m - 1 && dt.getUTCDate() === d;
}
