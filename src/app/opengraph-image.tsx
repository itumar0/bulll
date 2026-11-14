import { ImageResponse } from "next/og";

export const alt = "RED SHOT — Billiard Club, Bishkek";
export const size = { width: 1200, height: 630 };
export const contentType = "image/png";

// Latin-only text: the built-in OG font has no Cyrillic glyphs.
export default function OgImage() {
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
          background: "radial-gradient(circle at 80% 30%, #3a0509 0%, #0A0A0A 55%)",
          color: "#fff",
        }}
      >
        <div style={{ display: "flex", alignItems: "center", gap: 24 }}>
          <div
            style={{
              width: 96,
              height: 96,
              borderRadius: 48,
              background: "#C1121F",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              boxShadow: "0 0 80px #E63946",
            }}
          >
            <div
              style={{
                width: 46,
                height: 46,
                borderRadius: 23,
                background: "#fff",
                color: "#0A0A0A",
                fontSize: 30,
                fontWeight: 800,
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
              }}
            >
              8
            </div>
          </div>
          <div style={{ fontSize: 40, letterSpacing: 8, color: "#E63946", fontWeight: 700 }}>BILLIARD CLUB</div>
        </div>
        <div style={{ fontSize: 150, fontWeight: 800, letterSpacing: -4, marginTop: 30 }}>RED SHOT</div>
        <div style={{ fontSize: 40, color: "#a3a3a3", marginTop: 10 }}>6 tables · 2 VIP rooms · Bishkek · 12:00 – 02:00</div>
      </div>
    ),
    size,
  );
}
