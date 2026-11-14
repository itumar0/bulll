import { Reveal } from "../ui/Reveal";
import { BoltIcon, ClockIcon, CrownIcon, TargetIcon } from "../ui/icons";

const items = [
  {
    icon: TargetIcon,
    title: "Профессиональные столы",
    text: "6 столов для игры с друзьями — свободные места видно на сайте в реальном времени.",
  },
  {
    icon: CrownIcon,
    title: "VIP-комнаты",
    text: "2 отдельные комнаты до 10 гостей — для компании, дня рождения или встречи с коллегами.",
  },
  {
    icon: BoltIcon,
    title: "Бронь за минуту",
    text: "Выберите стол и время онлайн — занятые слоты сразу видно, подтверждение приходит в WhatsApp.",
  },
  {
    icon: ClockIcon,
    title: "Работаем 24/7",
    text: "Круглосуточно и без выходных — приходите днём, вечером или ночью.",
  },
];

export function Features() {
  return (
    <ul className="grid gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4">
      {items.map(({ icon: Icon, title, text }, i) => (
        <li key={title}>
          <Reveal delay={i * 0.06} className="card flex h-full gap-4 p-5 transition-colors hover:border-accent-500/30 sm:block sm:p-6">
            <span className="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-accent-600/15 text-accent-500 shadow-glow-sm sm:mb-5">
              <Icon />
            </span>
            <div>
              <h3 className="text-lg font-bold">{title}</h3>
              <p className="mt-1.5 text-sm leading-relaxed text-mute sm:mt-2">{text}</p>
            </div>
          </Reveal>
        </li>
      ))}
    </ul>
  );
}
