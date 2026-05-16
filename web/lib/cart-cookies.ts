export type CartLine = {
  menu_id: number;
  name: string;
  price: number;
  image: string;
  qty: number;
};

export const COOKIE_CART = "kk_cart";
export const COOKIE_CART_SHOP = "kk_cart_shop_id";
export const COOKIE_CHECKOUT_PREFILL = "kk_checkout_prefill";

export type CheckoutPrefill = Record<string, string>;

export function parseCartJson(raw: string | undefined): Record<string, CartLine> {
  if (!raw) return {};
  try {
    const o = JSON.parse(raw) as Record<string, CartLine>;
    return o && typeof o === "object" ? o : {};
  } catch {
    return {};
  }
}

export function cartItemCount(cart: Record<string, CartLine>): number {
  return Object.values(cart).reduce((s, item) => s + (item.qty ?? 0), 0);
}

export function encodeMenuImagePath(rel: string): string {
  if (!rel) return "";
  return rel
    .split("/")
    .map((p) => encodeURIComponent(p))
    .join("/");
}
