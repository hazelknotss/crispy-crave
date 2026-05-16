import Link from "next/link";
import { cookies } from "next/headers";
import {
  COOKIE_CART,
  COOKIE_CART_SHOP,
  COOKIE_CHECKOUT_PREFILL,
  cartItemCount,
  encodeMenuImagePath,
  parseCartJson,
} from "@/lib/cart-cookies";
import { createClient, isSupabaseConfigured } from "@/lib/supabase/server";
import { ClearCartLink } from "@/components/store/clear-cart-link";

type Props = { searchParams?: Promise<{ error?: string }> };

export default async function CartPage({ searchParams }: Props) {
  const sp = (await searchParams) ?? {};
  const cookieStore = await cookies();
  const cart = parseCartJson(cookieStore.get(COOKIE_CART)?.value);
  const count = cartItemCount(cart);
  const shopIdRaw = cookieStore.get(COOKIE_CART_SHOP)?.value;
  const shopId = shopIdRaw ? parseInt(shopIdRaw, 10) : NaN;

  let shopName = "";
  if (Number.isFinite(shopId) && shopId > 0 && isSupabaseConfigured()) {
    try {
      const supabase = await createClient();
      const { data: r } = await supabase
        .from("restaurants")
        .select("name")
        .eq("id", shopId)
        .maybeSingle();
      shopName = (r?.name as string | undefined) ?? "";
    } catch {
      shopName = "";
    }
  }

  const prefillRaw = cookieStore.get(COOKIE_CHECKOUT_PREFILL)?.value;
  let prefillNote = "";
  if (prefillRaw) {
    try {
      const o = JSON.parse(prefillRaw) as Record<string, string>;
      if (o.fulfillment) prefillNote = `Checkout prefill: ${o.fulfillment}`;
    } catch {
      prefillNote = "";
    }
  }

  const lines = Object.values(cart);
  let grandTotal = 0;
  for (const line of lines) {
    grandTotal += Number(line.price) * line.qty;
  }

  const shopsHref = "/#shops";

  return (
    <main className="cart-page">
      <div className="container cart-page__inner">
        <header className="cart-page__intro">
          <p className="cart-page__kicker">Checkout</p>
          <h1 className="cart-page__title">Your cart</h1>
          <p className="cart-page__lede">Review items before you continue.</p>
        </header>

        {sp.error === "different_shop" ? (
          <div className="alert cart-page__flash alert-dismissible fade show" role="alert">
            <div className="d-flex align-items-start gap-3">
              <i
                className="bi bi-exclamation-triangle-fill cart-page__flash-icon flex-shrink-0 mt-1"
                aria-hidden="true"
              />
              <div className="flex-grow-1 min-w-0">
                <strong className="d-block mb-1">One shop per order</strong>
                <span className="cart-page__flash-text">
                  You can only add items from one kitchen per order. Clear your cart first, then order
                  from another shop.
                </span>
              </div>
              <button
                type="button"
                className="btn btn-sm cart-page__flash-dismiss flex-shrink-0"
                data-bs-dismiss="alert"
                aria-label="Dismiss notice"
              >
                Dismiss
              </button>
            </div>
          </div>
        ) : null}

        {count === 0 ? (
          <div className="cart-page__surface">
            <div className="cart-page-empty" role="status">
              <i className="bi bi-cart3 cart-page-empty__icon" aria-hidden="true" />
              <p className="cart-page-empty__text">Your cart is empty.</p>
              <Link className="btn btn-sm btn-dark cart-page-empty__cta" href={shopsHref}>
                Browse shops
              </Link>
            </div>
          </div>
        ) : (
          <div className="cart-page__surface">
            {shopName ? (
              <p className="small text-muted mb-3">
                Ordering from <strong>{shopName}</strong>
              </p>
            ) : null}
            {prefillNote ? <p className="small text-muted mb-3">{prefillNote}</p> : null}
            <div className="table-responsive cart-page__scroll">
              <table className="table table-hover cart-page-table align-middle mb-0">
                <thead>
                  <tr>
                    <th scope="col">Item</th>
                    <th scope="col" className="text-end">
                      Price
                    </th>
                    <th scope="col" className="text-end cart-page-table__qty">
                      Qty
                    </th>
                    <th scope="col" className="text-end">
                      Total
                    </th>
                    <th scope="col" className="cart-page-table__act">
                      <span className="visually-hidden">Remove</span>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {lines.map((item) => {
                    const qty = item.qty;
                    const lineTotal = Number(item.price) * qty;
                    const imgPath = item.image
                      ? `/images/menus/${encodeMenuImagePath(item.image)}`
                      : "/images/menus/default.png";
                    return (
                      <tr key={item.menu_id}>
                        <td>
                          <div className="cart-page__line">
                            <img
                              src={imgPath}
                              width={56}
                              height={56}
                              className="cart-page__thumb rounded"
                              style={{ objectFit: "cover" }}
                              alt=""
                              loading="lazy"
                            />
                            <span className="cart-page__line-name">{item.name}</span>
                          </div>
                        </td>
                        <td className="text-end tabular-nums text-muted">
                          ₱{Number(item.price).toFixed(2)}
                        </td>
                        <td className="text-end cart-page-table__qty fw-semibold tabular-nums">
                          {qty}
                        </td>
                        <td className="text-end fw-semibold tabular-nums">
                          ₱{lineTotal.toFixed(2)}
                        </td>
                        <td className="cart-page-table__act text-end">
                          <a
                            href={`/api/cart/remove?id=${item.menu_id}`}
                            className="btn btn-sm btn-outline-danger cart-page__remove rounded-pill"
                            aria-label={`Remove ${item.name}`}
                          >
                            Remove
                          </a>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>

            <div className="cart-page__bar">
              <ClearCartLink />
              <div className="cart-page__bar-total">
                <span className="cart-page__bar-label">Grand total</span>
                <span className="cart-page__bar-amount tabular-nums">
                  ₱{grandTotal.toFixed(2)}
                </span>
              </div>
            </div>

            <div className="cart-page__cta">
              <Link
                href="/checkout"
                className="btn btn-dark btn-lg rounded-pill px-4 fw-semibold"
              >
                Checkout
              </Link>
            </div>
          </div>
        )}
      </div>
    </main>
  );
}
