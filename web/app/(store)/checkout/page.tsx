import Link from "next/link";
import { cookies } from "next/headers";
import {
  COOKIE_CART,
  cartItemCount,
  parseCartJson,
} from "@/lib/cart-cookies";

export default async function CheckoutPage() {
  const cookieStore = await cookies();
  const cart = parseCartJson(cookieStore.get(COOKIE_CART)?.value);
  const count = cartItemCount(cart);

  if (count === 0) {
    return (
      <main className="container py-5">
        <h1 className="h4 fw-semibold mb-2">Checkout</h1>
        <p className="text-muted mb-4">Your cart is empty.</p>
        <Link href="/cart" className="fw-semibold text-decoration-none">
          ← Back to cart
        </Link>
      </main>
    );
  }

  return (
    <main className="container py-5">
      <h1 className="h4 fw-semibold mb-2">Checkout</h1>
      <p className="text-muted mb-4">
        Order placement is being connected to the kitchen workflow. For now, review your items in{" "}
        <Link href="/cart">your cart</Link> or contact support to complete your order.
      </p>
      <Link href="/cart" className="btn btn-outline-dark rounded-pill">
        ← Back to cart
      </Link>
    </main>
  );
}
