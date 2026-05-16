<?php
/**
 * @return array{class: string, label: string}
 */
function rider_delivery_status_meta(string $status): array
{
    return match ($status) {
        'picked_up' => ['class' => 'rider-pill--picked', 'label' => 'Picked up'],
        'on_the_way' => ['class' => 'rider-pill--way', 'label' => 'On the way'],
        'delivered' => ['class' => 'rider-pill--done', 'label' => 'Delivered'],
        default => ['class' => 'rider-pill--assigned', 'label' => 'Assigned'],
    };
}

/**
 * @return array{class: string, label: string}
 */
function rider_order_status_meta(string $status): array
{
    return match (strtolower($status)) {
        'preparing' => ['class' => 'rider-pill--prep', 'label' => 'Preparing'],
        'delivering' => ['class' => 'rider-pill--way', 'label' => 'Delivering'],
        'completed' => ['class' => 'rider-pill--done', 'label' => 'Completed'],
        'cancelled' => ['class' => 'rider-pill--muted', 'label' => 'Cancelled'],
        default => ['class' => 'rider-pill--pending', 'label' => 'Pending'],
    };
}
