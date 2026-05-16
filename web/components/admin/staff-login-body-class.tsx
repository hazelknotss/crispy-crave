"use client";

import { useEffect } from "react";

/** Matches PHP `admin/login.php` body classes for rider + admin login styling. */
export function StaffLoginBodyClass() {
  useEffect(() => {
    document.body.classList.add("rider-login-page", "admin-login-page");
    return () => {
      document.body.classList.remove("rider-login-page", "admin-login-page");
    };
  }, []);
  return null;
}
