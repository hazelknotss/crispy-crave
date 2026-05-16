import { NextResponse } from "next/server";
import { cookies } from "next/headers";
import {
  COOKIE_CART,
  COOKIE_CART_SHOP,
  COOKIE_CHECKOUT_PREFILL,
  parseCartJson,
} from "@/lib/cart-cookies";

const cookieOpts = {
  path: "/",
  maxAge: 60 * 60 * 24 * 30,
  sameSite: "lax" as const,
  httpOnly: true,
};

export async function GET(request: Request) {
  const idRaw = new URL(request.url).searchParams.get("id");
  const cookieStore = await cookies();
  const cart = parseCartJson(cookieStore.get(COOKIE_CART)?.value);

  if (!idRaw) {
    return NextResponse.redirect(new URL("/cart", request.url));
  }

  const menuId = parseInt(idRaw, 10);
  if (!Number.isFinite(menuId) || menuId < 1) {
    return NextResponse.redirect(new URL("/cart", request.url));
  }

  const keyStr = String(menuId);
  if (!cart[keyStr]) {
    return NextResponse.redirect(new URL("/cart", request.url));
  }

  delete cart[keyStr];

  const res = NextResponse.redirect(new URL("/cart", request.url));
  res.cookies.set(COOKIE_CART, JSON.stringify(cart), cookieOpts);
  if (Object.keys(cart).length === 0) {
    res.cookies.delete(COOKIE_CART_SHOP);
    res.cookies.delete(COOKIE_CHECKOUT_PREFILL);
  }
  return res;
}
