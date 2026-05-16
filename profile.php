<?php
session_start();
require_once __DIR__ . '/db/database.php';
require_once __DIR__ . '/app/customer_profile.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'user') {
    header('Location: login.php?redirect=profile.php');
    exit;
}

$userId = (int) $_SESSION['user']['id'];
$message = '';
$error = '';

$stmt = $pdo->prepare('SELECT id, name, email, created_at FROM users WHERE id = ? AND role = ?');
$stmt->execute([$userId, 'user']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: logout.php');
    exit;
}

$profile = kk_customer_profile_get($pdo, $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'profile');

    if ($action === 'account') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));

        if ($name === '') {
            $error = 'Name cannot be empty.';
        } else {
            $pdo->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$name, $userId]);
            $_SESSION['user']['name'] = $name;
            $user['name'] = $name;

            kk_customer_profile_save($pdo, $userId, array_merge($profile, ['phone' => $phone]));
            $profile = kk_customer_profile_get($pdo, $userId);
            $message = 'Account details saved.';
        }
    } elseif ($action === 'payments') {
        kk_customer_profile_save($pdo, $userId, [
            'phone' => $profile['phone'],
            'gcash_number' => $_POST['gcash_number'] ?? '',
            'gcash_account_name' => $_POST['gcash_account_name'] ?? '',
            'bank_name' => $_POST['bank_name'] ?? '',
            'bank_account_name' => $_POST['bank_account_name'] ?? '',
            'bank_account_number' => $_POST['bank_account_number'] ?? '',
            'card_holder_name' => $_POST['card_holder_name'] ?? '',
            'card_number' => $_POST['card_number'] ?? '',
            'card_exp_month' => $_POST['card_exp_month'] ?? '',
            'card_exp_year' => $_POST['card_exp_year'] ?? '',
            'preferred_payment' => $_POST['preferred_payment'] ?? '',
        ]);
        $profile = kk_customer_profile_get($pdo, $userId);
        $message = 'Payment details saved. We only store the last 4 digits of your card — never the full number or CVV.';
    } elseif ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $newPass = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        $pwStmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $pwStmt->execute([$userId]);
        $hash = $pwStmt->fetchColumn();

        if (!password_verify($current, (string) $hash)) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($newPass) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($newPass !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([password_hash($newPass, PASSWORD_DEFAULT), $userId]);
            $message = 'Password updated successfully.';
        }
    }
}

$kkBodyClass = 'profile-layout';
include __DIR__ . '/views/header.php';

$memberSince = date('M Y', strtotime((string) $user['created_at']));
$hasGcash = !empty($profile['gcash_number']);
$hasBank = !empty($profile['bank_account_number']);
$hasCard = !empty($profile['card_last4']);
?>

<main class="profile-page">
    <div class="profile-page__inner">
        <header class="profile-page__intro">
            <a href="<?= htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="profile-page__back">
                <i class="bi bi-arrow-left" aria-hidden="true"></i><span>Back</span>
            </a>
            <p class="profile-page__kicker">Your account</p>
            <h1 class="profile-page__title">Profile</h1>
            <p class="profile-page__lede">Update your details and saved payment methods for faster checkout.</p>
        </header>

        <?php if ($message !== ''): ?>
            <div class="alert alert-success profile-page__alert" role="status"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger profile-page__alert" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="profile-page__grid">
            <aside class="profile-summary">
                <div class="profile-summary__avatar" aria-hidden="true">
                    <?= strtoupper(substr((string) $user['name'], 0, 1)) ?>
                </div>
                <h2 class="profile-summary__name"><?= htmlspecialchars($user['name']) ?></h2>
                <p class="profile-summary__email"><?= htmlspecialchars($user['email']) ?></p>
                <p class="profile-summary__meta">Member since <?= htmlspecialchars($memberSince) ?></p>
                <ul class="profile-summary__badges">
                    <li class="<?= $hasGcash ? 'is-set' : '' ?>"><i class="bi bi-phone"></i> GCash</li>
                    <li class="<?= $hasBank ? 'is-set' : '' ?>"><i class="bi bi-bank"></i> Bank</li>
                    <li class="<?= $hasCard ? 'is-set' : '' ?>"><i class="bi bi-credit-card"></i> Card</li>
                </ul>
            </aside>

            <div class="profile-page__sections">
                <section class="profile-card">
                    <h2 class="profile-card__title">Account</h2>
                    <form method="post" class="profile-form">
                        <input type="hidden" name="action" value="account">
                        <label class="profile-field">
                            <span class="profile-field__label">Full name</span>
                            <input type="text" name="name" class="profile-field__input" required
                                   value="<?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="profile-field">
                            <span class="profile-field__label">Email</span>
                            <input type="email" class="profile-field__input" disabled
                                   value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>">
                            <span class="profile-field__hint">Email cannot be changed here.</span>
                        </label>
                        <label class="profile-field">
                            <span class="profile-field__label">Phone</span>
                            <input type="tel" name="phone" class="profile-field__input" placeholder="09XX XXX XXXX"
                                   value="<?= htmlspecialchars((string) ($profile['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <button type="submit" class="profile-btn profile-btn--primary">Save account</button>
                    </form>
                </section>

                <section class="profile-card">
                    <h2 class="profile-card__title">Payment methods</h2>
                    <p class="profile-card__lede">Saved for your convenience at checkout. Card numbers are never stored in full.</p>
                    <form method="post" class="profile-form">
                        <input type="hidden" name="action" value="payments">

                        <fieldset class="profile-fieldset">
                            <legend class="profile-fieldset__legend"><i class="bi bi-phone"></i> GCash</legend>
                            <label class="profile-field">
                                <span class="profile-field__label">GCash number</span>
                                <input type="tel" name="gcash_number" class="profile-field__input" inputmode="numeric"
                                       placeholder="09XX XXX XXXX"
                                       value="<?= htmlspecialchars((string) ($profile['gcash_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </label>
                            <label class="profile-field">
                                <span class="profile-field__label">Account name</span>
                                <input type="text" name="gcash_account_name" class="profile-field__input"
                                       value="<?= htmlspecialchars((string) ($profile['gcash_account_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </label>
                        </fieldset>

                        <fieldset class="profile-fieldset">
                            <legend class="profile-fieldset__legend"><i class="bi bi-bank"></i> Bank transfer</legend>
                            <label class="profile-field">
                                <span class="profile-field__label">Bank name</span>
                                <input type="text" name="bank_name" class="profile-field__input" placeholder="e.g. BDO, BPI"
                                       value="<?= htmlspecialchars((string) ($profile['bank_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </label>
                            <label class="profile-field">
                                <span class="profile-field__label">Account name</span>
                                <input type="text" name="bank_account_name" class="profile-field__input"
                                       value="<?= htmlspecialchars((string) ($profile['bank_account_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </label>
                            <label class="profile-field">
                                <span class="profile-field__label">Account number</span>
                                <input type="text" name="bank_account_number" class="profile-field__input" inputmode="numeric"
                                       placeholder="<?= $profile['bank_account_number'] ? 'Leave blank to keep ' . kk_customer_profile_mask_account($profile['bank_account_number']) : 'Account number' ?>"
                                       autocomplete="off">
                                <?php if (!empty($profile['bank_account_number'])): ?>
                                    <span class="profile-field__hint">Saved: <?= htmlspecialchars(kk_customer_profile_mask_account($profile['bank_account_number'])) ?></span>
                                <?php endif; ?>
                            </label>
                        </fieldset>

                        <fieldset class="profile-fieldset">
                            <legend class="profile-fieldset__legend"><i class="bi bi-credit-card"></i> Credit / debit card</legend>
                            <?php if (!empty($profile['card_last4'])): ?>
                                <p class="profile-saved-card">
                                    <?= htmlspecialchars((string) ($profile['card_brand'] ?: 'Card')) ?>
                                    ending in <?= htmlspecialchars($profile['card_last4']) ?>
                                    <?php if ($profile['card_exp_month'] && $profile['card_exp_year']): ?>
                                        · exp <?= (int) $profile['card_exp_month'] ?>/<?= (int) $profile['card_exp_year'] ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                            <label class="profile-field">
                                <span class="profile-field__label">Name on card</span>
                                <input type="text" name="card_holder_name" class="profile-field__input"
                                       value="<?= htmlspecialchars((string) ($profile['card_holder_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </label>
                            <label class="profile-field">
                                <span class="profile-field__label">Card number</span>
                                <input type="text" name="card_number" class="profile-field__input" inputmode="numeric"
                                       placeholder="Enter only to update — we save last 4 digits"
                                       autocomplete="cc-number" maxlength="19">
                            </label>
                            <div class="profile-form__row">
                                <label class="profile-field profile-form__col">
                                    <span class="profile-field__label">Exp. month</span>
                                    <input type="number" name="card_exp_month" class="profile-field__input" min="1" max="12" placeholder="MM"
                                           value="<?= $profile['card_exp_month'] ? (int) $profile['card_exp_month'] : '' ?>">
                                </label>
                                <label class="profile-field profile-form__col">
                                    <span class="profile-field__label">Exp. year</span>
                                    <input type="number" name="card_exp_year" class="profile-field__input" min="<?= (int) date('Y') ?>" max="2099" placeholder="YYYY"
                                           value="<?= $profile['card_exp_year'] ? (int) $profile['card_exp_year'] : '' ?>">
                                </label>
                            </div>
                            <p class="profile-field__hint profile-field__hint--warn">We never store your full card number or CVV.</p>
                        </fieldset>

                        <label class="profile-field">
                            <span class="profile-field__label">Preferred checkout payment</span>
                            <select name="preferred_payment" class="profile-field__input">
                                <option value="">No preference</option>
                                <?php
                                $pref = $profile['preferred_payment'] ?? '';
                                foreach (['cod' => 'Cash on delivery', 'gcash' => 'GCash', 'bank' => 'Bank transfer', 'card' => 'Credit / debit card'] as $val => $lab):
                                    ?>
                                    <option value="<?= $val ?>"<?= $pref === $val ? ' selected' : '' ?>><?= htmlspecialchars($lab) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <button type="submit" class="profile-btn profile-btn--primary">Save payment details</button>
                    </form>
                </section>

                <section class="profile-card">
                    <h2 class="profile-card__title">Change password</h2>
                    <form method="post" class="profile-form">
                        <input type="hidden" name="action" value="password">
                        <label class="profile-field">
                            <span class="profile-field__label">Current password</span>
                            <input type="password" name="current_password" class="profile-field__input" required autocomplete="current-password">
                        </label>
                        <label class="profile-field">
                            <span class="profile-field__label">New password</span>
                            <input type="password" name="new_password" class="profile-field__input" required minlength="6" autocomplete="new-password">
                        </label>
                        <label class="profile-field">
                            <span class="profile-field__label">Confirm new password</span>
                            <input type="password" name="confirm_password" class="profile-field__input" required minlength="6" autocomplete="new-password">
                        </label>
                        <button type="submit" class="profile-btn profile-btn--secondary">Update password</button>
                    </form>
                </section>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/views/footer.php'; ?>
