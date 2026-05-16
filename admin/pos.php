<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/staff.php';
require_once __DIR__ . '/../app/kitchen_ops.php';
require_once __DIR__ . '/../app/menu_ops.php';

kk_kitchen_ensure_schema($pdo);
kk_menu_ensure_schema($pdo);
$shopId = kk_kitchen_require_shop_id();

$shopStmt = $pdo->prepare('SELECT name FROM restaurants WHERE id = ?');
$shopStmt->execute([$shopId]);
$shopName = $shopStmt->fetchColumn() ?: 'Shop';

if (!isset($_SESSION['pos_cart'])) {
    $_SESSION['pos_cart'] = [];
}
if (!isset($_SESSION['pos_cart'][$shopId])) {
    $_SESSION['pos_cart'][$shopId] = [];
}

$cart = &$_SESSION['pos_cart'][$shopId];

function kk_pos_add_menu_to_cart(array &$cart, array $row): void
{
    $id = (int) $row['id'];
    if (!isset($cart[$id])) {
        $cart[$id] = [
            'name' => $row['name'],
            'price' => (float) $row['price'],
            'qty' => 0,
        ];
    }
    $cart[$id]['qty']++;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $menuId = (int) ($_POST['menu_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT id, name, price FROM menus WHERE id = ? AND restaurant_id = ? AND is_active = 1');
        $stmt->execute([$menuId, $shopId]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            kk_pos_add_menu_to_cart($cart, $row);
        }
    } elseif ($action === 'decrease') {
        $menuId = (int) ($_POST['menu_id'] ?? 0);
        if (isset($cart[$menuId])) {
            $cart[$menuId]['qty']--;
            if ($cart[$menuId]['qty'] <= 0) {
                unset($cart[$menuId]);
            }
        }
    } elseif ($action === 'clear') {
        $cart = [];
    } elseif ($action === 'remove') {
        $menuId = (int) ($_POST['menu_id'] ?? 0);
        unset($cart[$menuId]);
    }

    header('Location: pos.php?shop_id=' . $shopId);
    exit;
}

$menus = $pdo->prepare('SELECT * FROM menus WHERE restaurant_id = ? AND is_active = 1 ORDER BY name');
$menus->execute([$shopId]);
$menus = $menus->fetchAll(PDO::FETCH_ASSOC);

$categories = ['All'];
foreach ($menus as &$m) {
    $m['_category'] = kk_menu_resolve_category($m['name'], $m['category'] ?? null);
    if (!in_array($m['_category'], $categories, true)) {
        $categories[] = $m['_category'];
    }
}
unset($m);
sort($categories);
if (($categories[0] ?? '') !== 'All') {
    array_unshift($categories, 'All');
} else {
    $rest = array_slice($categories, 1);
    sort($rest);
    $categories = array_merge(['All'], $rest);
}

$cartTotal = 0;
$cartItemCount = 0;
foreach ($cart as $item) {
    $cartTotal += $item['price'] * $item['qty'];
    $cartItemCount += (int) $item['qty'];
}

$cashError = isset($_GET['cash_error']);
$kkBodyClass = 'staff-portal pos-page';
include '../views/header.php';
?>

<main class="staff-main staff-main--pos" id="posRoot" data-cart-total="<?= htmlspecialchars((string) $cartTotal, ENT_QUOTES, 'UTF-8') ?>">
    <header class="pos-head">
        <div class="pos-head__text">
            <h1 class="pos-head__title">Point of sale</h1>
            <p class="pos-head__sub"><?= htmlspecialchars($shopName) ?> · syncs to kitchen display</p>
        </div>
        <div class="pos-head__actions">
            <button type="button" class="pos-head__btn" id="posFullscreenBtn" title="Full screen">
                <i class="bi bi-arrows-fullscreen" aria-hidden="true"></i>
            </button>
            <a href="kds.php?shop_id=<?= (int) $shopId ?>" class="pos-head__link">
                <i class="bi bi-display" aria-hidden="true"></i><span>KDS</span>
            </a>
        </div>
    </header>

    <?php if ($cashError): ?>
        <p class="pos-alert" role="status">Cash received must cover the order total.</p>
    <?php endif; ?>

    <div class="pos-layout">
        <section class="pos-menu" aria-label="Menu items">
            <div class="pos-menu__toolbar">
                <h2 class="pos-menu__title">Menu</h2>
                <span class="pos-menu__count" id="posVisibleCount"><?= count($menus) ?> items</span>
            </div>

            <div class="pos-search-wrap">
                <i class="bi bi-search pos-search-wrap__icon" aria-hidden="true"></i>
                <input type="search" id="posSearch" class="pos-search-wrap__input"
                       placeholder="Search menu…" autocomplete="off">
            </div>

            <div class="pos-categories" id="posCategories" role="tablist">
                <?php foreach ($categories as $cat): ?>
                    <button type="button" class="pos-cat-btn<?= $cat === 'All' ? ' is-active' : '' ?>"
                            data-category="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($cat) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php if (empty($menus)): ?>
                <p class="pos-empty">No active menu items. Add items under Menu management.</p>
            <?php else: ?>
                <div class="pos-menu-grid" id="posMenuGrid">
                    <?php foreach ($menus as $m):
                        $mid = (int) $m['id'];
                        $inCart = isset($cart[$mid]) ? (int) $cart[$mid]['qty'] : 0;
                        $cat = $m['_category'];
                        ?>
                        <form method="post" class="pos-menu-item"
                              data-name="<?= htmlspecialchars(strtolower($m['name']), ENT_QUOTES, 'UTF-8') ?>"
                              data-category="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="menu_id" value="<?= $mid ?>">
                            <button type="submit" class="pos-menu-item__btn<?= $inCart > 0 ? ' is-in-cart' : '' ?>">
                                <?php if ($inCart > 0): ?>
                                    <span class="pos-menu-item__badge"><?= $inCart ?></span>
                                <?php endif; ?>
                                <span class="pos-menu-item__name"><?= htmlspecialchars($m['name']) ?></span>
                                <span class="pos-menu-item__price">₱<?= number_format((float) $m['price'], 0) ?></span>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
                <p class="pos-no-results" id="posNoResults" hidden>No items match your search.</p>
            <?php endif; ?>
        </section>

        <aside class="pos-sale" aria-label="Current sale">
            <header class="pos-sale__head">
                <h2 class="pos-sale__title">Current sale</h2>
                <?php if ($cartItemCount > 0): ?>
                    <span class="pos-sale__count"><?= $cartItemCount ?> <?= $cartItemCount === 1 ? 'item' : 'items' ?></span>
                <?php endif; ?>
            </header>

            <div class="pos-sale__body">
                <?php if (empty($cart)): ?>
                    <div class="pos-sale__empty">
                        <i class="bi bi-basket" aria-hidden="true"></i>
                        <p>Tap a menu item to start a sale.</p>
                    </div>
                <?php else: ?>
                    <ul class="pos-sale__lines">
                        <?php foreach ($cart as $mid => $item):
                            $lineTotal = $item['price'] * $item['qty'];
                            ?>
                            <li class="pos-sale__line">
                                <div class="pos-sale__line-main">
                                    <span class="pos-sale__qty"><?= (int) $item['qty'] ?></span>
                                    <span class="pos-sale__name"><?= htmlspecialchars($item['name']) ?></span>
                                    <span class="pos-sale__amount">₱<?= number_format($lineTotal, 2) ?></span>
                                </div>
                                <div class="pos-sale__line-actions">
                                    <form method="post" class="pos-sale__qty-form">
                                        <input type="hidden" name="action" value="decrease">
                                        <input type="hidden" name="menu_id" value="<?= (int) $mid ?>">
                                        <button type="submit" class="pos-sale__qty-btn" aria-label="Decrease">−</button>
                                    </form>
                                    <form method="post" class="pos-sale__qty-form">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="menu_id" value="<?= (int) $mid ?>">
                                        <button type="submit" class="pos-sale__qty-btn" aria-label="Increase">+</button>
                                    </form>
                                    <form method="post" class="pos-sale__remove-form">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="menu_id" value="<?= (int) $mid ?>">
                                        <button type="submit" class="pos-sale__remove" aria-label="Remove">×</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="pos-sale__total" id="posSaleTotal">
                        <span>Total</span>
                        <strong>₱<?= number_format($cartTotal, 2) ?></strong>
                    </div>

                    <form method="post" action="pos-checkout.php" class="pos-sale__checkout" id="posCheckoutForm">
                        <input type="hidden" name="shop_id" value="<?= (int) $shopId ?>">
                        <input type="hidden" name="cash_tendered" id="posCashTenderedHidden" value="">

                        <label class="pos-field">
                            <span class="pos-field__label">Payment</span>
                            <select name="payment_method" id="posPaymentMethod" class="pos-field__input">
                                <option value="cod">Cash</option>
                                <option value="gcash">GCash</option>
                            </select>
                        </label>

                        <div class="pos-cash-panel" id="posCashPanel">
                            <p class="pos-cash-panel__label">Cash received</p>
                            <div class="pos-cash-panel__display" id="posCashDisplay">₱0.00</div>
                            <div class="pos-cash-panel__change">
                                <span>Change</span>
                                <strong id="posChangeDisplay">₱0.00</strong>
                            </div>
                            <div class="pos-keypad" id="posKeypad" role="group" aria-label="Cash keypad">
                                <button type="button" class="pos-key" data-key="1">1</button>
                                <button type="button" class="pos-key" data-key="2">2</button>
                                <button type="button" class="pos-key" data-key="3">3</button>
                                <button type="button" class="pos-key" data-key="4">4</button>
                                <button type="button" class="pos-key" data-key="5">5</button>
                                <button type="button" class="pos-key" data-key="6">6</button>
                                <button type="button" class="pos-key" data-key="7">7</button>
                                <button type="button" class="pos-key" data-key="8">8</button>
                                <button type="button" class="pos-key" data-key="9">9</button>
                                <button type="button" class="pos-key pos-key--action" data-key="exact">Exact</button>
                                <button type="button" class="pos-key" data-key="0">0</button>
                                <button type="button" class="pos-key pos-key--action" data-key="back">⌫</button>
                                <button type="button" class="pos-key pos-key--wide" data-key="00">00</button>
                                <button type="button" class="pos-key pos-key--action" data-key="clear">Clear</button>
                            </div>
                        </div>

                        <label class="pos-field">
                            <span class="pos-field__label">Order type</span>
                            <select name="fulfillment" class="pos-field__input">
                                <option value="pickup">Pickup / counter</option>
                                <option value="delivery">Delivery</option>
                            </select>
                        </label>

                        <button type="submit" class="pos-sale__submit" id="posSubmitBtn">Complete sale</button>
                    </form>

                    <form method="post" class="pos-sale__clear-form">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="pos-sale__clear">Clear cart</button>
                    </form>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</main>

<script src="<?= htmlspecialchars(app_url('js/pos.js'), ENT_QUOTES, 'UTF-8') ?>"></script>

<?php include '../views/footer.php'; ?>
