import type { MetadataRoute } from "next";
import { siteUrl } from "@/lib/site-url";

export default function sitemap(): MetadataRoute.Sitemap {
  const site = siteUrl();
  return [{ url: `${site}/`, changeFrequency: "weekly", priority: 1 }];
}
