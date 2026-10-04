<?php
/** Badge status pesanan & pembayaran. */

/** @param string $status */
function statusBadge(string $status): void
{
    printf(
        '<span class="badge %s">%s</span>',
        e(Status::color($status)),
        e(Status::label($status))
    );
}

/** @param string $status */
function paymentBadge(string $status): void
{
    printf(
        '<span class="badge %s">%s</span>',
        e(Status::PAYMENT_COLORS[$status] ?? 'badge-gray'),
        e(Status::PAYMENT_LABELS[$status] ?? $status)
    );
}

/** @param string $method */
function paymentMethodLabel(string $method): string
{
    return Status::PAYMENT_METHODS[$method] ?? $method;
}

/** Ikon kategori produk. */
function categoryIcon(?string $category): string
{
    return match ($category) {
        'Sepatu'   => '👟',
        'Tas'      => '🎒',
        'Baju'     => '👕',
        'Kosmetik' => '💄',
        'Makanan'  => '🍜',
        'Oleh-oleh'=> '🍪',
        default    => '📦',
    };
}

function renderImage(?string $imageUrl, string $fallbackEmoji = '📦'): void
{
    if ($imageUrl && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        printf('<img src="%s" alt="" onerror="this.style.display=\'none\'">', e($imageUrl));
    } else {
        echo e($fallbackEmoji);
    }
}

/** Baris kosong (empty state). */
function emptyState(string $message, string $icon = '📭', string $action = ''): void
{
    echo '<div class="empty-state"><div class="icon">' . $icon . '</div><p>' . e($message) . '</p>';
    if ($action !== '') {
        echo $action;
    }
    echo '</div>';
}

/** Timeline progres status pesanan. */
function orderProgress(string $status): void
{
    if (in_array($status, [Status::DIBATALKAN], true)) {
        printf('<div class="alert alert-danger">Pesanan ini telah dibatalkan.</div>');
        return;
    }

    $currentIndex = array_search($status, Status::FLOW, true);

    echo '<div class="timeline">';
    foreach (Status::FLOW as $i => $step) {
        $class = $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'current' : '');
        echo '<li class="' . $class . '">';
        echo '<div class="timeline-title">' . e(Status::label($step)) . '</div>';
        echo '</li>';
    }
    echo '</div>';
}