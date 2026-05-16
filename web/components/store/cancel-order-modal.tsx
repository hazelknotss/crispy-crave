"use client";

import { useEffect, useState } from "react";
import { cancelCustomerOrder } from "@/app/(store)/my-orders/actions";
import { CANCEL_REASONS } from "@/lib/customer-orders";

export function CancelOrderModal() {
  const [orderId, setOrderId] = useState<number | null>(null);
  const [redirect, setRedirect] = useState("/my-orders");
  const [reason, setReason] = useState("changed_mind");

  useEffect(() => {
    function onClick(e: MouseEvent) {
      const btn = (e.target as HTMLElement).closest("[data-kk-cancel-order]");
      if (!btn) return;
      const id = parseInt(btn.getAttribute("data-kk-cancel-order") ?? "", 10);
      const redir = btn.getAttribute("data-kk-cancel-redirect") ?? "/my-orders";
      if (!Number.isFinite(id)) return;
      setOrderId(id);
      setRedirect(redir);
      setReason("changed_mind");
      const el = document.getElementById("kkCancelOrderModal");
      const B = (window as unknown as {
        bootstrap?: { Modal?: { getOrCreateInstance: (el: Element) => { show: () => void } } };
      }).bootstrap;
      if (el && B?.Modal) {
        B.Modal.getOrCreateInstance(el).show();
      }
    }
    document.addEventListener("click", onClick);
    return () => document.removeEventListener("click", onClick);
  }, []);

  return (
    <div className="modal fade" id="kkCancelOrderModal" tabIndex={-1} aria-hidden="true">
      <div className="modal-dialog modal-dialog-centered">
        <div className="modal-content kk-cancel-modal">
          <form
            action={cancelCustomerOrder}
            onSubmit={(e) => {
              if (!confirm("Are you sure you want to cancel this order?")) {
                e.preventDefault();
              }
            }}
          >
            <div className="modal-header border-0 pb-0">
              <h2 className="modal-title h5 fw-bold">Cancel order?</h2>
              <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close" />
            </div>
            <div className="modal-body">
              <p className="kk-cancel-modal__lede small text-muted mb-3">
                Tell us why you want to cancel{" "}
                <strong>{orderId ? `order #${orderId}` : "this order"}</strong>.
              </p>
              <input type="hidden" name="order_id" value={orderId ?? ""} readOnly />
              <input type="hidden" name="redirect" value={redirect} readOnly />
              <fieldset className="kk-cancel-modal__reasons mb-3">
                <legend className="visually-hidden">Cancellation reason</legend>
                {Object.entries(CANCEL_REASONS).map(([key, label]) => (
                  <label key={key} className="kk-cancel-modal__reason">
                    <input
                      type="radio"
                      name="cancel_reason"
                      value={key}
                      required
                      checked={reason === key}
                      onChange={() => setReason(key)}
                    />
                    <span>{label}</span>
                  </label>
                ))}
              </fieldset>
              {reason === "other" ? (
                <div>
                  <label htmlFor="kkCancelNote" className="form-label small fw-semibold">
                    Please tell us more
                  </label>
                  <textarea
                    id="kkCancelNote"
                    name="cancel_note"
                    className="form-control form-control-sm"
                    rows={3}
                    maxLength={400}
                    required
                  />
                </div>
              ) : null}
            </div>
            <div className="modal-footer border-0 pt-0 flex-nowrap gap-2">
              <button type="button" className="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">
                Keep order
              </button>
              <button type="submit" className="btn btn-danger flex-fill" disabled={!orderId}>
                Cancel order
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}