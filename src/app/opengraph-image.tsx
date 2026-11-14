import { readFile } from "node:fs/promises";
import { join } from "node:path";
import { ImageResponse } from "next/og";

export const alt = "Бильярд Старт — бильярдный клуб в Караколе";
export const size = { width: 1200, height: 630 };
export const contentType = "image/png";

// Latin-only text: the built-in OG font has no Cyrillic glyphs.
export default async function OgImage() {
  const logo = await readFile(join(process.cwd(), "src/app/og-logo.png"));
  const logoSrc = `data:image/png;base64,${logo.toString("base64")}`;
  return new ImageResponse(
    (
      <div
        style={{
          width: "100%",
          height: "100%",
          display: "flex",
          flexDirection: "column",
          justifyContent: "center",
          padding: "80px",
          background: "radial-gradient(circle at 80% 30%, #06301a 0%, #0A0A0A 55%)",
          color: "#fff",
        }}
      >
        <div style={{ display: "flex", alignItems: "center", gap: 24 }}>
          {/* eslint-disable-next-line jsx-a11y/alt-text */}
          <img src={logoSrc} width={120} height={120} style={{ borderRadius: 60, boxShadow: "0 0 80px #22C55E" }} />
          <div style={{ fontSize: 40, letterSpacing: 8, color: "#22C55E", fontWeight: 700 }}>KARAKOL</div>
        </div>
        <div style={{ fontSize: 120, fontWeight: 800, letterSpacing: -3, marginTop: 30 }}>BILLIARD START</div>
        <div style={{ fontSize: 40, color: "#a3a3a3", marginTop: 10 }}>6 tables · 2 VIP rooms · Karakol · 24/7</div>
      </div>
    ),
    size,
  );
}
