import { Suspense } from "react";
import { RiderLoginForm } from "@/components/rider/rider-login-form";

export default function RiderLoginPage() {
  return (
    <Suspense fallback={<p className="p-4 text-center text-muted">Loading…</p>}>
      <RiderLoginForm />
    </Suspense>
  );
}
