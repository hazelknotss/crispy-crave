<?php
session_start();
require_once __DIR__ . '/../db/database.php';
require_once __DIR__ . '/../app/rider_portal.php';

kk_rider_ensure_schema($pdo);

$error = '';
$success = '';
$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;
if ($step < 1 || $step > 2) {
    $step = 1;
}

$draft = $_SESSION['rider_apply_draft'] ?? [];
if (!is_array($draft)) {
    $draft = [];
}

/* Step 1 — account & vehicle */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['apply_step'] ?? '') === '1') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $vehicleType = trim((string) ($_POST['vehicle_type'] ?? 'motorcycle'));
    $vehiclePlate = trim((string) ($_POST['vehicle_plate'] ?? ''));

    if ($name === '' || $email === '' || strlen($password) < 6 || $phone === '') {
        $error = 'Please complete all required fields (password must be 6+ characters).';
        $step = 1;
    } else {
        $exists = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $exists->execute([$email]);
        if ($exists->fetch()) {
            $error = 'That email is already registered.';
            $step = 1;
        } else {
            $_SESSION['rider_apply_draft'] = [
                'name'          => $name,
                'email'         => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'phone'         => $phone,
                'vehicle_type'  => $vehicleType,
                'vehicle_plate' => $vehiclePlate,
            ];
            header('Location: apply.php?step=2');
            exit;
        }
    }
    $draft = array_merge($draft, [
        'name'          => $name,
        'email'         => $email,
        'phone'         => $phone,
        'vehicle_type'  => $vehicleType,
        'vehicle_plate' => $vehiclePlate,
    ]);
}

/* Step 2 — documents & submit */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['apply_step'] ?? '') === '2') {
    $step = 2;
    if (empty($draft['email']) || empty($draft['password_hash'])) {
        header('Location: apply.php?step=1');
        exit;
    }

    $licenseOk = !empty($_FILES['doc_license']['name']) && ($_FILES['doc_license']['error'] ?? 0) === UPLOAD_ERR_OK;
    $idOk = !empty($_FILES['doc_id']['name']) && ($_FILES['doc_id']['error'] ?? 0) === UPLOAD_ERR_OK;

    if (!$licenseOk || !$idOk) {
        $error = 'Please upload both your driver\'s license and valid ID.';
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, approval_status)
                VALUES (?, ?, ?, 'rider', 'pending')
            ");
            $stmt->execute([
                $draft['name'],
                $draft['email'],
                $draft['password_hash'],
            ]);
            $userId = (int) $pdo->lastInsertId();

            kk_rider_ensure_profile_row($pdo, $userId);
            $upd = $pdo->prepare('
                UPDATE riders SET phone = ?, vehicle_type = ?, vehicle_plate = ?, status = \'available\'
                WHERE user_id = ?
            ');
            $upd->execute([
                $draft['phone'],
                $draft['vehicle_type'],
                $draft['vehicle_plate'],
                $userId,
            ]);

            $uploadDir = kk_rider_upload_dir();
            foreach (['license' => 'doc_license', 'id_photo' => 'doc_id'] as $type => $field) {
                $ext = pathinfo((string) $_FILES[$field]['name'], PATHINFO_EXTENSION);
                $safe = $type . '_' . $userId . '_' . time() . '.' . preg_replace('/[^a-z0-9]/i', '', $ext);
                $target = $uploadDir . '/' . $safe;
                if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
                    throw new RuntimeException('Upload failed');
                }
                kk_rider_save_document($pdo, $userId, $type, $safe);
            }

            kk_rider_notify(
                $pdo,
                $userId,
                'Application received',
                'Your rider application is under review. We will notify you when approved.',
                null
            );

            $pdo->commit();
            unset($_SESSION['rider_apply_draft']);
            $success = 'Application submitted! You can sign in once an admin approves your account.';
            $step = 0;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $error = 'Could not submit application. Please try again.';
        }
    }
}

if ($step === 2 && empty($draft['email'])) {
    header('Location: apply.php?step=1');
    exit;
}

$bgImage = app_url('images/rider.jpg');
$logoUrl = app_brand_logo_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Become a rider — Crispy Crave</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('css/rider-portal.css')) ?>">
</head>
<body class="rider-login-page rider-login-page--apply">
    <div class="rider-login-shell rider-login-shell--apply">
        <aside class="rider-login-visual rider-login-visual--apply" style="--rider-login-bg: url('<?= htmlspecialchars($bgImage, ENT_QUOTES, 'UTF-8') ?>')">
            <div class="rider-login-visual__inner">
                <div class="rider-login-visual__logo-wrap" aria-hidden="true">
                    <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="rider-login-visual__logo" width="52" height="52">
                </div>
                <p class="rider-login-visual__brand">Crispy Crave</p>
                <h1 class="rider-login-visual__title">Rider sign-up</h1>
                <p class="rider-login-visual__text">Register, upload your documents, and start delivering when approved.</p>
            </div>
        </aside>

        <main class="rider-login-panel rider-login-panel--apply">
            <div class="rider-apply-panel-wrap">
            <div class="rider-login-panel__card rider-apply-card">
                <div class="rider-login-panel__inner">
                    <a href="<?= htmlspecialchars(app_url('rider/login.php'), ENT_QUOTES, 'UTF-8') ?>" class="rider-login-panel__back rider-login-panel__back--pill">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i>
                        <span>Rider sign in</span>
                    </a>

                    <?php if ($success !== ''): ?>
                        <div class="rider-apply-success" role="status">
                            <span class="rider-apply-success__icon" aria-hidden="true"><i class="bi bi-check-circle-fill"></i></span>
                            <h2 class="rider-login-panel__title">You&apos;re all set</h2>
                            <p class="rider-login-panel__lede"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
                            <a href="<?= htmlspecialchars(app_url('rider/login.php'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-dark w-100 rider-login-submit">Go to rider sign in</a>
                        </div>
                    <?php else: ?>

                    <ol class="rider-apply-steps" aria-label="Sign-up progress">
                        <li class="rider-apply-steps__item<?= $step >= 1 ? ' rider-apply-steps__item--active' : '' ?><?= $step > 1 ? ' rider-apply-steps__item--done' : '' ?>">
                            <span class="rider-apply-steps__num">1</span>
                            <span class="rider-apply-steps__label">Account</span>
                        </li>
                        <li class="rider-apply-steps__item<?= $step >= 2 ? ' rider-apply-steps__item--active' : '' ?>">
                            <span class="rider-apply-steps__num">2</span>
                            <span class="rider-apply-steps__label">Documents</span>
                        </li>
                    </ol>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger rider-login-alert py-2"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <?php if ($step === 1): ?>
                        <h2 class="rider-login-panel__title">Your details</h2>
                        <p class="rider-login-panel__lede rider-login-panel__lede--tight">Create your rider account. Step 2 uploads your license and ID.</p>

                        <form method="post" class="rider-login-form rider-apply-form">
                            <input type="hidden" name="apply_step" value="1">
                            <div class="row g-2">
                                <div class="col-12">
                                    <label class="form-label">Full name</label>
                                    <input type="text" name="name" class="form-control form-control-sm rider-login-input" required
                                           value="<?= htmlspecialchars((string) ($draft['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control form-control-sm rider-login-input" required
                                           value="<?= htmlspecialchars((string) ($draft['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="phone" class="form-control form-control-sm rider-login-input" required
                                           value="<?= htmlspecialchars((string) ($draft['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control form-control-sm rider-login-input" minlength="6" required autocomplete="new-password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Vehicle</label>
                                    <select name="vehicle_type" class="form-select form-select-sm rider-login-input" required>
                                        <?php
                                        $vt = (string) ($draft['vehicle_type'] ?? 'motorcycle');
                                        foreach (['motorcycle' => 'Motorcycle', 'bicycle' => 'Bicycle', 'car' => 'Car'] as $val => $label):
                                            ?>
                                            <option value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"<?= $vt === $val ? ' selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Plate no. <span class="text-muted fw-normal">(optional)</span></label>
                                    <input type="text" name="vehicle_plate" class="form-control form-control-sm rider-login-input"
                                           value="<?= htmlspecialchars((string) ($draft['vehicle_plate'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-dark w-100 rider-login-submit mt-2">Continue to documents</button>
                        </form>

                    <?php else: ?>
                        <h2 class="rider-login-panel__title">Upload documents</h2>
                        <p class="rider-login-panel__lede rider-login-panel__lede--tight">Admin will review before you can sign in.</p>

                        <div class="rider-apply-review small text-muted mb-2">
                            <strong class="text-dark"><?= htmlspecialchars((string) $draft['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            · <?= htmlspecialchars((string) $draft['email'], ENT_QUOTES, 'UTF-8') ?>
                        </div>

                        <form method="post" enctype="multipart/form-data" class="rider-login-form rider-apply-form">
                            <input type="hidden" name="apply_step" value="2">
                            <div class="mb-2">
                                <label class="form-label">Driver&apos;s license</label>
                                <input type="file" name="doc_license" class="form-control form-control-sm" accept="image/*,.pdf" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Valid ID</label>
                                <input type="file" name="doc_id" class="form-control form-control-sm" accept="image/*,.pdf" required>
                            </div>
                            <div class="d-flex flex-column flex-sm-row gap-2 mt-2">
                                <a href="apply.php?step=1" class="btn btn-outline-secondary flex-fill rider-login-submit">Back</a>
                                <button type="submit" class="btn btn-dark flex-fill rider-login-submit">Submit application</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <?php endif; ?>
                </div>
            </div>
            </div>
        </main>
    </div>
</body>
</html>
