import Link from "next/link";
import type { MenuRow, ShopRow } from "@/lib/menu-catalog";
import { encodeMenuImagePath } from "@/lib/cart-cookies";

type Props = {
  shop: ShopRow;
  menus: MenuRow[];
  isLoggedIn: boolean;
  differentShopError: boolean;
};

export function RestaurantMenuPage({
  shop,
  menus,
  isLoggedIn,
  differentShopError,
}: Props) {
  const logoSrc = `/images/logos/${encodeMenuImagePath(shop.logo ?? "")}`;
  const about = (shop.description ?? "").trim();

  return (
    <>
      {differentShopError ? (
        <div className="container mt-3">
          <div
            className="alert cart-page__flash alert-dismissible fade show"
            role="alert"
          >
            <div className="d-flex align-items-start gap-3">
              <i
                className="bi bi-exclamation-triangle-fill cart-page__flash-icon flex-shrink-0 mt-1"
                aria-hidden="true"
              />
              <div className="flex-grow-1 min-w-0">
                <strong className="d-block mb-1">Cart notice</strong>
                <span className="cart-page__flash-text">
                  You already have items from another shop in your cart. Please clear your
                  cart before ordering from this kitchen.
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
        </div>
      ) : null}

      <main className="restaurant-menu-page">
        <div className="container my-4">
          <header className="restaurant-page-head">
            <div className="restaurant-page-head__brand">
              <div className="restaurant-page-head__logo-wrap flex-shrink-0">
                <img
                  src={logoSrc}
                  className="restaurant-page-head__logo rounded-circle"
                  width={72}
                  height={72}
                  alt=""
                  loading="eager"
                />
              </div>
              <div className="restaurant-page-head__text min-w-0">
                <h1 className="restaurant-page-head__title h4 fw-bold mb-1 text-break">
                  {shop.name}
                </h1>
                <span className="badge-delivery">
                  <i className="bi bi-stopwatch me-1" aria-hidden="true" />
                  {shop.delivery_time ?? "Delivery"}
                </span>
              </div>
            </div>
            {about !== "" ? (
              <div className="restaurant-page-head__about">
                <h2
                  id="restaurant-about-heading"
                  className="restaurant-page-head__about-title"
                >
                  About this kitchen
                </h2>
                <p className="restaurant-page-head__about-text">{about}</p>
              </div>
            ) : null}
          </header>

          <h2
            id="menu"
            className="h4 mb-3 menu-section-title d-flex align-items-center gap-2 mt-4"
          >
            <i className="bi bi-egg-fried fs-4" aria-hidden="true" />
            <span>Menu</span>
          </h2>

          {menus.length === 0 ? (
            <p className="text-muted">No menu available.</p>
          ) : (
            <div
              className="menu-carousel menu-carousel--restaurant"
              data-menu-carousel
            >
              <div className="menu-carousel__shell">
                <button
                  type="button"
                  className="menu-carousel__nav menu-carousel__nav--prev"
                  aria-label="Previous menu items"
                >
                  <i className="bi bi-chevron-left" aria-hidden="true" />
                </button>
                <button
                  type="button"
                  className="menu-carousel__nav menu-carousel__nav--next"
                  aria-label="Next menu items"
                >
                  <i className="bi bi-chevron-right" aria-hidden="true" />
                </button>
                <div className="menu-carousel__viewport">
                  <div className="menu-carousel__track">
                    {menus.map((menu) => {
                      const mid = menu.id;
                      const imgSrc = `/images/menus/${encodeMenuImagePath(menu.image ?? "")}`;
                      const desc = menu.description ?? "";
                      return (
                        <div key={mid} className="menu-carousel__item">
                          <div className="card menu-item-card h-100 border-0 w-100">
                            <div className="menu-item-card__img-wrap">
                              <img
                                src={imgSrc}
                                className="menu-item-card__img"
                                alt={menu.name}
                              />
                            </div>
                            <div className="card-body">
                              <h3 className="menu-item-card__title h5">{menu.name}</h3>
                              <p className="menu-item-card__desc text-muted">{desc}</p>
                              <strong className="menu-item-card__price">
                                ₱{Number(menu.price).toFixed(2)}
                              </strong>
                            </div>
                            {isLoggedIn ? (
                              <form action="/api/cart/add" method="POST">
                                <input type="hidden" name="menu_id" value={mid} />
                                <button
                                  type="submit"
                                  className="btn btn-sm w-100 btn-menu-cart"
                                >
                                  <i className="bi bi-cart-plus me-1" aria-hidden="true" />
                                  Add to cart
                                </button>
                              </form>
                            ) : (
                              <button
                                type="button"
                                className="btn btn-sm btn-menu-outline w-100"
                                data-bs-toggle="modal"
                                data-bs-target="#kkAuthModal"
                                data-auth-tab="login"
                              >
                                <i
                                  className="bi bi-box-arrow-in-right me-1"
                                  aria-hidden="true"
                                />
                                Log in to order
                              </button>
                            )}
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              </div>
            </div>
          )}

          <p className="mt-4 mb-0">
            <Link href="/" className="text-decoration-none">
              ← Back to home
            </Link>
          </p>
        </div>
      </main>
    </>
  );
}
