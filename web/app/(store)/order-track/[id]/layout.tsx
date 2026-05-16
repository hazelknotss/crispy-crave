"use client";

import { useEffect } from "react";

export default function OrderTrackLayout({ children }: { children: React.ReactNode }) {
  useEffect(() => {
    document.body.classList.add("order-track-layout");
    return () => document.body.classList.remove("order-track-layout");
  }, []);
  return <>{children}</>;
}
