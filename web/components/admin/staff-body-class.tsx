"use client";

import { useEffect } from "react";

export function StaffBodyClass() {
  useEffect(() => {
    document.body.classList.add("staff-portal");
    return () => document.body.classList.remove("staff-portal");
  }, []);
  return null;
}
