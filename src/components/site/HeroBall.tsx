"use client";

import Image from "next/image";
import { LazyMotion, domAnimation, m, useReducedMotion } from "framer-motion";

/** Club logo with a soft green glow, gently floating. Decorative (the name is in the H1). */
export function HeroBall() {
  const reduce = useReducedMotion();
  return (
    <LazyMotion features={domAnimation} strict>
      <div aria-hidden className="relative mx-auto hidden aspect-square w-full max-w-sm lg:block">
        <div className="absolute inset-6 rounded-full bg-accent-600/35 blur-3xl" />
        <m.div
          className="relative h-full w-full"
          initial={reduce ? false : { opacity: 0, scale: 0.9, rotate: -8 }}
          animate={{ opacity: 1, scale: 1, rotate: 0, y: reduce ? 0 : [0, -14, 0] }}
          transition={{
            opacity: { duration: 0.8 },
            scale: { duration: 0.8, ease: [0.22, 1, 0.36, 1] },
            rotate: { duration: 0.8 },
            y: { duration: 6, repeat: Infinity, ease: "easeInOut" },
          }}
        >
          <Image
            src="/logo-512.webp"
            alt=""
            width={512}
            height={512}
            sizes="384px"
            className="h-full w-full rounded-full shadow-[0_0_0_1px_rgb(34_197_94/0.35),0_30px_80px_-20px_rgb(21_128_61/0.7)]"
          />
        </m.div>
      </div>
    </LazyMotion>
  );
}
