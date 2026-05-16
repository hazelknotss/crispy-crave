<?php
require 'db/database.php';

$shop_id = $_GET['id'] ?? null;

if (!$shop_id) {
    header("Location: index.php");
    exit;
}

if (isset($_SESSION['cart_error'])): ?>
    <div class="alert alert-warning text-center">
        <?= $_SESSION['cart_error']; ?>
    </div>
    <?php unset($_SESSION['cart_error']); ?>
<?php endif;

// Get shop
$stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
$stmt->execute([$shop_id]);
$shop = $stmt->fetch();

if (!$shop) {
    header("Location: index.php");
    exit;
}

// Get menus
$menusStmt = $pdo->prepare("SELECT * FROM menus WHERE restaurant_id = ? AND is_active = 1 ORDER BY id ASC");
$menusStmt->execute([$shop_id]);
$menuList = $menusStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'views/header.php'; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'different_shop'): ?>
    <div class="container mt-3">
        <div class="alert cart-page__flash alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-exclamation-triangle-fill cart-page__flash-icon flex-shrink-0 mt-1" aria-hidden="true"></i>
                <div class="flex-grow-1 min-w-0">
                    <strong class="d-block mb-1">Cart notice</strong>
                    <span class="cart-page__flash-text">You already have items from another shop in your cart. Please clear your cart before ordering from this shop.</span>
                </div>
                <button type="button" class="btn btn-sm cart-page__flash-dismiss flex-shrink-0" data-bs-dismiss="alert" aria-label="Dismiss notice">Dismiss</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<main class="restaurant-menu-page">

<div class="container my-4">

    <header class="restaurant-page-head">
        <div class="restaurant-page-head__brand">
            <div class="restaurant-page-head__logo-wrap flex-shrink-0">
                <img src="<?= htmlspecialchars(app_url('images/logos/' . $shop['logo'])) ?>"
                     class="restaurant-page-head__logo rounded-circle"
                     width="72"
                     height="72"
                     alt=""
                     loading="eager">
            </div>
            <div class="restaurant-page-head__text min-w-0">
                <h1 class="restaurant-page-head__title h4 fw-bold mb-1 text-break"><?= htmlspecialchars($shop['name']) ?></h1>
                <span class="badge-delivery"><i class="bi bi-stopwatch me-1" aria-hidden="true"></i><?= htmlspecialchars($shop['delivery_time']) ?></span>
            </div>
        </div>
        <?php
        $shopAbout = trim((string) ($shop['description'] ?? ''));
        if ($shopAbout !== ''):
        ?>
            <div class="restaurant-page-head__about">
                <h2 id="restaurant-about-heading" class="restaurant-page-head__about-title">About this kitchen</h2>
                <p class="restaurant-page-head__about-text"><?= htmlspecialchars($shopAbout, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php endif; ?>
    </header>

    <h2 id="menu" class="h4 mb-3 menu-section-title d-flex align-items-center gap-2 mt-4"><i class="bi bi-egg-fried fs-4" aria-hidden="true"></i><span>Menu</span></h2>

    <?php if (count($menuList) === 0): ?>
        <p class="text-muted">No menu available.</p>
    <?php else: ?>
        <div class="menu-carousel menu-carousel--restaurant" data-menu-carousel>
            <div class="menu-carousel__shell">
                <button type="button" class="menu-carousel__nav menu-carousel__nav--prev" aria-label="Previous menu items">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </button>
                <button type="button" class="menu-carousel__nav menu-carousel__nav--next" aria-label="Next menu items">
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </button>
                <div class="menu-carousel__viewport">
                    <div class="menu-carousel__track">
                        <?php foreach ($menuList as $menu): ?>
                            <div class="menu-carousel__item">
                                <div class="card menu-item-card h-100 border-0 w-100">
                                    <div class="menu-item-card__img-wrap">
                                        <img src="<?= htmlspecialchars(app_url('images/menus/' . $menu['image'])) ?>"
                                             class="menu-item-card__img"
                                             alt="<?= htmlspecialchars($menu['name']) ?>">
                                    </div>

                                    <div class="card-body">
                                        <h3 class="menu-item-card__title h5"><?= htmlspecialchars($menu['name']) ?></h3>
                                        <p class="menu-item-card__desc text-muted"><?= htmlspecialchars($menu['description']) ?></p>
                                        <strong class="menu-item-card__price">₱<?= number_format($menu['price'], 2) ?></strong>
                                    </div>

                                    <?php if (isset($_SESSION['user'])): ?>
                                    <form method="POST" action="<?= htmlspecialchars(app_url('add-to-cart.php')) ?>">
                                        <input type="hidden" name="menu_id" value="<?= (int) $menu['id'] ?>">
                                        <input type="hidden" name="shop_id" value="<?= (int) $shop_id ?>">
                                        <button type="submit" class="btn btn-sm w-100 btn-menu-cart">
                                            <i class="bi bi-cart-plus me-1" aria-hidden="true"></i>Add to cart
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-menu-outline w-100" data-bs-toggle="modal" data-bs-target="#kkAuthModal" data-auth-tab="login">
                                            <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>Log in to order
                                        </button>
                                    <?php endif; ?>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

</main>

<?php include 'views/footer.php'; ?>
