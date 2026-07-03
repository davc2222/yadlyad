<?php
require_once '../../includes/db.php';

header('Content-Type: text/html; charset=utf-8');

$q = trim($_GET['q'] ?? '');
$manufacturer_id = (int) ($_GET['manufacturer_id'] ?? 0);
$model_id = (int) ($_GET['model_id'] ?? 0);
$year_from = (int) ($_GET['year_from'] ?? 0);
$year_to = (int) ($_GET['year_to'] ?? 0);
$price_to = (int) ($_GET['price_to'] ?? 0);
$km_to = (int) ($_GET['km_to'] ?? 0);
$hand_to = (int) ($_GET['hand_to'] ?? 0);

$where = ["a.status IN ('pending','active')", "a.is_deleted = 0"];
$params = [];

if ($q !== '') {
    $where[] = "(m.name LIKE ? OR cm.name LIKE ? OR a.title LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if ($manufacturer_id > 0) {
    $where[] = "a.manufacturer_id = ?";
    $params[] = $manufacturer_id;
}
if ($model_id > 0) {
    $where[] = "a.model_id = ?";
    $params[] = $model_id;
}
if ($year_from > 0) {
    $where[] = "a.year >= ?";
    $params[] = $year_from;
}
if ($year_to > 0) {
    $where[] = "a.year <= ?";
    $params[] = $year_to;
}
if ($price_to > 0) {
    $where[] = "a.price <= ?";
    $params[] = $price_to;
}
if ($km_to > 0) {
    $where[] = "a.km <= ?";
    $params[] = $km_to;
}
if ($hand_to > 0) {
    $where[] = "a.hand <= ?";
    $params[] = $hand_to;
}

$sql = "
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
WHERE " . implode(' AND ', $where) . "
ORDER BY a.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ads = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$ads): ?>
    <div class="empty-state">לא נמצאו רכבים מתאימים.</div>
<?php else: ?>
    <?php foreach ($ads as $ad): ?>
        <a class="vehicle-card" href="/vehicle/view.php?id=<?= (int) $ad['id'] ?>">
            <div class="vehicle-card-image">
                <?php if (!empty($ad['image_path'])): ?>
                    <img src="<?= htmlspecialchars($ad['image_path']) ?>" alt="">
                <?php else: ?>
                    <div class="no-image">🚗</div>
                <?php endif; ?>

                <?php if (!empty($ad['year'])): ?>
                    <span class="year-badge">
                        <?= (int) $ad['year'] ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="vehicle-card-body">
                <h2>
                    <?= htmlspecialchars(trim(($ad['maker_name'] ?? '') . ' ' . ($ad['model_name'] ?? ''))) ?>
                </h2>

                <div class="vehicle-meta">
                    <span>📅
                        <?= htmlspecialchars($ad['year'] ?? '') ?>
                    </span>
                    <span>🤝 יד
                        <?= htmlspecialchars($ad['hand'] ?? '') ?>
                    </span>
                    <span>🛣
                        <?= number_format((int) $ad['km']) ?> ק״מ
                    </span>
                </div>

                <div class="vehicle-meta">
                    <?php if (!empty($ad['gearbox_name'])): ?><span>⚙
                            <?= htmlspecialchars($ad['gearbox_name']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($ad['fuel_name'])): ?><span>⛽
                            <?= htmlspecialchars($ad['fuel_name']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="vehicle-price">
                    ₪
                    <?= number_format((int) $ad['price']) ?>
                    <?php if ((int) $ad['is_price_flexible'] === 1): ?><small>גמיש</small>
                    <?php endif; ?>
                </div>

                <div class="vehicle-card-footer">
                    <span>👁
                        <?= number_format((int) $ad['views']) ?> צפיות
                    </span>
                    <span>לפרטים</span>
                </div>
            </div>
        </a>
    <?php endforeach; ?>
<?php endif; ?>