<?php
session_start();
$cart = $_SESSION['cart'] ?? [];
include 'views/header.php';
$shopsHref = htmlspecialchars(app_url('index.php')) . '#shops';
?>

<main class="cart-page">
    <div class="container cart-page__inner">
        <header class="cart-page__intro">
            <p class="cart-page__kicker">Checkout</p>
            <h1 class="cart-page__title">Your cart</h1>
            <p class="cart-page__lede">Review items before you continue.</p>
        </header>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'different_shop'): ?>
            <div class="alert cart-page__flash alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-exclamation-triangle-fill cart-page__flash-icon flex-shrink-0 mt-1" aria-hidden="true"></i>
                    <div class="flex-grow-1 min-w-0">
                        <strong class="d-block mb-1">One shop per order</strong>
                        <span class="cart-page__flash-text">You can only add items from one shop per order. Clear your cart first, then order from another shop.</span>
                    </div>
                    <button type="button" class="btn btn-sm cart-page__flash-dismiss flex-shrink-0" data-bs-dismiss="alert" aria-label="Dismiss notice">Dismiss</button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($cart)): ?>
            <div class="cart-page__surface">
                <div class="cart-page-empty" role="status">
                    <i class="bi bi-cart3 cart-page-empty__icon" aria-hidden="true"></i>
                    <p class="cart-page-empty__text">Your cart is empty.</p>
                    <a class="btn btn-sm btn-dark cart-page-empty__cta" href="<?= $shopsHref ?>">Browse shops</a>
                </div>
            </div>
        <?php else: ?>
            <?php $grandTotal = 0; ?>
            <div class="cart-page__surface">
                <div class="table-responsive cart-page__scroll">
                    <table class="table table-hover cart-page-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Item</th>
                                <th scope="col" class="text-end">Price</th>
                                <th scope="col" class="text-end cart-page-table__qty">Qty</th>
                                <th scope="col" class="text-end">Total</th>
                                <th scope="col" class="cart-page-table__act"><span class="visually-hidden">Remove</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart as $item): ?>
                                <?php
                                $qty = (int) $item['qty'];
                                $total = (float) $item['price'] * $qty;
                                $grandTotal += $total;
                                $rel = 'images/menus/' . $item['image'];
                                $full = app_project_root() . '/' . $rel;
                                if (empty($item['image']) || !is_file($full)) {
                                    $imagePath = app_url('images/menus/default.png');
                                } else {
                                    $imagePath = app_url('images/menus/' . $item['image']);
                                }
                                ?>
                                <tr>
                                    <td>
                                        <div class="cart-page__line">
                                            <img src="<?= htmlspecialchars($imagePath) ?>"
                                                 width="56"
                                                 height="56"
                                                 class="cart-page__thumb rounded"
                                                 style="object-fit:cover;"
                                                 alt=""
                                                 loading="lazy">
                                            <span class="cart-page__line-name"><?= htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end tabular-nums text-muted">₱<?= number_format((float) $item['price'], 2) ?></td>
                                    <td class="text-end cart-page-table__qty fw-semibold tabular-nums"><?= $qty ?></td>
                                    <td class="text-end fw-semibold tabular-nums">₱<?= number_format($total, 2) ?></td>
                                    <td class="cart-page-table__act text-end">
                                        <a href="<?= htmlspecialchars(app_url('remove-from-cart.php?id=' . (int) $item['menu_id'])) ?>"
                                           class="btn btn-sm btn-outline-danger cart-page__remove rounded-pill"
                                           aria-label="Remove <?= htmlspecialchars((string) ($item['name'] ?? 'item'), ENT_QUOTES, 'UTF-8') ?>">Remove</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="cart-page__bar">
                    <a href="<?= htmlspecialchars(app_url('clear-cart.php')) ?>"
                       class="btn btn-sm btn-outline-danger rounded-pill"
                       onclick="return confirm('Clear your cart?');">
                        <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Clear cart
                    </a>
                    <div class="cart-page__bar-total">
                        <span class="cart-page__bar-label">Grand total</span>
                        <span class="cart-page__bar-amount tabular-nums">₱<?= number_format($grandTotal, 2) ?></span>
                    </div>
                </div>

                <div class="cart-page__cta">
                    <a href="<?= htmlspecialchars(app_url('checkout.php')) ?>" class="btn btn-dark btn-lg rounded-pill px-4 fw-semibold">Checkout</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'views/footer.php'; ?>
