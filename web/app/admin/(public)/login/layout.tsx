import type { Metadata } from "next";
import { StaffLoginBodyClass } from "@/components/admin/staff-login-body-class";

export const metadata: Metadata = {
  title: "Sign in",
};

export default function AdminLoginLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <>
      <StaffLoginBodyClass />
      {children}
    </>
  );
}
