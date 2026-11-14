import type { Metadata, Viewport } from "next";
import { headers } from "next/headers";
import "@fontsource-variable/manrope/index.css";
import "./globals.css";
import { CLUB } from "@/lib/config";

const siteUrl = process.env.SITE_URL || "http://localhost:3000";

export const metadata: Metadata = {
  metadataBase: new URL(siteUrl),
  title: {
    default: `${CLUB.name} — бильярдный клуб в Бишкеке | Онлайн-бронирование`,
    template: `%s | ${CLUB.name}`,
  },
  description: CLUB.description,
  keywords: ["бильярд Бишкек", "бильярдный клуб", "русский бильярд", "пул", "VIP комната", "бронирование стола"],
  applicationName: CLUB.name,
  alternates: { canonical: "/" },
  openGraph: {
    type: "website",
    locale: "ru_RU",
    url: "/",
    siteName: CLUB.name,
    title: `${CLUB.name} — бильярдный клуб в Бишкеке`,
    description: CLUB.description,
  },
  twitter: {
    card: "summary_large_image",
    title: `${CLUB.name} — бильярдный клуб в Бишкеке`,
    description: CLUB.description,
  },
  robots: { index: true, follow: true },
  formatDetection: { telephone: false },
};

export const viewport: Viewport = {
  themeColor: "#0A0A0A",
  width: "device-width",
  initialScale: 1,
  colorScheme: "dark",
};

export default async function RootLayout({ children }: { children: React.ReactNode }) {
  // Reading headers makes every page dynamic so Next can attach the per-request CSP nonce.
  const nonce = (await headers()).get("x-nonce") ?? undefined;
  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "SportsActivityLocation",
    name: CLUB.fullName,
    description: CLUB.description,
    url: siteUrl,
    telephone: "+996506056556",
    address: { "@type": "PostalAddress", streetAddress: "ул. Киевская, 95", addressLocality: "Бишкек", addressCountry: "KG" },
    openingHours: "Mo-Su 12:00-02:00",
  };

  return (
    <html lang="ru">
      <body className="min-h-dvh bg-ink-950 text-white">
        {children}
        <script
          type="application/ld+json"
          nonce={nonce}
          // Static, server-built object — no user input.
          dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd).replace(/</g, "\\u003c") }}
        />
      </body>
    </html>
  );
}
