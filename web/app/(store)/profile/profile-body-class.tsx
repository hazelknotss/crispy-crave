"use client";

import { useEffect } from "react";

export default function ProfileBodyClassLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  useEffect(() => {
    document.body.classList.add("profile-layout");
    return () => document.body.classList.remove("profile-layout");
  }, []);
  return <>{children}</>;
}
