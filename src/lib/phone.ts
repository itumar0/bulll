/** Kyrgyzstan phone helpers: +996 followed by 9 digits. */

export const PHONE_RE = /^\+996\d{9}$/;

/** Extract the 9 local digits from any user input (masked, pasted, with or without the country code). */
export function phoneDigits(input: string): string {
  let s = input;
  const idx = s.indexOf("+996");
  if (idx !== -1) s = s.slice(0, idx) + s.slice(idx + 4); // drop the mask prefix wherever the caret was
  let digits = s.replace(/\D/g, "");
  if (idx === -1) {
    if (digits.length > 9 && digits.startsWith("996")) digits = digits.slice(3);
    else if (digits.length > 9 && digits.startsWith("0")) digits = digits.slice(1);
  }
  return digits.slice(0, 9);
}

/** Keep the caret after the fixed "+996" prefix. */
export function caretToEnd(e: { currentTarget: HTMLInputElement }) {
  const el = e.currentTarget;
  requestAnimationFrame(() => el.setSelectionRange(el.value.length, el.value.length));
}

/** Display mask: +996 (XXX) XXX-XXX */
export function formatPhoneMask(input: string): string {
  const d = phoneDigits(input);
  let out = "+996";
  if (d.length > 0) out += ` (${d.slice(0, 3)}`;
  if (d.length >= 3) out += ")";
  if (d.length > 3) out += ` ${d.slice(3, 6)}`;
  if (d.length > 6) out += `-${d.slice(6, 9)}`;
  return out;
}

export function normalizePhone(input: string): string | null {
  const d = phoneDigits(input);
  return d.length === 9 ? `+996${d}` : null;
}

export function prettyPhone(normalized: string): string {
  return formatPhoneMask(normalized);
}
