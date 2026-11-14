import { BookingForm } from "@/components/site/BookingForm";
import { Contacts } from "@/components/site/Contacts";
import { Footer } from "@/components/site/Footer";
import { Header } from "@/components/site/Header";
import { Hero } from "@/components/site/Hero";
import { QuickContact } from "@/components/site/QuickContact";
import { TablesSection } from "@/components/site/TablesSection";
import { Reveal } from "@/components/ui/Reveal";
import { SectionHeading } from "@/components/ui/SectionHeading";
import { getTableStatuses, toPublicStatus } from "@/lib/booking-service";
import { whatsappNumber } from "@/lib/env";
import { businessDate } from "@/lib/time";

export const dynamic = "force-dynamic";

async function initialStatuses() {
  try {
    const tables = await getTableStatuses();
    return { ok: true, now: new Date().toISOString(), tables: tables.map(toPublicStatus) };
  } catch {
    return null; // DB unavailable → client polling will retry
  }
}

export default async function HomePage() {
  const whatsapp = whatsappNumber();
  const initial = await initialStatuses();
  const today = businessDate();

  return (
    <>
      <a href="#main" className="sr-only z-[60] rounded-lg bg-red-600 px-4 py-2 focus:not-sr-only focus:fixed focus:top-3 focus:left-3">
        Перейти к содержимому
      </a>
      <Header />
      <main id="main">
        <Hero whatsapp={whatsapp} />

        <section id="tables" aria-labelledby="tables-title" className="py-20 sm:py-28">
          <div className="container-x">
            <Reveal>
              <SectionHeading
                id="tables-title"
                eyebrow="Наши столы"
                title="6 столов и 2 VIP-комнаты"
                text="Профессиональное сукно, выверенные борта и свет без бликов. Статус обновляется в реальном времени — нажмите на стол, чтобы забронировать."
              />
            </Reveal>
            <TablesSection initial={initial} />
          </div>
        </section>

        <section id="booking" aria-labelledby="booking-title" className="relative py-20 sm:py-28">
          <div aria-hidden className="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/10 to-transparent" />
          <div className="container-x">
            <Reveal>
              <SectionHeading
                id="booking-title"
                eyebrow="Онлайн-бронирование"
                title="Забронируйте стол за минуту"
                text="Выберите стол, дату и время — занятые слоты недоступны. После брони откроется WhatsApp с готовым сообщением."
              />
            </Reveal>
            <BookingForm today={today} />
          </div>
        </section>

        <section id="contact" aria-labelledby="contact-title" className="py-20 sm:py-28">
          <div className="container-x grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:gap-16">
            <Reveal>
              <SectionHeading
                id="contact-title"
                eyebrow="Быстрая связь"
                title="Есть вопрос? Напишите нам"
                text="Ответим в WhatsApp в течение нескольких минут: цены, турниры, корпоративы и дни рождения."
              />
            </Reveal>
            <QuickContact whatsapp={whatsapp} />
          </div>
        </section>

        <section id="contacts" aria-labelledby="contacts-title" className="pt-10 pb-20 sm:pb-28">
          <div className="container-x">
            <Reveal>
              <SectionHeading id="contacts-title" eyebrow="Контакты" title="Ждём вас в клубе" />
            </Reveal>
            <Contacts whatsapp={whatsapp} />
          </div>
        </section>
      </main>
      <Footer />
    </>
  );
}
