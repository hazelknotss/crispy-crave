"use client";

import { adminUpdateOrderStatus } from "@/app/admin/actions";

const STATUSES = ["pending", "preparing", "delivering", "completed", "cancelled"] as const;

export function OrderStatusForm({
  orderId,
  currentStatus,
}: {
  orderId: number;
  currentStatus: string;
}) {
  const safe =
    STATUSES.includes(currentStatus as (typeof STATUSES)[number]) ? currentStatus : "pending";

  return (
    <form action={adminUpdateOrderStatus} className="mt-3 d-flex flex-wrap align-items-center gap-2">
      <input type="hidden" name="order_id" value={orderId} />
      <label className="small text-muted mb-0" htmlFor={`ord-st-${orderId}`}>
        Update status
      </label>
      <select
        id={`ord-st-${orderId}`}
        name="order_status"
        className="form-select form-select-sm"
        style={{ maxWidth: "12rem" }}
        defaultValue={safe}
        onChange={(e) => e.currentTarget.form?.requestSubmit()}
      >
        {STATUSES.map((s) => (
          <option key={s} value={s}>
            {s}
          </option>
        ))}
      </select>
    </form>
  );
}
