import type { MetadataRoute } from "next";

export default function sitemap(): MetadataRoute.Sitemap {
  const site = process.env.SITE_URL || "http://localhost:3000";
  return [{ url: `${site}/`, changeFrequency: "weekly", priority: 1 }];
}
