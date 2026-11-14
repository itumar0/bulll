import { CLUB } from "@/lib/config";
import { waLink } from "@/lib/whatsapp";
import { ButtonLink } from "../ui/Button";
import { ArrowIcon, WhatsAppIcon } from "../ui/icons";
import { HeroBall } from "./HeroBall";

export function Hero({ whatsapp }: { whatsapp: string }) {
  const stats = [
    { value: "6", label: "профессиональных столов" },
    { value: "2", label: "VIP-комнаты" },
    { value: "24/7", label: "без выходных" },
  ];
  return (
    <section aria-labelledby="hero-title" className="relative isolate overflow-hidden pt-28 pb-20 sm:pt-36 sm:pb-28">
      <div aria-hidden className="pointer-events-none absolute inset-0 -z-10">
        <div className="absolute -top-40 right-[-20%] h-[38rem] w-[38rem] rounded-full bg-accent-600/25 blur-[120px]" />
        <div className="absolute bottom-[-30%] left-[-20%] h-[28rem] w-[28rem] rounded-full bg-accent-700/15 blur-[120px]" />
        <div className="grain absolute inset-0" />
        <div className="absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-accent-500/40 to-transparent" />
      </div>

      <div className="container-x grid items-center gap-12 lg:grid-cols-[1.2fr_0.8fr]">
        <div>
          <p className="mb-6 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.03] px-4 py-1.5 text-xs font-semibold text-soft">
            <span className="h-1.5 w-1.5 rounded-full bg-accent-500 shadow-glow-sm" aria-hidden />
            {CLUB.city} · {CLUB.hoursLabel}
          </p>
          <h1 id="hero-title" className="text-[3.4rem] leading-[1.02] font-extrabold tracking-[-0.04em] sm:text-8xl lg:text-[7.5rem]">
            Бильярд
            <br />
            <span className="bg-gradient-to-r from-accent-500 to-accent-600 bg-clip-text text-transparent drop-shadow-[0_0_30px_rgba(34,197,94,0.35)]">
              СТАРТ
            </span>
          </h1>
          <p className="mt-6 max-w-xl text-lg leading-relaxed text-soft sm:text-xl">{CLUB.slogan}</p>
          <p className="mt-2 max-w-xl text-base text-mute">
            Профессиональные столы, VIP-комнаты для компаний и бронь онлайн за минуту.
          </p>

          <div className="mt-9 flex flex-col gap-3 sm:flex-row">
            <ButtonLink href="#booking" size="lg">
              Забронировать <ArrowIcon />
            </ButtonLink>
            <ButtonLink
              href={waLink(whatsapp, "Здравствуйте! Хочу забронировать стол 🎱")}
              external
              variant="whatsapp"
              size="lg"
              aria-label="Написать в WhatsApp (откроется в новой вкладке)"
            >
              <WhatsAppIcon className="text-[#25D366]" /> Написать в WhatsApp
            </ButtonLink>
          </div>

          <dl className="mt-12 grid max-w-md grid-cols-3 gap-6 border-t border-white/[0.08] pt-8">
            {stats.map((s) => (
              <div key={s.label}>
                <dt className="sr-only">{s.label}</dt>
                <dd className="text-3xl font-extrabold tracking-tight">{s.value}</dd>
                <dd className="mt-1 text-xs leading-snug text-mute">{s.label}</dd>
              </div>
            ))}
          </dl>
        </div>

        <HeroBall />
      </div>
    </section>
  );
}
