<?php
require_once 'db/database.php';
require_once __DIR__ . '/app/url.php';
require_once __DIR__ . '/app/menu_recommendations.php';

$shops = $pdo
    ->query('SELECT * FROM restaurants WHERE is_active = 1 ORDER BY name ASC')
    ->fetchAll();

$isLoggedIn = !empty($_SESSION['user']);
$userName = $isLoggedIn ? (string) ($_SESSION['user']['name'] ?? '') : '';

$menusByShop = [];
if (count($shops) > 0) {
    $shopIds = array_values(array_filter(array_map('intval', array_column($shops, 'id'))));
    if ($shopIds !== []) {
        $inList = implode(',', $shopIds);
        $menuRows = $pdo->query(
            "SELECT id, restaurant_id, name, description, price, image FROM menus WHERE is_active = 1 AND restaurant_id IN ($inList) ORDER BY restaurant_id ASC, id ASC"
        );
        foreach ($menuRows as $row) {
            $rid = (int) $row['restaurant_id'];
            if (!isset($menusByShop[$rid])) {
                $menusByShop[$rid] = [];
            }
            $menusByShop[$rid][] = $row;
        }
    }
}

$kkMenuCatalog = kk_menu_catalog_for_picks($pdo);
$kkPicksConfig = [
    'catalog' => $kkMenuCatalog,
    'imgBase' => app_url('images/menus/'),
    'restaurantUrl' => app_url('restaurant.php'),
    'loggedIn' => $isLoggedIn,
    'defaultLat' => 10.7813,
    'defaultLon' => 122.6340,
    'defaultPlace' => 'Pototan area',
];

include 'views/header.php';

$splashSrc = app_url('images/splash.png');
$splashPath = app_project_root() . '/images/splash.png';
if (is_file($splashPath)) {
    $splashSrc .= '?v=' . (string) filemtime($splashPath);
}
?>

<?php if (!$isLoggedIn): ?>
<section id="hero" class="hero hero--split" aria-labelledby="hero-heading">
    <div class="hero-deco" aria-hidden="true">
        <span class="hero-deco__t">Crispy</span>
        <span class="hero-deco__t hero-deco__t--b">Crave</span>
        <span class="hero-deco__t hero-deco__t--c">Fresh</span>
    </div>

    <div class="hero-inner">
        <div class="hero-copy">
            <p class="hero-eyebrow">Crispy Crave</p>
            <h1 id="hero-heading" class="hero-title">
                Local favorites, elevated<span class="hero-title__dot">.</span>
                <span class="hero-title__sub">Local kitchens. One simple checkout. Straight to your door.</span>
            </h1>
            <p class="hero-lede">
                Serving Pototan — browse menus from trusted local restaurants, check out in a few taps, and
                get meals, snacks, and drinks delivered to your barangay while they're still fresh.
            </p>
            <div class="hero-cta-row">
                <button type="button" class="order-btn" data-bs-toggle="modal" data-bs-target="#kkAuthModal" data-auth-tab="login">Order now</button>
                <a href="#shops" class="hero-scroll-hint">Scroll to menus</a>
            </div>
        </div>

        <div class="hero-visual">
            <div class="hero-visual__stack">
                <div class="hero-visual__blob" aria-hidden="true"></div>
                <img
                    src="<?= htmlspecialchars($splashSrc) ?>"
                    alt="Crispy Crave — food from Pototan kitchens"
                    class="hero-visual__img"
                    fetchpriority="high"
                    decoding="async">
                <div class="hero-visual__badge" aria-hidden="true">
                    <strong>Today</strong>
                    <span>Made to order</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="shops" class="shops-section">
    <div class="section-inner">
        <header class="section-head">
            <p class="section-eyebrow">Shops</p>
            <h2>Choose a kitchen</h2>
            <p>See dishes from each kitchen below, or open a shop for the full menu and checkout.</p>
        </header>

        <?php if (count($kkMenuCatalog) > 0): ?>
            <?php include __DIR__ . '/views/crispy-picks.php'; ?>
        <?php endif; ?>

        <div class="shops-grid">
            <?php if (count($shops) > 0): ?>
                <?php foreach ($shops as $shop): ?>
                    <?php
                    $sid = (int) $shop['id'];
                    $stripMenus = array_slice($menusByShop[$sid] ?? [], 0, 10);
                    ?>
                    <article class="shop-card shop-card--preview">
                        <a href="restaurant.php?id=<?= $sid ?>" class="shop-card__hero">
                            <img
                                src="<?= htmlspecialchars(app_url('images/logos/' . $shop['logo'])) ?>"
                                alt="<?= htmlspecialchars($shop['name']) ?>"
                                class="shop-logo">
                            <div class="shop-info">
                                <h3><?= htmlspecialchars($shop['name']) ?></h3>
                                <p><?= htmlspecialchars($shop['description']) ?></p>
                                <small>Delivery <?= htmlspecialchars($shop['delivery_time']) ?></small>
                            </div>
                        </a>
                        <?php if (count($stripMenus) > 0): ?>
                            <div class="shop-card__menu-strip" role="list" aria-label="<?= htmlspecialchars($shop['name']) ?> — popular items">
                                <?php foreach ($stripMenus as $item): ?>
                                    <a
                                        href="restaurant.php?id=<?= $sid ?>"
                                        class="shop-card__menu-chip"
                                        role="listitem">
                                        <span class="shop-card__menu-chip-img">
                                            <img
                                                src="<?= htmlspecialchars(app_url('images/menus/' . $item['image'])) ?>"
                                                alt="<?= htmlspecialchars($item['name']) ?>"
                                                width="72"
                                                height="72"
                                                loading="lazy">
                                        </span>
                                        <span class="shop-card__menu-chip-name"><?= htmlspecialchars($item['name']) ?></span>
                                        <span class="shop-card__menu-chip-price">₱<?= number_format((float) $item['price'], 2) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="shops-empty">No restaurants available right now. Check back soon.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php else: ?>

<?php
$kkBarangayDistances = require __DIR__ . '/data/barangay_pototan.php';
$kkBarangayJson = json_encode($kkBarangayDistances, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);
?>

<section id="shops" class="home-logged" aria-labelledby="home-logged-heading">
    <div class="home-logged__inner">
        <header class="home-welcome">
            <h1 id="home-logged-heading" class="home-welcome__title">
                Welcome<span class="home-welcome__comma">,</span>
                <span class="home-welcome__name"><?= htmlspecialchars($userName !== '' ? $userName : 'back') ?></span>
            </h1>
            <p class="home-welcome__hint">Please choose your order.</p>
        </header>

        <?php if (count($kkMenuCatalog) > 0): ?>
            <?php include __DIR__ . '/views/crispy-picks.php'; ?>
        <?php endif; ?>

        <?php if (count($shops) === 0): ?>
            <p class="home-logged__empty">No restaurants are open right now. Please check back soon.</p>
        <?php else: ?>
            <div class="home-shops">
                <?php foreach ($shops as $shop): ?>
                    <?php
                    $sid = (int) $shop['id'];
                    $previewMenus = $menusByShop[$sid] ?? [];
                    ?>
                    <article class="home-shop-card">
                        <div class="home-shop-card__top">
                            <a href="restaurant.php?id=<?= $sid ?>" class="home-shop-card__brand">
                                <span class="home-shop-card__logo-wrap">
                                    <img
                                        src="<?= htmlspecialchars(app_url('images/logos/' . $shop['logo'])) ?>"
                                        alt="<?= htmlspecialchars($shop['name']) ?>"
                                        width="56"
                                        height="56"
                                        loading="lazy"
                                        class="home-shop-card__logo">
                                </span>
                                <span class="home-shop-card__brand-text">
                                    <span class="home-shop-card__name"><?= htmlspecialchars($shop['name']) ?></span>
                                    <span class="home-shop-card__meta">
                                        <i class="bi bi-clock" aria-hidden="true"></i>
                                        Delivery <?= htmlspecialchars($shop['delivery_time']) ?>
                                    </span>
                                </span>
                            </a>
                            <a href="restaurant.php?id=<?= $sid ?>" class="home-shop-card__link">Open shop</a>
                        </div>

                        <?php if (count($previewMenus) > 0): ?>
                            <div class="menu-carousel menu-carousel--home" data-menu-carousel role="group" aria-label="<?= htmlspecialchars($shop['name']) ?> — menu preview">
                                <div class="menu-carousel__shell">
                                    <button type="button" class="menu-carousel__nav menu-carousel__nav--prev" aria-label="Previous dishes">
                                        <i class="bi bi-chevron-left" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" class="menu-carousel__nav menu-carousel__nav--next" aria-label="Next dishes">
                                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                    </button>
                                    <div class="menu-carousel__viewport">
                                        <div class="menu-carousel__track">
                                            <?php foreach ($previewMenus as $item): ?>
                                                <?php
                                                $mid = (int) $item['id'];
                                                $desc = (string) ($item['description'] ?? '');
                                                ?>
                                                <div class="menu-carousel__item">
                                                    <div class="home-menu-tile">
                                                        <button
                                                            type="button"
                                                            class="home-menu-tile__media home-menu-tile__media--trigger"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#kkHomeMenuModal"
                                                            data-kk-open="details"
                                                            data-kk-menu-id="<?= $mid ?>"
                                                            data-kk-shop-id="<?= $sid ?>"
                                                            data-kk-name="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-kk-price="<?= htmlspecialchars((string) $item['price'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-kk-image="<?= htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-kk-description="<?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?>"
                                                            data-kk-shop-name="<?= htmlspecialchars($shop['name'], ENT_QUOTES, 'UTF-8') ?>"
                                                            aria-label="View <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> — ingredients and order">
                                                            <span class="home-menu-tile__img">
                                                                <img
                                                                    src="<?= htmlspecialchars(app_url('images/menus/' . $item['image'])) ?>"
                                                                    alt=""
                                                                    width="380"
                                                                    height="285"
                                                                    loading="lazy"
                                                                    decoding="async">
                                                            </span>
                                                        </button>
                                                        <div class="home-menu-tile__body">
                                                            <span class="home-menu-tile__name"><?= htmlspecialchars($item['name']) ?></span>
                                                            <span class="home-menu-tile__price">₱<?= number_format((float) $item['price'], 2) ?></span>
                                                        </div>
                                                        <div class="home-menu-tile__actions">
                                                            <button
                                                                type="button"
                                                                class="home-menu-tile__btn home-menu-tile__btn--secondary"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#kkHomeMenuModal"
                                                                data-kk-open="details"
                                                                data-kk-menu-id="<?= $mid ?>"
                                                                data-kk-shop-id="<?= $sid ?>"
                                                                data-kk-name="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>"
                                                                data-kk-price="<?= htmlspecialchars((string) $item['price'], ENT_QUOTES, 'UTF-8') ?>"
                                                                data-kk-image="<?= htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') ?>"
                                                                data-kk-description="<?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?>"
                                                                data-kk-shop-name="<?= htmlspecialchars($shop['name'], ENT_QUOTES, 'UTF-8') ?>">
                                                                Order now
                                                            </button>
                                                            <button
                                                                type="button"
                                                                class="home-menu-tile__btn home-menu-tile__btn--primary"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#kkHomeMenuModal"
                                                                data-kk-open="cart"
                                                                data-kk-menu-id="<?= $mid ?>"
                                                                data-kk-shop-id="<?= $sid ?>"
                                                                data-kk-name="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>"
                                                                data-kk-price="<?= htmlspecialchars((string) $item['price'], ENT_QUOTES, 'UTF-8') ?>"
                                                                data-kk-image="<?= htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') ?>"
                                                                data-kk-description="<?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?>"
                                                                data-kk-shop-name="<?= htmlspecialchars($shop['name'], ENT_QUOTES, 'UTF-8') ?>">
                                                                Add to cart
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="home-shop-card__empty">Menu coming soon — open the shop for updates.</p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="modal fade" id="kkHomeMenuModal" tabindex="-1" aria-labelledby="kkHomeMenuModalLabel" aria-hidden="true"
     data-img-base="<?= htmlspecialchars(app_url('images/menus/'), ENT_QUOTES, 'UTF-8') ?>"
     data-restaurant-href="<?= htmlspecialchars(app_url('restaurant.php'), ENT_QUOTES, 'UTF-8') ?>">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable kk-home-menu-dialog">
        <div class="modal-content home-menu-modal position-relative">
            <button type="button" class="kk-modal-dismiss home-menu-modal__dismiss" data-bs-dismiss="modal" aria-label="Dismiss dialog">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
            <div class="modal-header border-0 pb-0 home-menu-modal__head">
                <div class="min-w-0 pe-2 flex-grow-1">
                    <p class="text-muted small mb-1 text-break" id="kkHomeMenuShopLine"></p>
                    <h2 class="modal-title fw-bold text-break" id="kkHomeMenuModalLabel"></h2>
                </div>
            </div>
            <div class="modal-body pt-2">
                <div id="kkHomeMenuStep1" class="kk-home-menu-step">
                    <div class="row g-3 align-items-start">
                        <div class="col-sm-5">
                            <img src="" alt="" class="w-100 rounded-3 shadow-sm home-menu-modal__img" id="kkHomeMenuImg" width="400" height="300">
                        </div>
                        <div class="col-sm-7">
                            <p class="home-menu-modal__price mb-3" id="kkHomeMenuPriceLine"></p>
                            <div id="kkHomeMenuDetailsWrap" class="home-menu-modal__details d-none">
                                <h3 class="h6 fw-semibold mb-2">Ingredients &amp; details</h3>
                                <p class="text-muted mb-0" id="kkHomeMenuDesc"></p>
                            </div>
                            <button type="button" class="btn btn-link px-0 d-none mt-2" id="kkHomeMenuShowDetails">
                                Show ingredients &amp; details
                            </button>
                            <div id="kkHomeMenuStep1Actions" class="d-none mt-3 d-flex flex-column flex-sm-row flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-secondary flex-grow-1" id="kkHomeMenuStep1Order">
                                    Order now
                                </button>
                                <button type="button" class="btn btn-dark flex-grow-1" id="kkHomeMenuStep1Add">
                                    <i class="bi bi-cart-plus me-1" aria-hidden="true"></i>Add to cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="kkHomeMenuStep2" class="kk-home-menu-step d-none">
                    <div class="kk-home-menu-checkout-lite border rounded-3 p-3 mb-3 bg-body-secondary">
                        <h4 class="h6 fw-semibold mb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-receipt-cutoff" aria-hidden="true"></i><span>Your cart (this item)</span>
                        </h4>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-2">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="kkMiniCartBody"></tbody>
                            </table>
                        </div>
                        <p class="mb-1 small"><strong>Food Total:</strong> ₱<span id="kkMiniFoodTotal">0.00</span></p>
                        <p class="mb-1 small"><strong>Rider Fee:</strong> ₱<span id="kkMiniRiderFee">0.00</span></p>
                        <p class="mb-0 small"><strong>Grand Total:</strong> ₱<span id="kkMiniGrandTotal">0.00</span></p>
                    </div>

                    <h3 class="h6 fw-semibold mb-3 kk-home-menu-step2__heading">Pick up or delivery</h3>
                    <div class="row g-2 mb-3" role="group" aria-label="Fulfillment">
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="kk_order_fulfillment" id="kkOrderFulDelivery" value="delivery" checked autocomplete="off">
                            <label class="btn btn-outline-secondary w-100 h-100 py-2 d-flex align-items-center justify-content-center text-center" for="kkOrderFulDelivery">Delivery</label>
                        </div>
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="kk_order_fulfillment" id="kkOrderFulPickup" value="pickup" autocomplete="off">
                            <label class="btn btn-outline-secondary w-100 h-100 py-2 d-flex align-items-center justify-content-center text-center" for="kkOrderFulPickup">Pick up</label>
                        </div>
                    </div>

                    <div class="mb-3 position-relative" id="kkModalBarangayWrap">
                        <label class="d-flex flex-wrap align-items-center gap-1 gap-sm-2 form-label fw-semibold mb-1 text-break" for="kkModalBarangay">
                            <i class="bi bi-geo-alt flex-shrink-0" aria-hidden="true"></i><span>Barangay (Pototan, Iloilo only)</span>
                        </label>
                        <input type="text" class="form-control" id="kkModalBarangay" placeholder="Type your barangay..." autocomplete="off">
                        <div id="kkModalSuggestions" class="list-group position-absolute w-100 shadow-sm rounded mt-1" style="z-index: 1060; max-height: 12rem; overflow-y: auto;"></div>
                    </div>

                    <div class="mb-3" id="kkOrderAddressBlock">
                        <label class="d-flex flex-wrap align-items-center gap-1 gap-sm-2 form-label fw-semibold mb-1 text-break" for="kkOrderStreet">
                            <i class="bi bi-house-door flex-shrink-0" aria-hidden="true"></i><span id="kkOrderStreetLabel">Street / landmark</span>
                        </label>
                        <textarea class="form-control" id="kkOrderStreet" rows="2" placeholder="House number, street, landmark…"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="d-flex flex-wrap align-items-center gap-1 gap-sm-2 form-label fw-semibold mb-1 text-break" for="kkOrderTime">
                            <i class="bi bi-clock flex-shrink-0" aria-hidden="true"></i><span>Preferred pickup / delivery time</span>
                        </label>
                        <input type="time" class="form-control" id="kkOrderTime" value="12:00">
                    </div>

                    <div class="mb-3">
                        <label class="d-flex flex-wrap align-items-center gap-1 gap-sm-2 form-label fw-semibold mb-1 text-break" for="kkOrderNotes">
                            <i class="bi bi-chat-left-text flex-shrink-0" aria-hidden="true"></i><span>Order notes</span>
                        </label>
                        <textarea class="form-control" id="kkOrderNotes" rows="2" placeholder="Optional"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold d-flex flex-wrap align-items-center gap-1 gap-sm-2 mb-2 text-break">
                            <i class="bi bi-truck flex-shrink-0" aria-hidden="true"></i><span>Delivery options</span>
                        </label>
                        <div class="delivery-option active">
                            <input type="radio" name="kk_delivery_option" value="standard" id="kkDelStandard" checked>
                            <label for="kkDelStandard">
                                Standard
                                <small class="text-muted d-block">20 – 35 mins</small>
                            </label>
                        </div>
                        <div class="delivery-option">
                            <input type="radio" name="kk_delivery_option" value="priority" id="kkDelPriority">
                            <label for="kkDelPriority">
                                Priority
                                <small class="text-muted d-block">40 – 55 mins · + ₱30 rider fee</small>
                                <span class="badge bg-success mt-1">Available</span>
                            </label>
                        </div>
                        <div class="delivery-option">
                            <input type="radio" name="kk_delivery_option" value="scheduled" id="kkDelScheduled">
                            <label for="kkDelScheduled">
                                Scheduled
                                <small class="text-muted d-block">Choose date &amp; time below</small>
                                <span class="badge bg-success mt-1">Available</span>
                            </label>
                        </div>
                        <div class="kk-modal-scheduled mt-2 d-none" id="kkModalScheduledFields">
                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <label class="form-label small fw-semibold" for="kkScheduleDate">Delivery date</label>
                                    <input type="date" class="form-control" id="kkScheduleDate" min="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small fw-semibold" for="kkScheduleTime">Delivery time</label>
                                    <input type="time" class="form-control" id="kkScheduleTime" value="12:00">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="fw-bold d-flex flex-wrap align-items-center gap-1 gap-sm-2 mb-2 text-break">
                            <i class="bi bi-credit-card flex-shrink-0" aria-hidden="true"></i><span>Payment method</span>
                        </label>
                        <div class="payment-option active">
                            <input type="radio" name="kk_order_payment" value="cod" id="kkPayCod" checked>
                            <label for="kkPayCod" class="d-inline-flex align-items-center flex-wrap gap-2 mb-0">
                                <i class="bi bi-cash-coin fs-5 text-success" aria-hidden="true"></i>
                                <span>Cash on delivery</span>
                                <span class="badge bg-success">Available</span>
                            </label>
                        </div>
                        <div class="payment-option">
                            <input type="radio" name="kk_order_payment" value="gcash" id="kkPayGcash">
                            <label for="kkPayGcash" class="d-inline-flex align-items-center flex-wrap gap-2 mb-0">
                                <i class="bi bi-phone fs-5 text-primary" aria-hidden="true"></i>
                                <span>GCash</span>
                                <span class="badge bg-success">Available</span>
                            </label>
                        </div>
                        <div class="payment-option">
                            <input type="radio" name="kk_order_payment" value="bank" id="kkPayBank">
                            <label for="kkPayBank" class="d-inline-flex align-items-center flex-wrap gap-2 mb-0">
                                <i class="bi bi-bank fs-5 text-body-secondary" aria-hidden="true"></i>
                                <span>Bank transfer</span>
                                <span class="badge bg-success">Available</span>
                            </label>
                        </div>
                        <div class="payment-option">
                            <input type="radio" name="kk_order_payment" value="card" id="kkPayCard">
                            <label for="kkPayCard" class="d-inline-flex align-items-center flex-wrap gap-2 mb-0">
                                <i class="bi bi-credit-card-2-front fs-5 text-body-secondary" aria-hidden="true"></i>
                                <span>Credit / debit card</span>
                                <span class="badge bg-success">Available</span>
                            </label>
                        </div>
                    </div>
                    <script type="application/json" id="kk-home-barangay-data"><?= $kkBarangayJson ?></script>
                </div>
            </div>
            <div class="modal-footer border-0 flex-column flex-sm-row flex-wrap align-items-stretch align-items-sm-center gap-2 pt-0 w-100">
                <a href="#" class="btn btn-outline-secondary order-1 order-sm-0 w-100 w-sm-auto text-center" id="kkHomeMenuShopLink">View full menu</a>
                <div class="ms-sm-auto d-flex flex-column flex-sm-row flex-wrap gap-2 align-items-stretch align-items-sm-center w-100 w-sm-auto order-0 order-sm-1">
                    <button type="button" class="btn btn-outline-secondary d-none w-100 w-sm-auto" id="kkHomeMenuBtnBack">Back</button>
                    <button type="button" class="btn btn-dark d-none w-100 w-sm-auto" id="kkHomeMenuBtnContinue">Continue</button>
                    <form method="POST" id="kkHomeMenuAddForm" action="<?= htmlspecialchars(app_url('add-to-cart.php')) ?>" class="d-flex flex-column flex-sm-row gap-2 align-items-stretch align-items-sm-center w-100 w-sm-auto">
                        <input type="hidden" name="menu_id" id="kkHomeMenuInputMenuId" value="">
                        <input type="hidden" name="shop_id" id="kkHomeMenuInputShopId" value="">
                        <input type="hidden" name="prefill_flow" id="kkPrefillFlow" value="">
                        <input type="hidden" name="prefill_fulfillment" id="kkPrefillFulfillment" value="delivery">
                        <input type="hidden" name="prefill_address" id="kkPrefillAddress" value="">
                        <input type="hidden" name="prefill_time" id="kkPrefillTime" value="">
                        <input type="hidden" name="prefill_payment" id="kkPrefillPayment" value="cod">
                        <input type="hidden" name="prefill_notes" id="kkPrefillNotes" value="">
                        <input type="hidden" name="prefill_barangay" id="kkPrefillBarangay" value="">
                        <input type="hidden" name="prefill_delivery_option" id="kkPrefillDeliveryOption" value="standard">
                        <input type="hidden" name="prefill_schedule_date" id="kkPrefillScheduleDate" value="">
                        <input type="hidden" name="prefill_schedule_time" id="kkPrefillScheduleTime" value="">
                        <input type="hidden" name="prefill_distance_km" id="kkPrefillDistanceKm" value="">
                        <input type="hidden" name="prefill_rider_fee" id="kkPrefillRiderFee" value="">
                        <button type="submit" class="btn btn-dark w-100 w-sm-auto" id="kkHomeMenuBtnSubmit">
                            <i class="bi bi-cart-plus me-1" aria-hidden="true"></i>Add to cart
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php include 'views/footer.php'; ?>

