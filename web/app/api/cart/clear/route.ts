import { NextResponse } from "next/server";
import {
  COOKIE_CART,
  COOKIE_CART_SHOP,
  COOKIE_CHECKOUT_PREFILL,
} from "@/lib/cart-cookies";

export async function GET(request: Request) {
  const res = NextResponse.redirect(new URL("/cart", request.url));
  res.cookies.delete(COOKIE_CART);
  res.cookies.delete(COOKIE_CART_SHOP);
  res.cookies.delete(COOKIE_CHECKOUT_PREFILL);
  return res;
}
