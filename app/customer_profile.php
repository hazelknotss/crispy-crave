<?php

function kk_customer_profile_ensure_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS customer_profiles (
            user_id INT NOT NULL PRIMARY KEY,
            phone VARCHAR(24) NULL,
            gcash_number VARCHAR(20) NULL,
            gcash_account_name VARCHAR(100) NULL,
            bank_name VARCHAR(80) NULL,
            bank_account_name VARCHAR(100) NULL,
            bank_account_number VARCHAR(40) NULL,
            card_holder_name VARCHAR(100) NULL,
            card_last4 CHAR(4) NULL,
            card_brand VARCHAR(24) NULL,
            card_exp_month TINYINT UNSIGNED NULL,
            card_exp_year SMALLINT UNSIGNED NULL,
            preferred_payment ENUM('cod','gcash','bank','card') NULL DEFAULT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function kk_customer_profile_get(PDO $pdo, int $userId): array
{
    kk_customer_profile_ensure_schema($pdo);
    $stmt = $pdo->prepare('SELECT * FROM customer_profiles WHERE user_id = ?');
    $stmt->execute([$userId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: [
        'user_id' => $userId,
        'phone' => null,
        'gcash_number' => null,
        'gcash_account_name' => null,
        'bank_name' => null,
        'bank_account_name' => null,
        'bank_account_number' => null,
        'card_holder_name' => null,
        'card_last4' => null,
        'card_brand' => null,
        'card_exp_month' => null,
        'card_exp_year' => null,
        'preferred_payment' => null,
    ];
}

function kk_customer_profile_mask_account(?string $number): string
{
    $n = preg_replace('/\D/', '', (string) $number);
    if ($n === '') {
        return '';
    }
    if (strlen($n) <= 4) {
        return $n;
    }

    return str_repeat('•', max(4, strlen($n) - 4)) . substr($n, -4);
}

function kk_customer_card_brand_from_number(string $digits): string
{
    if ($digits === '') {
        return '';
    }
    if ($digits[0] === '4') {
        return 'Visa';
    }
    if (preg_match('/^5[1-5]/', $digits) || preg_match('/^2[2-7]/', $digits)) {
        return 'Mastercard';
    }

    return 'Card';
}

function kk_customer_profile_save(PDO $pdo, int $userId, array $data): void
{
    kk_customer_profile_ensure_schema($pdo);

    $existing = kk_customer_profile_get($pdo, $userId);

    $cardLast4 = $existing['card_last4'];
    $cardBrand = $existing['card_brand'];
    $cardExpMonth = $existing['card_exp_month'];
    $cardExpYear = $existing['card_exp_year'];
    $cardHolder = trim((string) ($data['card_holder_name'] ?? $existing['card_holder_name'] ?? ''));

    $cardDigits = preg_replace('/\D/', '', (string) ($data['card_number'] ?? ''));
    if ($cardDigits !== '') {
        $cardLast4 = substr($cardDigits, -4);
        $cardBrand = kk_customer_card_brand_from_number($cardDigits);
    }

    if (isset($data['card_exp_month']) && $data['card_exp_month'] !== '') {
        $cardExpMonth = (int) $data['card_exp_month'];
    }
    if (isset($data['card_exp_year']) && $data['card_exp_year'] !== '') {
        $cardExpYear = (int) $data['card_exp_year'];
    }

    $bankNumber = trim((string) ($data['bank_account_number'] ?? ''));
    if ($bankNumber === '') {
        $bankNumber = (string) ($existing['bank_account_number'] ?? '');
    }

    $preferred = (string) ($data['preferred_payment'] ?? '');
    if (!in_array($preferred, ['cod', 'gcash', 'bank', 'card'], true)) {
        $preferred = $existing['preferred_payment'];
    }

    $stmt = $pdo->prepare("
        INSERT INTO customer_profiles (
            user_id, phone, gcash_number, gcash_account_name,
            bank_name, bank_account_name, bank_account_number,
            card_holder_name, card_last4, card_brand, card_exp_month, card_exp_year,
            preferred_payment
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            phone = VALUES(phone),
            gcash_number = VALUES(gcash_number),
            gcash_account_name = VALUES(gcash_account_name),
            bank_name = VALUES(bank_name),
            bank_account_name = VALUES(bank_account_name),
            bank_account_number = VALUES(bank_account_number),
            card_holder_name = VALUES(card_holder_name),
            card_last4 = VALUES(card_last4),
            card_brand = VALUES(card_brand),
            card_exp_month = VALUES(card_exp_month),
            card_exp_year = VALUES(card_exp_year),
            preferred_payment = VALUES(preferred_payment)
    ");

    $stmt->execute([
        $userId,
        trim((string) ($data['phone'] ?? '')) ?: null,
        trim((string) ($data['gcash_number'] ?? '')) ?: null,
        trim((string) ($data['gcash_account_name'] ?? '')) ?: null,
        trim((string) ($data['bank_name'] ?? '')) ?: null,
        trim((string) ($data['bank_account_name'] ?? '')) ?: null,
        $bankNumber !== '' ? $bankNumber : null,
        $cardHolder !== '' ? $cardHolder : null,
        $cardLast4 ?: null,
        $cardBrand ?: null,
        $cardExpMonth ?: null,
        $cardExpYear ?: null,
        $preferred ?: null,
    ]);
}
