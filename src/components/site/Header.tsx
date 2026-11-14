import Link from "next/link";
import { buttonClass } from "../ui/Button";

const nav = [
  { href: "#tables", label: "Столы" },
  { href: "#booking", label: "Бронь" },
  { href: "#contact", label: "Связь" },
  { href: "#contacts", label: "Контакты" },
];

export function Logo() {
  return (
    <Link href="/" className="group flex items-center gap-2.5">
      <span aria-hidden className="relative grid h-9 w-9 place-items-center rounded-full bg-red-600 shadow-glow-sm transition-transform group-hover:rotate-12">
        <span className="grid h-4 w-4 place-items-center rounded-full bg-white text-[9px] font-black text-ink-950">8</span>
      </span>
      <span className="text-lg font-extrabold tracking-tight">
        Бильярд <span className="text-red-500">Старт</span>
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
        <a href="#booking" className={buttonClass("primary", "sm")}>
          Забронировать
        </a>
      </div>
    </header>
  );
}
