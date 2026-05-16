import { Suspense } from "react";
import { RiderApplyForm } from "@/components/rider/rider-apply-form";

export default function RiderApplyPage() {
  return (
    <Suspense
      fallback={
        <div className="rider-login-shell rider-login-shell--apply p-4 text-center text-muted">
          Loading…
        </div>
      }
    >
      <RiderApplyForm />
    </Suspense>
  );
}
