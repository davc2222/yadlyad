

<?php
require_once '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

function ajaxEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$q = trim($_GET['q'] ?? '');
$manufacturerId = (int) ($_GET['manufacturer_id'] ?? 0);
$modelId = (int) ($_GET['model_id'] ?? 0);
$regionId = (int) ($_GET['region_id'] ?? 0);
$cityId = (int) ($_GET['city_id'] ?? 0);
$yearFrom = (int) ($_GET['year_from'] ?? 0);
$yearTo = (int) ($_GET['year_to'] ?? 0);
$priceTo = (int) ($_GET['price_to'] ?? 0);
$kmTo = (int) ($_GET['km_to'] ?? 0);
$handTo = (int) ($_GET['hand_to'] ?? 0);

$where = [
    "a.status = 'active'",
    "a.is_deleted = 0"
];

$params = [];

if ($q !== '') {
    $where[] = "(
        m.name LIKE ?
        OR cm.name LIKE ?
        OR a.title LIKE ?
        OR a.description LIKE ?
        OR r.name LIKE ?
        OR c.name LIKE ?
    )";

    $like = '%' . $q . '%';

    array_push(
        $params,
        $like,
        $like,
        $like,
        $like,
        $like,
        $like
    );
}

if ($manufacturerId > 0) {
    $where[] = "a.manufacturer_id = ?";
    $params[] = $manufacturerId;
}

if ($modelId > 0) {
    $where[] = "a.model_id = ?";
    $params[] = $modelId;
}

if ($regionId > 0) {
    $where[] = "a.region_id = ?";
    $params[] = $regionId;
}

if ($cityId > 0) {
    $where[] = "a.city_id = ?";
    $params[] = $cityId;
}

if ($yearFrom > 0) {
    $where[] = "a.year >= ?";
    $params[] = $yearFrom;
}

if ($yearTo > 0) {
    $where[] = "a.year <= ?";
    $params[] = $yearTo;
}

if ($priceTo > 0) {
    $where[] = "a.price <= ?";
    $params[] = $priceTo;
}

if ($kmTo > 0) {
    $where[] = "a.km <= ?";
    $params[] = $kmTo;
}

if ($handTo > 0) {
    $where[] = "a.hand <= ?";
    $params[] = $handTo;
}

$sql = "
    SELECT
        a.id,
        a.price,
        a.year,
        a.km,
        a.hand,
        a.views,
        a.is_price_flexible,
        m.name AS maker_name,
        cm.name AS model_name,
        g.name AS gearbox_name,
        f.name AS fuel_name,
        r.name AS region_name,
        c.name AS city_name,
        (
            SELECT image_path
            FROM vehicle_images
            WHERE ad_id = a.id
            ORDER BY is_main DESC, sort_order ASC, id ASC
            LIMIT 1
        ) AS image_path
    FROM vehicle_ads a
    LEFT JOIN car_makers m
        ON m.id = a.manufacturer_id
    LEFT JOIN car_models cm
        ON cm.id = a.model_id
    LEFT JOIN gearboxes g
        ON g.id = a.gearbox_id
    LEFT JOIN fuel_types f
        ON f.id = a.fuel_type_id
    LEFT JOIN regions r
        ON r.id = a.region_id
    LEFT JOIN cities c
        ON c.id = a.city_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY a.id DESC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    ?>

    <?php if (!$ads): ?>

        <div class="empty-state">
            לא נמצאו מודעות מתאימות.
        </div>

    <?php else: ?>

        <div class="vehicle-grid">

            <?php foreach ($ads as $ad): ?>

                <a class="vehicle-card" href="/vehicle/view.php?id=<?= (int) $ad['id'] ?>">

                    <div class="vehicle-card-image">

                        <?php if (!empty($ad['image_path'])): ?>

                            <img src="<?= ajaxEscape($ad['image_path']) ?>" alt="" loading="lazy">

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
                            <?= ajaxEscape(
                                trim(
                                    ($ad['maker_name'] ?? '') .
                                    ' ' .
                                    ($ad['model_name'] ?? '')
                                )
                            ) ?>
                        </h2>

                        <div class="vehicle-meta">

                            <?php if (!empty($ad['region_name'])): ?>
                                <span>
                                    📍 <?= ajaxEscape($ad['region_name']) ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($ad['city_name'])): ?>
                                <span>
                                    <?= ajaxEscape($ad['city_name']) ?>
                                </span>
                            <?php endif; ?>

                        </div>

                        <div class="vehicle-meta">

                            <span>
                                📅 <?= ajaxEscape($ad['year'] ?? '') ?>
                            </span>

                            <span>
                                🤝 יד <?= ajaxEscape($ad['hand'] ?? '') ?>
                            </span>

                            <span>
                                🛣 <?= number_format((int) ($ad['km'] ?? 0)) ?> ק״מ
                            </span>

                        </div>

                        <div class="vehicle-meta">

                            <?php if (!empty($ad['gearbox_name'])): ?>
                                <span>
                                    ⚙ <?= ajaxEscape($ad['gearbox_name']) ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($ad['fuel_name'])): ?>
                                <span>
                                    ⛽ <?= ajaxEscape($ad['fuel_name']) ?>
                                </span>
                            <?php endif; ?>

                        </div>

                        <div class="vehicle-price">

                            <?php if (!empty($ad['price'])): ?>

                                ₪<?= number_format((int) $ad['price']) ?>

                            <?php else: ?>

                                מחיר לא צוין

                            <?php endif; ?>

                            <?php if ((int) ($ad['is_price_flexible'] ?? 0) === 1): ?>

                                <small>גמיש</small>

                            <?php endif; ?>

                        </div>

                        <div class="vehicle-card-footer">

                            <span>
                                👁 <?= number_format((int) ($ad['views'] ?? 0)) ?> צפיות
                            </span>

                            <span>לפרטים</span>

                        </div>

                    </div>

                </a>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <?php
    $html = ob_get_clean();

    echo json_encode(
        [
            'success' => true,
            'count' => count($ads),
            'html' => $html
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

} catch (Throwable $e) {
    http_response_code(500);

    error_log(
        'Vehicle AJAX search error: ' .
        $e->getMessage()
    );

    echo json_encode(
        [
            'success' => false,
            'count' => 0,
            'html' => '<div class="empty-state">אירעה שגיאה בחיפוש.</div>',
            'error' => 'Vehicle search failed'
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
}