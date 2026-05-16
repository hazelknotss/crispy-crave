<?php
require '../auth/auth.php';
requireStaff();
require '../db/database.php';
require_once __DIR__ . '/../app/staff.php';

$shopId = kk_staff_require_shop();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($name !== '' && $email !== '' && $password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role, restaurant_id, approval_status)
            VALUES (?, ?, ?, 'rider', ?, 'approved')
        ");
        $stmt->execute([$name, $email, $hash, $shopId]);
    }
    header('Location: riders.php');
    exit;
}

include '../views/header.php';
?>

<main class="staff-main">
    <a href="riders.php" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-arrow-left me-1"></i>Back</a>
    <h3 class="mb-3">Add rider</h3>
    <form method="POST" class="col-md-6">
        <input type="text" name="name" class="form-control mb-2" placeholder="Name" required>
        <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
        <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
        <button type="submit" class="btn btn-success">Save</button>
    </form>
</main>

<?php include '../views/footer.php'; ?>
