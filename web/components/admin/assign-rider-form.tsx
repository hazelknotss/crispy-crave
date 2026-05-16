"use client";

import { useRef } from "react";
import { adminAssignRider } from "@/app/admin/actions";

type RiderOpt = { id: string; display_name: string | null };

export function AssignRiderForm({
  orderId,
  riders,
  currentRiderId,
}: {
  orderId: number;
  riders: RiderOpt[];
  currentRiderId: string | null;
}) {
  const formRef = useRef<HTMLFormElement>(null);

  return (
    <form ref={formRef} action={adminAssignRider} className="d-inline-flex align-items-center gap-1">
      <input type="hidden" name="order_id" value={orderId} />
      <select
        name="rider_id"
        className="form-select form-select-sm"
        style={{ minWidth: "7rem" }}
        defaultValue={currentRiderId ?? ""}
        onChange={() => formRef.current?.requestSubmit()}
        aria-label="Assign rider"
      >
        <option value="">Rider…</option>
        {riders.map((r) => (
          <option key={r.id} value={r.id}>
            {r.display_name?.trim() || r.id.slice(0, 8)}
          </option>
        ))}
      </select>
    </form>
  );
}
