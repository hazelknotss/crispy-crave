"use client";

import { useEffect } from "react";

export default function MyOrdersBodyClass({ children }: { children: React.ReactNode }) {
  useEffect(() => {
    document.body.classList.add("my-orders-layout");
    return () => document.body.classList.remove("my-orders-layout");
  }, []);
  return <>{children}</>;
}
