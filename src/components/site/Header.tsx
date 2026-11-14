import Image from "next/image";
import Link from "next/link";
import { CLUB } from "@/lib/config";
import { buttonClass } from "../ui/Button";
import { InstagramIcon } from "../ui/icons";

const nav = [
  { href: "#tables", label: "Столы" },
  { href: "#prices", label: "Цены" },
  { href: "#booking", label: "Бронь" },
  { href: "#contact", label: "Связь" },
  { href: "#contacts", label: "Контакты" },
];

export function Logo() {
  return (
    <Link href="/" className="group flex items-center gap-2.5">
      <Image
        src="/logo-96.webp"
        alt=""
        width={40}
        height={40}
        priority
        className="h-10 w-10 rounded-full shadow-glow-sm ring-1 ring-white/10 transition-transform group-hover:rotate-6"
      />
      <span className="text-lg font-extrabold tracking-tight">
        Бильярд <span className="text-accent-500">Старт</span>
        <span className="sr-only"> — на главную</span>
      </span>
    </Link>
  );
}

export function Header() {
  return (
    <header className="fixed inset-x-0 top-0 z-50 border-b border-white/[0.06] bg-ink-950/75 backdrop-blur-xl">
      <div className="container-x flex h-16 items-center justify-between">
        <Logo />
        <nav aria-label="Основная навигация" className="hidden md:block">
          <ul className="flex items-center gap-1">
            {nav.map((n) => (
              <li key={n.href}>
                <a href={n.href} className="rounded-lg px-3 py-2 text-sm font-medium text-mute transition-colors hover:text-white">
                  {n.label}
                </a>
              </li>
            ))}
          </ul>
        </nav>
        <div className="flex items-center gap-1">
          <a
            href={CLUB.instagram}
            target="_blank"
            rel="noopener noreferrer"
            aria-label="Instagram клуба"
            className="hidden rounded-lg p-2 text-mute transition-colors hover:text-white sm:inline-flex"
          >
            <InstagramIcon />
          </a>
          <a href="#booking" className={buttonClass("primary", "sm")}>
            Забронировать
          </a>
        </div>
      </div>
    </header>
  );
}
