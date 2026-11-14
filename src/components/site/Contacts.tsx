import { CLUB } from "@/lib/config";
import { waLink } from "@/lib/whatsapp";
import { ClockIcon, PhoneIcon, PinIcon, WhatsAppIcon } from "../ui/icons";

export function Contacts({ whatsapp }: { whatsapp: string }) {
  const items = [
    { icon: PinIcon, title: "Адрес", value: CLUB.address, href: CLUB.mapUrl, cta: "Открыть в 2ГИС" },
    { icon: ClockIcon, title: "Часы работы", value: CLUB.hoursLabel, sub: "Без выходных и перерывов" },
    { icon: PhoneIcon, title: "Телефон", value: CLUB.phoneDisplay, href: CLUB.phoneHref, cta: "Позвонить" },
    { icon: WhatsAppIcon, title: "WhatsApp", value: `+${whatsapp}`, href: waLink(whatsapp), cta: "Написать" },
  ];
  return (
    <ul className="grid gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4">
      {items.map(({ icon: Icon, ...it }) => (
        <li key={it.title} className="card flex flex-col p-6">
          <span className="mb-5 grid h-11 w-11 place-items-center rounded-xl bg-red-600/15 text-red-500">
            <Icon />
          </span>
          <h3 className="text-sm font-medium text-mute">{it.title}</h3>
          <p className="mt-1 text-lg leading-snug font-bold">{it.value}</p>
          {it.sub && <p className="mt-1 text-sm text-neutral-400">{it.sub}</p>}
          {it.href && (
            <a
              href={it.href}
              {...(it.href.startsWith("http") ? { target: "_blank", rel: "noopener noreferrer" } : {})}
              className="mt-auto pt-4 text-sm font-semibold text-red-400 hover:text-red-300"
            >
              {it.cta} →
            </a>
          )}
        </li>
      ))}
    </ul>
  );
}
