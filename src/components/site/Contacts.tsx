import { CLUB } from "@/lib/config";
import { waLink } from "@/lib/whatsapp";
import { ClockIcon, InstagramIcon, MapIcon, PhoneIcon, PinIcon, WhatsAppIcon } from "../ui/icons";

export function Contacts({ whatsapp }: { whatsapp: string }) {
  const items = [
    { icon: PinIcon, title: "Адрес", value: CLUB.address, href: CLUB.mapUrl, cta: "Открыть в 2ГИС" },
    { icon: ClockIcon, title: "Часы работы", value: CLUB.hoursLabel, sub: "Без выходных и перерывов" },
    { icon: PhoneIcon, title: "Телефон", value: CLUB.phoneDisplay, href: CLUB.phoneHref, cta: "Позвонить" },
    { icon: WhatsAppIcon, title: "WhatsApp", value: `+${whatsapp}`, href: waLink(whatsapp), cta: "Написать" },
    { icon: InstagramIcon, title: "Instagram", value: CLUB.instagramHandle, sub: "Фото зала, турниры и акции", href: CLUB.instagram, cta: "Подписаться" },
    { icon: MapIcon, title: "2ГИС", value: "Маршрут до клуба", sub: "Отзывы и как добраться", href: CLUB.mapUrl, cta: "Построить маршрут" },
  ];
  return (
    <ul className="grid gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3">
      {items.map(({ icon: Icon, ...it }) => (
        <li key={it.title} className="card flex gap-4 p-5 transition-colors hover:border-accent-500/30 sm:flex-col sm:gap-0 sm:p-6">
          <span className="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-accent-600/15 text-accent-500 sm:mb-5">
            <Icon />
          </span>
          <div className="flex min-w-0 flex-1 flex-col">
          <h3 className="text-sm font-medium text-mute">{it.title}</h3>
          <p className="mt-1 text-lg leading-snug font-bold break-words">{it.value}</p>
          {it.sub && <p className="mt-1 text-sm text-neutral-400">{it.sub}</p>}
          {it.href && (
            <a
              href={it.href}
              {...(it.href.startsWith("http") ? { target: "_blank", rel: "noopener noreferrer" } : {})}
              className="mt-auto pt-3 text-sm font-semibold text-accent-400 hover:text-accent-300 sm:pt-4"
            >
              {it.cta} →
            </a>
          )}
          </div>
        </li>
      ))}
    </ul>
  );
}
