<?php
require '../auth/auth.php';
requirePlatformAdmin();
require '../db/database.php';

$shops = $pdo->query('SELECT * FROM restaurants ORDER BY id DESC')->fetchAll();
?>

<?php include '../views/header.php'; ?>

<main class="staff-main staff-shops-page">
    <header class="staff-page-head staff-page-head--row">
        <div>
            <h1 class="staff-page-head__title">Chicken shops</h1>
            <p class="staff-page-head__sub">Manage locations, menus, and kitchen operations</p>
        </div>
        <a href="add-shop.php" class="staff-btn staff-btn--primary">
            <i class="bi bi-plus-lg" aria-hidden="true"></i> Add shop
        </a>
    </header>

    <?php if (empty($shops)): ?>
        <div class="staff-empty-panel">
            <i class="bi bi-shop" aria-hidden="true"></i>
            <p>No shops yet. Add your first location to get started.</p>
            <a href="add-shop.php" class="staff-btn staff-btn--primary">Add shop</a>
        </div>
    <?php else: ?>
        <div class="staff-shop-grid staff-shop-grid--manage">
            <?php foreach ($shops as $shop):
                $desc = trim((string) ($shop['description'] ?? ''));
                $delivery = trim((string) ($shop['delivery_time'] ?? ''));
                $logoUrl = app_url('images/logos/' . $shop['logo']);
                $noImg = app_url('images/no-image.png');
                ?>
                <article class="staff-shop-card staff-shop-card--manage">
                    <div class="staff-shop-card__img-wrap staff-shop-card__img-wrap--contain">
                        <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>"
                             onerror="this.src='<?= htmlspecialchars($noImg, ENT_QUOTES, 'UTF-8') ?>'"
                             class="staff-shop-card__img"
                             alt="<?= htmlspecialchars($shop['name'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="staff-shop-card__body">
                        <h2 class="staff-shop-card__title"><?= htmlspecialchars($shop['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <?php if ($desc !== ''): ?>
                            <p class="staff-shop-card__desc"><?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <?php if ($delivery !== ''): ?>
                            <p class="staff-shop-card__meta">
                                <i class="bi bi-clock" aria-hidden="true"></i>
                                <?= htmlspecialchars($delivery, ENT_QUOTES, 'UTF-8') ?> min delivery
                            </p>
                        <?php endif; ?>

                        <div class="staff-shop-card__actions">
                            <a href="kds.php?shop_id=<?= (int) $shop['id'] ?>" class="staff-chip staff-chip--menus">KDS</a>
                            <a href="pos.php?shop_id=<?= (int) $shop['id'] ?>" class="staff-chip staff-chip--edit">POS</a>
                            <a href="menus.php?shop_id=<?= (int) $shop['id'] ?>" class="staff-chip staff-chip--menus">Menu</a>
                        </div>

                        <footer class="staff-shop-card__footer">
                            <a href="edit-shop.php?id=<?= (int) $shop['id'] ?>" class="staff-shop-card__link staff-shop-card__link--edit">
                                <i class="bi bi-pencil" aria-hidden="true"></i> Edit
                            </a>
                            <a href="shop_delete.php?id=<?= (int) $shop['id'] ?>"
                               class="staff-shop-card__link staff-shop-card__link--danger"
                               onclick="return confirm('Delete this shop?')">
                                <i class="bi bi-trash3" aria-hidden="true"></i> Delete
                            </a>
                        </footer>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include '../views/footer.php'; ?>
