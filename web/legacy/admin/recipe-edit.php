<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/kitchen_ops.php';

kk_kitchen_ensure_schema($pdo);
$shopId = kk_kitchen_require_shop_id();
$menuId = (int) ($_GET['menu_id'] ?? $_POST['menu_id'] ?? 0);

$menu = $pdo->prepare('SELECT * FROM menus WHERE id = ? AND restaurant_id = ?');
$menu->execute([$menuId, $shopId]);
$menu = $menu->fetch(PDO::FETCH_ASSOC);
if (!$menu) {
    header('Location: recipes.php?shop_id=' . $shopId);
    exit;
}

$recipe = $pdo->prepare('SELECT * FROM kitchen_recipes WHERE menu_id = ?');
$recipe->execute([$menuId]);
$recipe = $recipe->fetch(PDO::FETCH_ASSOC);

$inventory = $pdo->prepare('SELECT id, name, unit FROM kitchen_inventory WHERE shop_id = ? ORDER BY name');
$inventory->execute([$shopId]);
$inventory = $inventory->fetchAll(PDO::FETCH_ASSOC);

$ingredients = [];
if ($recipe) {
    $ings = $pdo->prepare('SELECT * FROM kitchen_recipe_ingredients WHERE recipe_id = ?');
    $ings->execute([(int) $recipe['id']]);
    $ingredients = $ings->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prep = (int) ($_POST['prep_minutes'] ?? 15);
    $yield = max(1, (int) ($_POST['yield_servings'] ?? 1));
    $steps = trim((string) ($_POST['steps'] ?? ''));
    $cal = $_POST['calories'] !== '' ? (int) $_POST['calories'] : null;
    $allergens = trim((string) ($_POST['allergens'] ?? ''));
    $protein = $_POST['protein_g'] !== '' ? (float) $_POST['protein_g'] : null;
    $carbs = $_POST['carbs_g'] !== '' ? (float) $_POST['carbs_g'] : null;
    $fat = $_POST['fat_g'] !== '' ? (float) $_POST['fat_g'] : null;

    if ($recipe) {
        $rid = (int) $recipe['id'];
        $pdo->prepare('UPDATE kitchen_recipes SET prep_minutes=?, yield_servings=?, steps=?, calories=?, allergens=?, protein_g=?, carbs_g=?, fat_g=? WHERE id=?')
            ->execute([$prep, $yield, $steps, $cal, $allergens, $protein, $carbs, $fat, $rid]);
        $pdo->prepare('DELETE FROM kitchen_recipe_ingredients WHERE recipe_id = ?')->execute([$rid]);
    } else {
        $pdo->prepare('INSERT INTO kitchen_recipes (shop_id, menu_id, prep_minutes, yield_servings, steps, calories, allergens, protein_g, carbs_g, fat_g) VALUES (?,?,?,?,?,?,?,?,?,?)')
            ->execute([$shopId, $menuId, $prep, $yield, $steps, $cal, $allergens, $protein, $carbs, $fat]);
        $rid = (int) $pdo->lastInsertId();
    }

    $invIds = $_POST['ing_inventory_id'] ?? [];
    $qtys = $_POST['ing_quantity'] ?? [];
    $ins = $pdo->prepare('INSERT INTO kitchen_recipe_ingredients (recipe_id, inventory_id, quantity) VALUES (?, ?, ?)');
    foreach ($invIds as $i => $invId) {
        $invId = (int) $invId;
        $qty = (float) ($qtys[$i] ?? 0);
        if ($invId > 0 && $qty > 0) {
            $ins->execute([$rid, $invId, $qty]);
        }
    }
    header('Location: recipes.php?shop_id=' . $shopId);
    exit;
}

include '../views/header.php';
?>

<main class="staff-main">
    <header class="staff-page-head">
        <h1 class="staff-page-head__title">Recipe: <?= htmlspecialchars($menu['name']) ?></h1>
        <p class="staff-page-head__sub"><a href="recipes.php?shop_id=<?= $shopId ?>">← All recipes</a></p>
    </header>

    <form method="post" class="staff-panel">
        <input type="hidden" name="menu_id" value="<?= $menuId ?>">
        <div class="staff-panel__body--padded">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Prep time (min)</label>
                    <input type="number" name="prep_minutes" class="form-control" value="<?= (int) ($recipe['prep_minutes'] ?? 15) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Yield (servings)</label>
                    <input type="number" name="yield_servings" class="form-control" value="<?= (int) ($recipe['yield_servings'] ?? 1) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Calories</label>
                    <input type="number" name="calories" class="form-control" value="<?= (int) ($recipe['calories'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Allergens</label>
                    <input type="text" name="allergens" class="form-control" placeholder="e.g. gluten, soy" value="<?= htmlspecialchars($recipe['allergens'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Preparation steps</label>
                    <textarea name="steps" class="form-control" rows="4"><?= htmlspecialchars($recipe['steps'] ?? '') ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Protein (g)</label>
                    <input type="number" step="0.1" name="protein_g" class="form-control" value="<?= $recipe['protein_g'] ?? '' ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Carbs (g)</label>
                    <input type="number" step="0.1" name="carbs_g" class="form-control" value="<?= $recipe['carbs_g'] ?? '' ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fat (g)</label>
                    <input type="number" step="0.1" name="fat_g" class="form-control" value="<?= $recipe['fat_g'] ?? '' ?>">
                </div>
            </div>

            <h3 class="h6 mt-4">Ingredients (inventory deduct per order)</h3>
            <?php for ($i = 0; $i < max(3, count($ingredients) + 1); $i++): ?>
                <?php $ing = $ingredients[$i] ?? null; ?>
                <div class="row g-2 mb-2">
                    <div class="col-md-8">
                        <select name="ing_inventory_id[]" class="form-select form-select-sm">
                            <option value="">— Ingredient —</option>
                            <?php foreach ($inventory as $inv): ?>
                                <option value="<?= (int) $inv['id'] ?>" <?= $ing && (int) $ing['inventory_id'] === (int) $inv['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($inv['name']) ?> (<?= htmlspecialchars($inv['unit']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="number" step="0.001" name="ing_quantity[]" class="form-control form-control-sm" placeholder="Qty" value="<?= $ing ? (float) $ing['quantity'] : '' ?>">
                    </div>
                </div>
            <?php endfor; ?>

            <button type="submit" class="staff-btn staff-btn--primary mt-3">Save recipe</button>
        </div>
    </form>
</main>

<?php include '../views/footer.php'; ?>
