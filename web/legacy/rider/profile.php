<?php
require '../auth/auth.php';
require '../db/database.php';
require_once __DIR__ . '/../app/rider_portal.php';

requireRider();
kk_rider_ensure_schema($pdo);

$riderId = (int) $_SESSION['user']['id'];
$profile = kk_rider_profile($pdo, $riderId);
$documents = kk_rider_documents($pdo, $riderId);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $vehicleType = trim((string) ($_POST['vehicle_type'] ?? ''));
    $vehiclePlate = trim((string) ($_POST['vehicle_plate'] ?? ''));
    $emergency = trim((string) ($_POST['emergency_contact'] ?? ''));
    $fleetStatus = in_array($_POST['fleet_status'] ?? '', ['available', 'busy'], true)
        ? $_POST['fleet_status'] : 'available';

    kk_rider_ensure_profile_row($pdo, $riderId);
    $upd = $pdo->prepare('
        UPDATE riders SET phone = ?, vehicle_type = ?, vehicle_plate = ?, emergency_contact = ?, status = ?
        WHERE user_id = ?
    ');
    $upd->execute([$phone, $vehicleType, $vehiclePlate, $emergency, $fleetStatus, $riderId]);

    $uploadDir = kk_rider_upload_dir();
    foreach (['license' => 'doc_license', 'registration' => 'doc_reg', 'id_photo' => 'doc_id'] as $type => $field) {
        if (!empty($_FILES[$field]['name']) && ($_FILES[$field]['error'] ?? 0) === UPLOAD_ERR_OK) {
            $ext = pathinfo((string) $_FILES[$field]['name'], PATHINFO_EXTENSION);
            $safe = $type . '_' . $riderId . '_' . time() . '.' . preg_replace('/[^a-z0-9]/i', '', $ext);
            if (move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . '/' . $safe)) {
                kk_rider_save_document($pdo, $riderId, $type, $safe);
            }
        }
    }

    $message = 'Profile updated.';
    $profile = kk_rider_profile($pdo, $riderId);
    $documents = kk_rider_documents($pdo, $riderId);
}

$onboardingOk = $profile ? kk_rider_onboarding_complete($profile, $documents) : false;
$kkRiderNavActive = 'profile';
$riderPageTitle = 'Profile';
require '../views/rider-layout-head.php';
?>

<main class="rider-dash-page">
    <div class="container-fluid rider-dash-page__inner">
        <header class="rider-dash-hero">
            <div class="rider-dash-hero__copy">
                <p class="rider-dash-header__kicker">Account</p>
                <h1 class="rider-dash-header__title">Profile &amp; documents</h1>
                <p class="rider-dash-header__lede">Manage personal details, vehicle info, and compliance files.</p>
            </div>
        </header>

        <?php if ($message !== ''): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($profile && ($profile['approval_status'] ?? '') === 'pending'): ?>
            <div class="alert alert-warning">Your account is pending admin approval.</div>
        <?php elseif (!$onboardingOk): ?>
            <div class="alert alert-info">Upload a valid license and ID to complete onboarding.</div>
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="rider-dash-surface p-3 p-md-4">
                    <h2 class="h6 fw-bold mb-3">Personal &amp; vehicle</h2>
                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Phone</label>
                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars((string) ($profile['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Vehicle type</label>
                            <select name="vehicle_type" class="form-select">
                                <?php foreach (['motorcycle', 'bicycle', 'car'] as $vt): ?>
                                    <option value="<?= $vt ?>" <?= ($profile['vehicle_type'] ?? '') === $vt ? 'selected' : '' ?>><?= ucfirst($vt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Plate number</label>
                            <input type="text" name="vehicle_plate" class="form-control" value="<?= htmlspecialchars((string) ($profile['vehicle_plate'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Emergency contact</label>
                            <input type="text" name="emergency_contact" class="form-control" value="<?= htmlspecialchars((string) ($profile['emergency_contact'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Fleet status</label>
                            <select name="fleet_status" class="form-select">
                                <option value="available" <?= ($profile['fleet_status'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
                                <option value="busy" <?= ($profile['fleet_status'] ?? '') === 'busy' ? 'selected' : '' ?>>Busy / on delivery</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-dark">Save profile</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="rider-dash-surface p-3 p-md-4 mb-3">
                    <h2 class="h6 fw-bold mb-3">Compliance documents</h2>
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="phone" value="<?= htmlspecialchars((string) ($profile['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="vehicle_type" value="<?= htmlspecialchars((string) ($profile['vehicle_type'] ?? 'motorcycle'), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="vehicle_plate" value="<?= htmlspecialchars((string) ($profile['vehicle_plate'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="emergency_contact" value="<?= htmlspecialchars((string) ($profile['emergency_contact'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="fleet_status" value="<?= htmlspecialchars((string) ($profile['fleet_status'] ?? 'available'), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="mb-2">
                            <label class="form-label small">Driver's license</label>
                            <input type="file" name="doc_license" class="form-control form-control-sm" accept="image/*,.pdf">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Vehicle registration</label>
                            <input type="file" name="doc_reg" class="form-control form-control-sm" accept="image/*,.pdf">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Valid ID</label>
                            <input type="file" name="doc_id" class="form-control form-control-sm" accept="image/*,.pdf">
                        </div>
                        <button type="submit" class="btn btn-outline-dark btn-sm">Upload documents</button>
                    </form>
                    <ul class="list-group list-group-flush mt-3">
                        <?php if ($documents === []): ?>
                            <li class="list-group-item text-muted small">No documents uploaded yet.</li>
                        <?php else: ?>
                            <?php foreach ($documents as $doc): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center small">
                                    <span><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) $doc['doc_type'])), ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="badge bg-<?= ($doc['status'] ?? '') === 'approved' ? 'success' : (($doc['status'] ?? '') === 'rejected' ? 'danger' : 'secondary') ?>"><?= htmlspecialchars((string) $doc['status'], ENT_QUOTES, 'UTF-8') ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                <p class="small text-muted mb-0">New rider? <a href="<?= htmlspecialchars(app_url('rider/apply.php'), ENT_QUOTES, 'UTF-8') ?>">Refer applicants</a></p>
            </div>
        </div>
    </div>
</main>

<?php require '../views/rider-layout-foot.php'; ?>
