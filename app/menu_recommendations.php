<?php

require_once __DIR__ . '/menu_ops.php';

if (!function_exists('app_url')) {
    require_once __DIR__ . '/url.php';
}

/**
 * Tags used to match weather, mood, and menu items for Crispy Picks.
 *
 * @return list<string>
 */
function kk_menu_item_recommendation_tags(string $name, string $category, string $description): array
{
    $blob = strtolower($name . ' ' . $category . ' ' . $description);
    $tags = [];

    $rules = [
        'chicken' => '/\b(chicken|wings|drumstick)\b/',
        'fried' => '/\b(fried|crispy|crunch)\b/',
        'rice' => '/\b(rice|with rice)\b/',
        'pares' => '/\bpares\b/',
        'soup' => '/\b(broth|soup|pares)\b/',
        'warm' => '/\b(steamed|hot|slow-cooked|broth|siopao|pares|savory)\b/',
        'comfort' => '/\b(pares|rice|siopao|hearty)\b/',
        'dimsum' => '/\b(sioma|lumpia|kikiam|tempura|ngohiong|bola|siopao|dim\s*sum)\b/',
        'snack' => '/\b(lumpia|kikiam|cheese|fries|nuts|sticks|tempura|street|skewer|roll)\b/',
        'siomai' => '/\bsioma/i',
        'lumpia' => '/\blumpia/i',
        'light' => '/\b(3 pcs|snack|nuts|small|bite)\b/i',
        'street' => '/\b(street|kikiam|cheese sticks|fries|tempura)\b/',
        'share' => '/\b(3 pcs|sticks|fries|lumpia|bola)\b/',
    ];

    foreach ($rules as $tag => $pattern) {
        if (preg_match($pattern, $blob)) {
            $tags[] = $tag;
        }
    }

    $cat = strtolower($category);
    if ($cat === 'dim sum') {
        $tags[] = 'dimsum';
        $tags[] = 'snack';
    } elseif ($cat === 'chicken') {
        $tags[] = 'chicken';
        $tags[] = 'fried';
    } elseif ($cat === 'rice meals') {
        $tags[] = 'rice';
        $tags[] = 'comfort';
    } elseif ($cat === 'sides & snacks') {
        $tags[] = 'snack';
        $tags[] = 'street';
    }

    return array_values(array_unique($tags));
}

/**
 * All active menu items for client-side weather / mood recommendations.
 *
 * @return list<array<string, mixed>>
 */
function kk_menu_catalog_for_picks(PDO $pdo): array
{
    kk_menu_ensure_schema($pdo);

    $stmt = $pdo->query(
        'SELECT m.id, m.restaurant_id, m.name, m.description, m.price, m.image, m.category,
                r.name AS shop_name
         FROM menus m
         INNER JOIN restaurants r ON r.id = m.restaurant_id
         WHERE m.is_active = 1 AND r.is_active = 1
         ORDER BY m.restaurant_id ASC, m.id ASC'
    );

    $catalog = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $category = kk_menu_resolve_category((string) $row['name'], $row['category'] ?? null);
        $description = (string) ($row['description'] ?? '');

        $catalog[] = [
            'id' => (int) $row['id'],
            'shopId' => (int) $row['restaurant_id'],
            'shopName' => (string) $row['shop_name'],
            'name' => (string) $row['name'],
            'description' => $description,
            'price' => (float) $row['price'],
            'image' => (string) $row['image'],
            'category' => $category,
            'tags' => kk_menu_item_recommendation_tags((string) $row['name'], $category, $description),
        ];
    }

    return $catalog;
}
