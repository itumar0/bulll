"use client";

import { LazyMotion, domAnimation, m, useReducedMotion } from "framer-motion";

/** Decorative floating 8-ball. Hidden from assistive tech. */
export function HeroBall() {
  const reduce = useReducedMotion();
  return (
    <LazyMotion features={domAnimation} strict>
      <div aria-hidden className="relative mx-auto hidden aspect-square w-full max-w-sm lg:block">
        <div className="absolute inset-8 rounded-full bg-red-600/30 blur-3xl" />
        <m.div
          className="relative h-full w-full"
          initial={reduce ? false : { opacity: 0, scale: 0.9, rotate: -20 }}
          animate={{ opacity: 1, scale: 1, rotate: 0, y: reduce ? 0 : [0, -14, 0] }}
          transition={{
            opacity: { duration: 0.8 },
            scale: { duration: 0.8, ease: [0.22, 1, 0.36, 1] },
            rotate: { duration: 0.8 },
            y: { duration: 6, repeat: Infinity, ease: "easeInOut" },
          }}
        >
          <svg viewBox="0 0 200 200" className="h-full w-full drop-shadow-[0_30px_60px_rgba(193,18,31,0.45)]">
            <defs>
              <radialGradient id="ball" cx="35%" cy="30%" r="75%">
                <stop offset="0%" stopColor="#3a3a3a" />
                <stop offset="45%" stopColor="#141414" />
                <stop offset="100%" stopColor="#000" />
              </radialGradient>
              <radialGradient id="rim" cx="50%" cy="50%" r="50%">
                <stop offset="80%" stopColor="transparent" />
                <stop offset="100%" stopColor="#E63946" stopOpacity="0.55" />
              </radialGradient>
            </defs>
            <circle cx="100" cy="100" r="96" fill="url(#ball)" />
            <circle cx="100" cy="100" r="96" fill="url(#rim)" />
            <circle cx="88" cy="84" r="36" fill="#fff" />
            <text x="88" y="98" textAnchor="middle" fontSize="40" fontWeight="800" fill="#0A0A0A" fontFamily="Manrope Variable, sans-serif">
              8
            </text>
            <ellipse cx="62" cy="48" rx="22" ry="11" fill="#fff" opacity="0.12" transform="rotate(-30 62 48)" />
          </svg>
        </m.div>
      </div>
    </LazyMotion>
  );
}
