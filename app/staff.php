<?php

/**
 * Staff = platform admin (role admin) or kitchen / shop manager (role restaurant).
 */

function kk_staff_role(): ?string
{
    return $_SESSION['user']['role'] ?? null;
}

function kk_is_staff(): bool
{
    $role = kk_staff_role();
    return $role === 'admin' || $role === 'restaurant';
}

/** Platform-wide admin (manages all shops). */
function kk_staff_is_platform(): bool
{
    return kk_staff_role() === 'admin';
}

/** Shop-bound manager (kitchen / restaurant account). */
function kk_staff_is_shop_manager(): bool
{
    return kk_staff_role() === 'restaurant';
}

/** Assigned shop id for shop managers; null for platform admin. */
function kk_staff_shop_id(): ?int
{
    if (!kk_staff_is_shop_manager()) {
        return null;
    }
    $id = (int) ($_SESSION['user']['restaurant_id'] ?? 0);
    return $id > 0 ? $id : null;
}

function kk_staff_can_access_shop(int $shopId): bool
{
    if (kk_staff_is_platform()) {
        return true;
    }
    $mine = kk_staff_shop_id();
    return $mine !== null && $mine === $shopId;
}

/** Redirect if shop manager has no shop linked. */
function kk_staff_require_shop(): int
{
    $shopId = kk_staff_shop_id();
    if ($shopId === null) {
        header('Location: ' . app_url('admin/dashboard.php'));
        exit;
    }
    return $shopId;
}

/** Redirect unless user may manage this shop. */
function kk_staff_assert_shop(int $shopId): void
{
    if (!kk_staff_can_access_shop($shopId)) {
        header('Location: ' . app_url('admin/dashboard.php'));
        exit;
    }
}
