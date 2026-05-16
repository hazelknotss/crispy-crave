"use client";

import { useRef } from "react";
import { riderUpdateDelivery } from "@/app/rider/actions";

type Props = {
  orderId: number;
  redirectBack: string;
  completeUrl: string;
};

export function RiderStatusSelect({ orderId, redirectBack, completeUrl }: Props) {
  const formRef = useRef<HTMLFormElement>(null);

  return (
    <form ref={formRef} action={riderUpdateDelivery} className="rider-delivery-actions__select-form">
      <input type="hidden" name="order_id" value={orderId} />
      <input type="hidden" name="redirect" value={redirectBack} />
      <label className="visually-hidden" htmlFor={`status-select-${orderId}`}>
        Set delivery status
      </label>
      <select
        id={`status-select-${orderId}`}
        name="delivery_status"
        className="form-select form-select-sm rider-status-select"
        defaultValue=""
        onChange={(e) => {
          const v = e.target.value;
          if (v === "delivered") {
            window.location.href = completeUrl;
            return;
          }
          if (v) formRef.current?.requestSubmit();
        }}
      >
        <option value="" disabled>
          Other status…
        </option>
        <option value="assigned">Assigned</option>
        <option value="picked_up">Picked up</option>
        <option value="on_the_way">On the way</option>
        <option value="delivered">Delivered</option>
      </select>
    </form>
  );
}
