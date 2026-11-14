"use client";

import { useEffect, useState } from "react";
import { waLink } from "@/lib/whatsapp";
import { WhatsAppIcon } from "../ui/icons";

/** Sticky bottom bar on phones: appears after the hero, hides near the booking form. */
export function MobileCta({ whatsapp }: { whatsapp: string }) {
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const booking = document.getElementById("booking");
    let inBooking = false;
    const io = booking
      ? new IntersectionObserver(([e]) => {
          inBooking = e.isIntersecting;
          update();
        }, { threshold: 0.05 })
      : null;
    function update() {
      setVisible(window.scrollY > window.innerHeight * 0.8 && !inBooking);
    }
    if (booking && io) io.observe(booking);
    window.addEventListener("scroll", update, { passive: true });
    update();
    return () => {
      window.removeEventListener("scroll", update);
      io?.disconnect();
    };
  }, []);

  return (
    <div
      className={`fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-ink-950/90 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur-xl transition-transform duration-300 md:hidden ${
        visible ? "translate-y-0" : "translate-y-full"
      }`}
      aria-hidden={!visible}
    >
      <div className="flex gap-2">
        <a
          href="#booking"
          tabIndex={visible ? 0 : -1}
          className="flex h-12 flex-1 items-center justify-center rounded-xl bg-accent-600 font-semibold shadow-glow active:scale-[0.98]"
        >
          Забронировать стол
        </a>
        <a
          href={waLink(whatsapp, "Здравствуйте! Хочу забронировать стол 🎱")}
          target="_blank"
          rel="noopener noreferrer"
          tabIndex={visible ? 0 : -1}
          aria-label="Написать в WhatsApp"
          className="grid h-12 w-12 place-items-center rounded-xl bg-[#25D366] text-ink-950 active:scale-[0.98]"
        >
          <WhatsAppIcon />
        </a>
      </div>
    </div>
  );
}
