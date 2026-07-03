<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$stmt = $pdo->query("
    SELECT
        a.id, a.price, a.year, a.km, a.hand, a.views, a.is_price_flexible,
        m.name AS maker_name,
        cm.name AS model_name,
        g.name AS gearbox_name,
        f.name AS fuel_name,
        (
            SELECT image_path
            FROM vehicle_images
            WHERE ad_id = a.id
            ORDER BY is_main DESC, sort_order ASC, id ASC
            LIMIT 1
        ) AS image_path
    FROM vehicle_ads a
    LEFT JOIN car_makers m ON m.id = a.manufacturer_id
    LEFT JOIN car_models cm ON cm.id = a.model_id
    LEFT JOIN gearboxes g ON g.id = a.gearbox_id
    LEFT JOIN fuel_types f ON f.id = a.fuel_type_id
    WHERE a.status IN ('pending','active')
      AND a.is_deleted = 0
    ORDER BY a.id DESC
");

$ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="/vehicle/css/vehicle_list.css">

<section class="vehicle-list-page">

    <div class="vehicle-list-header">
        <div>
            <h1>רכבים למכירה</h1>
            <p>נמצאו <?= count($ads) ?> מודעות</p>
        </div>

        <a href="/vehicle/add.php" class="add-vehicle-btn">פרסם רכב</a>
    </div>

    <?php if (!$ads): ?>
        <div class="empty-state">
            אין מודעות רכב עדיין.
        </div>
    <?php else: ?>

        <div class="vehicle-grid">

            <?php foreach ($ads as $ad): ?>
                <a class="vehicle-card" href="/vehicle/view.php?id=<?= (int) $ad['id'] ?>">

                    <div class="vehicle-card-image">
                        <?php if (!empty($ad['image_path'])): ?>
                            <img src="<?= htmlspecialchars($ad['image_path']) ?>" alt="">
                        <?php else: ?>
                            <div class="no-image">🚗</div>
                        <?php endif; ?>

                        <?php if (!empty($ad['year'])): ?>
                            <span class="year-badge"><?= (int) $ad['year'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="vehicle-card-body">

                        <h2>
                            <?= htmlspecialchars(trim(($ad['maker_name'] ?? '') . ' ' . ($ad['model_name'] ?? ''))) ?>
                        </h2>

                        <div class="vehicle-meta">
                            <span>📅 <?= htmlspecialchars($ad['year'] ?? '') ?></span>
                            <span>🤝 יד <?= htmlspecialchars($ad['hand'] ?? '') ?></span>
                            <span>🛣 <?= number_format((int) $ad['km']) ?> ק״מ</span>
                        </div>

                        <div class="vehicle-meta">
                            <?php if (!empty($ad['gearbox_name'])): ?>
                                <span>⚙ <?= htmlspecialchars($ad['gearbox_name']) ?></span>
                            <?php endif; ?>

                            <?php if (!empty($ad['fuel_name'])): ?>
                                <span>⛽ <?= htmlspecialchars($ad['fuel_name']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="vehicle-price">
                            ₪<?= number_format((int) $ad['price']) ?>
                            <?php if ((int) $ad['is_price_flexible'] === 1): ?>
                                <small>גמיש</small>
                            <?php endif; ?>
                        </div>

                        <div class="vehicle-card-footer">
                            <span>👁 <?= number_format((int) $ad['views']) ?> צפיות</span>
                            <span>לפרטים</span>
                        </div>

                    </div>

                </a>
            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<?php require_once '../includes/footer.php'; ?>