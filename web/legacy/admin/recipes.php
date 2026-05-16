<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/kitchen_ops.php';

kk_kitchen_ensure_schema($pdo);
$shopId = kk_kitchen_require_shop_id();

$rows = $pdo->prepare("
    SELECT m.id AS menu_id, m.name, m.price, r.id AS recipe_id, r.prep_minutes, r.calories, r.allergens
    FROM menus m
    LEFT JOIN kitchen_recipes r ON r.menu_id = m.id
    WHERE m.restaurant_id = ?
    ORDER BY m.name
");
$rows->execute([$shopId]);
$rows = $rows->fetchAll(PDO::FETCH_ASSOC);

include '../views/header.php';
?>

<main class="staff-main">
    <header class="staff-page-head">
        <h1 class="staff-page-head__title">Recipes & production</h1>
        <p class="staff-page-head__sub">Standardized recipes, nutrition, allergens, and ingredient yields</p>
    </header>

    <section class="staff-panel">
        <div class="staff-panel__head">Menu recipes</div>
        <div class="staff-panel__body staff-table-wrap">
            <table class="table align-middle mb-0">
                <thead>
                    <tr><th>Menu item</th><th>Prep</th><th>Calories</th><th>Allergens</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['name']) ?></strong> · ₱<?= number_format((float) $r['price'], 0) ?></td>
                        <td><?= $r['recipe_id'] ? (int) $r['prep_minutes'] . ' min' : '—' ?></td>
                        <td><?= $r['calories'] ? (int) $r['calories'] : '—' ?></td>
                        <td class="small"><?= $r['allergens'] ? htmlspecialchars($r['allergens']) : '—' ?></td>
                        <td>
                            <a href="recipe-edit.php?menu_id=<?= (int) $r['menu_id'] ?>&shop_id=<?= $shopId ?>" class="btn btn-sm btn-outline-primary">
                                <?= $r['recipe_id'] ? 'Edit' : 'Create' ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php include '../views/footer.php'; ?>
