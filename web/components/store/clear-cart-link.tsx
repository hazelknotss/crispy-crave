"use client";

export function ClearCartLink() {
  return (
    <a
      href="/api/cart/clear"
      className="btn btn-sm btn-outline-danger rounded-pill"
      onClick={(e) => {
        if (!confirm("Clear your cart?")) e.preventDefault();
      }}
    >
      <i className="bi bi-trash3 me-1" aria-hidden="true" />
      Clear cart
    </a>
  );
}
