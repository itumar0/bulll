import { CLUB } from "@/lib/config";
import { buttonClass } from "../ui/Button";
import { CheckIcon, CrownIcon } from "../ui/icons";

const plans = [
  {
    name: "Стол",
    price: CLUB.priceRegular,
    note: "6 столов · до 6 гостей",
    features: ["Профессиональный стол", "До 6 гостей за столом", "Бронь от 1 до 5 часов"],
    vip: false,
  },
  {
    name: "VIP-комната",
    price: CLUB.priceVip,
    note: "2 комнаты · до 10 гостей",
    features: ["Отдельная комната для компании", "До 10 гостей", "Бронь от 1 до 5 часов"],
    vip: true,
  },
];

export function Pricing() {
  return (
    <div className="grid gap-4 md:grid-cols-2">
      {plans.map((p) => (
        <div
          key={p.name}
          className={`relative overflow-hidden rounded-3xl border p-7 sm:p-9 ${
            p.vip ? "border-accent-500/40 bg-gradient-to-br from-accent-950/60 via-ink-900 to-ink-900 shadow-glow" : "border-white/[0.07] bg-ink-900"
          }`}
        >
          {p.vip && (
            <>
              <div aria-hidden className="absolute -top-16 -right-16 h-48 w-48 rounded-full bg-accent-600/25 blur-3xl" />
              <span className="absolute top-6 right-6 inline-flex items-center gap-1.5 rounded-full bg-accent-600 px-3 py-1 text-xs font-bold">
                <CrownIcon className="h-3.5 w-3.5" /> Для компаний
              </span>
            </>
          )}
          <h3 className="text-xl font-bold">{p.name}</h3>
          <p className="mt-1 text-sm text-mute">{p.note}</p>
          <p className="mt-6 flex items-baseline gap-2">
            <span className="text-5xl font-extrabold tracking-tight tabular-nums">{p.price.toLocaleString("ru-RU")}</span>
            <span className="text-mute">сом / час</span>
          </p>
          <ul className="mt-6 space-y-3">
            {p.features.map((f) => (
              <li key={f} className="flex items-center gap-3 text-sm text-soft">
                <CheckIcon className="h-4 w-4 shrink-0 text-accent-400" /> {f}
              </li>
            ))}
          </ul>
          <a href="#booking" className={buttonClass(p.vip ? "primary" : "outline", "lg", "mt-8 w-full")}>
            Забронировать {p.vip ? "VIP" : "стол"}
          </a>
        </div>
      ))}
    </div>
  );
}
