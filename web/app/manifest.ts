import type { MetadataRoute } from "next";
import { BRAND_LOGO_SRC } from "@/lib/brand";

export default function manifest(): MetadataRoute.Manifest {
  return {
    name: "Crispy Crave",
    short_name: "Crispy Crave",
    description:
      "Order meals, snacks, and drinks from Pototan restaurants — delivery or pickup.",
    start_url: "/",
    scope: "/",
    display: "standalone",
    orientation: "portrait-primary",
    background_color: "#ffffff",
    theme_color: "#111111",
    lang: "en",
    categories: ["food", "shopping"],
    icons: [
      {
        src: BRAND_LOGO_SRC,
        sizes: "192x192",
        type: "image/png",
        purpose: "any",
      },
      {
        src: BRAND_LOGO_SRC,
        sizes: "512x512",
        type: "image/png",
        purpose: "any",
      },
      {
        src: BRAND_LOGO_SRC,
        sizes: "512x512",
        type: "image/png",
        purpose: "maskable",
      },
    ],
  };
}