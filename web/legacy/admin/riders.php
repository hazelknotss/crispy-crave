<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/staff.php';

$shopId = kk_staff_require_shop();

$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'rider' AND restaurant_id = ?");
$stmt->execute([$shopId]);
$riders = $stmt->fetchAll();

include '../views/header.php';
?>

<main class="staff-main">
    <header class="staff-page-head d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1 class="staff-page-head__title">Riders</h1>
            <p class="staff-page-head__sub">Delivery riders for your shop</p>
        </div>
        <a href="add-rider.php" class="staff-btn staff-btn--primary">
            <i class="bi bi-person-plus"></i> Add rider
        </a>
    </header>

    <section class="staff-panel">
        <div class="staff-panel__body staff-table-wrap">
            <table class="table align-middle mb-0">
                <thead>
                    <tr><th>Name</th><th>Email</th></tr>
                </thead>
                <tbody>
                <?php foreach ($riders as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['name']) ?></td>
                        <td><?= htmlspecialchars($r['email']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($riders)): ?>
                    <tr><td colspan="2" class="staff-empty">No riders yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php include '../views/footer.php'; ?>
