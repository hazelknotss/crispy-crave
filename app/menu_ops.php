<?php

/**
 * Menu fields for POS (categories).
 */
function kk_menu_ensure_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    try {
        $pdo->exec('ALTER TABLE menus ADD COLUMN category VARCHAR(80) NULL DEFAULT NULL');
    } catch (PDOException $e) {
        // column exists
    }
}

/** @return list<string> */
function kk_menu_category_options(): array
{
    return [
        'General',
        'Chicken',
        'Rice meals',
        'Sides & snacks',
        'Dim sum',
        'Drinks',
    ];
}

function kk_menu_resolve_category(string $name, ?string $stored): string
{
    $stored = trim((string) $stored);
    if ($stored !== '') {
        return $stored;
    }

    $n = strtolower($name);
    if (preg_match('/\b(chicken|wings|drumstick)\b/', $n)) {
        return 'Chicken';
    }
    if (preg_match('/\b(rice|pares|silog)\b/', $n)) {
        return 'Rice meals';
    }
    if (preg_match('/\b(sioma|lumpia|kikiam|tempura|ngohiong|bola|siopao|dim\s*sum)\b/', $n)) {
        return 'Dim sum';
    }
    if (preg_match('/\b(fries|cheese|nuts|snack|sticks)\b/', $n)) {
        return 'Sides & snacks';
    }
    if (preg_match('/\b(drink|juice|soda|tea|coffee|water)\b/', $n)) {
        return 'Drinks';
    }

    return 'General';
}
