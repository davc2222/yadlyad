<?php
$price = !empty($ad['price']) ? number_format((int) $ad['price']) . ' ₪' : 'לא צוין מחיר';
$title = trim(($ad['maker_name'] ?? '') . ' ' . ($ad['model_name'] ?? ''));

if ($title === '') {
    $title = $ad['title'] ?? 'מודעת רכב';
}
?>

<div class="vehicle-card">

    <div class="vehicle-card-image">
        <span>אין תמונה</span>
    </div>

    <div class="vehicle-card-body">

        <div class="vehicle-card-price">
            <?= htmlspecialchars($price) ?>
        </div>

        <h2>
            <?= htmlspecialchars($title) ?>
        </h2>

        <div class="vehicle-card-meta">
            <?php if (!empty($ad['year'])): ?>
                <span>
                    <?= (int) $ad['year'] ?>
                </span>
            <?php endif; ?>

            <?php if (!empty($ad['km'])): ?>
                <span>
                    <?= number_format((int) $ad['km']) ?> ק״מ
                </span>
            <?php endif; ?>

            <?php if (!empty($ad['hand'])): ?>
                <span>יד
                    <?= (int) $ad['hand'] ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="vehicle-card-meta">
            <?php if (!empty($ad['gearbox_name'])): ?>
                <span>
                    <?= htmlspecialchars($ad['gearbox_name']) ?>
                </span>
            <?php endif; ?>

            <?php if (!empty($ad['fuel_name'])): ?>
                <span>
                    <?= htmlspecialchars($ad['fuel_name']) ?>
                </span>
            <?php endif; ?>
        </div>

        <a class="vehicle-card-link" href="/vehicle/view.php?id=<?= (int) $ad['id'] ?>">
            פרטים נוספים
        </a>

    </div>

</div>