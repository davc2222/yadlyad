<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/header.php';
?>
<link rel="stylesheet" href="/vehicle/css/vehicle.css">

<?php
function e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function hasVal($v)
{
    return $v !== null && $v !== '' && $v !== '0000-00-00';
}

function fmtDate($date)
{
    return hasVal($date) ? date('d/m/Y', strtotime($date)) : '';
}

function fmtMonthYear($month, $year)
{
    if (!hasVal($month) || !hasVal($year)) {
        return '';
    }

    return str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '/' . $year;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    echo "<main class='vehicle-view'><h1>מודעה לא נמצאה</h1></main>";
    require_once '../includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        va.*,
        cm.name AS manufacturer_name,
        cmo.name AS model_name,
        vcg.name AS vehicle_category_name,
        bt.name AS body_type_name,
        ft.name AS fuel_type_name,
        vc.name AS color_name,
        ot.name AS ownership_name,
        cond.name AS condition_name,
        dt.name AS drive_type_name
    FROM vehicle_ads va
    LEFT JOIN car_makers cm ON cm.id = va.manufacturer_id
    LEFT JOIN car_models cmo ON cmo.id = va.model_id
    LEFT JOIN vehicle_categories vcg ON vcg.id = va.vehicle_category_id
    LEFT JOIN vehicle_body_types bt ON bt.id = va.body_type_id
    LEFT JOIN fuel_types ft ON ft.id = va.fuel_type_id
    LEFT JOIN vehicle_colors vc ON vc.id = va.color_id
    LEFT JOIN ownership_types ot ON ot.id = va.ownership_type_id
    LEFT JOIN vehicle_conditions cond ON cond.id = va.condition_id
    LEFT JOIN vehicle_drive_types dt ON dt.id = va.drive_type_id
    WHERE va.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$ad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ad) {
    echo "<main class='vehicle-view'><h1>מודעה לא נמצאה</h1></main>";
    require_once '../includes/footer.php';
    exit;
}

$imgStmt = $pdo->prepare("
    SELECT *
    FROM vehicle_images
    WHERE ad_id = ?
    ORDER BY is_main DESC, sort_order ASC, id ASC
");
$imgStmt->execute([$id]);
$images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

$featuresMap = [
    'has_abs' => 'ABS',
    'has_esp' => 'ESP',
    'has_airbags' => 'כריות אוויר',
    'has_reverse_camera' => 'מצלמת רוורס',
    'has_parking_sensors' => 'חיישני רוורס',
    'has_sunroof' => 'גג נפתח',
    'has_multimedia' => 'מולטימדיה',
    'has_navigation' => 'ניווט',
    'has_cruise_control' => 'בקרת שיוט',
    'has_alloy_wheels' => 'חישוקי מגנזיום',
    'has_leather_seats' => 'מושבי עור',
    'has_android_auto' => 'Android Auto',
    'has_apple_carplay' => 'Apple CarPlay',
];

$features = [];
foreach ($featuresMap as $field => $label) {
    if (!empty($ad[$field])) {
        $features[] = $label;
    }
}

$details = [
    ['יצרן', $ad['manufacturer_name'] ?? ''],
    ['דגם', $ad['model_name'] ?? ''],
    ['קטגוריה', $ad['vehicle_category_name'] ?? ''],
    ['מרכב', $ad['body_type_name'] ?? ''],
    ['שנה', $ad['year'] ?? ''],
    ['חודש עלייה', fmtMonthYear($ad['road_month'] ?? '', $ad['year'] ?? '')],
    ['יד', $ad['hand'] ?? ''],
    ['ק״מ', hasVal($ad['km'] ?? '') ? number_format((float) $ad['km']) : ''],
    ['דלק', $ad['fuel_type_name'] ?? ''],
    ['נפח מנוע', $ad['engine_volume'] ?? ''],
    ['הנעה', $ad['drive_type_name'] ?? ''],
    ['צבע', $ad['color_name'] ?? ''],
    ['בעלות', $ad['ownership_name'] ?? ''],
    ['מצב', $ad['condition_name'] ?? ''],
    ['טסט עד', fmtDate($ad['test_until'] ?? '')],
    ['דלתות', $ad['doors'] ?? ''],
    ['מושבים', $ad['seats'] ?? ''],
    ['פורסם', fmtDate($ad['created_at'] ?? '')],
];

$phoneClean = preg_replace('/[^0-9]/', '', $ad['phone'] ?? '');
$whatsappPhone = '972' . ltrim($phoneClean, '0');

$title = trim(($ad['manufacturer_name'] ?? '') . ' ' . ($ad['model_name'] ?? '') . ' ' . ($ad['year'] ?? ''));
if ($title === '') {
    $title = $ad['title'] ?? 'רכב למכירה';
}

$mainImage = $images[0]['image_path'] ?? '';
?>

<main class="vehicle-view">

    <section class="vehicle-hero-card">

        <div class="vehicle-gallery">
            <div class="vehicle-main-photo-wrap">
                <?php if ($mainImage): ?>
                    <img id="mainVehicleImage" class="vehicle-main-image" src="<?= e($mainImage) ?>" alt="">
                <?php else: ?>
                    <div class="vehicle-placeholder">אין תמונה</div>
                <?php endif; ?>

                <div class="vehicle-photo-count">📷 <?= count($images) ?: 0 ?></div>
                <button class="vehicle-fav-top" type="button">♡</button>
            </div>

            <?php if (count($images) > 1): ?>
                <div class="vehicle-thumbs">
                    <?php foreach ($images as $img): ?>
                        <img src="<?= e($img['image_path']) ?>"
                            onclick="document.getElementById('mainVehicleImage').src=this.src" alt="">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="vehicle-summary">
            <h1><?= e($title) ?></h1>

            <div class="vehicle-price">
                <?= !empty($ad['price']) ? number_format((float) $ad['price']) . ' ₪' : 'מחיר לא צוין' ?>
            </div>

            <div class="vehicle-tags">
                <?php if (hasVal($ad['year'] ?? '')): ?>
                    <span><?= e($ad['year']) ?></span>
                <?php endif; ?>

                <?php if (hasVal($ad['km'] ?? '')): ?>
                    <span><?= number_format((float) $ad['km']) ?> ק״מ</span>
                <?php endif; ?>

                <?php if (hasVal($ad['hand'] ?? '')): ?>
                    <span>יד <?= e($ad['hand']) ?></span>
                <?php endif; ?>

                <?php if (hasVal($ad['fuel_type_name'] ?? '')): ?>
                    <span><?= e($ad['fuel_type_name']) ?></span>
                <?php endif; ?>
            </div>

            <div class="vehicle-small-actions">
                <button type="button">♡ שמור מודעה</button>
                <button type="button">↗ שתף</button>
                <button type="button">⚠ דווח על מודעה</button>
            </div>
        </div>

    </section>

    <section class="vehicle-main-grid">

        <section class="vehicle-card vehicle-details-card">
            <h2>פרטי הרכב</h2>

            <div class="vehicle-details-list">
                <?php foreach ($details as [$label, $value]): ?>
                    <?php if (hasVal($value)): ?>
                        <div class="vehicle-detail-line">
                            <span><?= e($label) ?></span>
                            <strong><?= e($value) ?></strong>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="vehicle-card vehicle-contact-card">
            <h2>תיאור המודעה</h2>

            <div class="vehicle-contact-description">
                <?php if (!empty($ad['description'])): ?>
                    <?= nl2br(e($ad['description'])) ?>
                <?php else: ?>
                    לא נוסף תיאור למודעה.
                <?php endif; ?>
            </div>

            <div class="vehicle-contact-buttons-bottom">
                <?php if (!empty($ad['phone']) && !empty($ad['allow_whatsapp'])): ?>
                    <a class="vehicle-whatsapp-btn" target="_blank" href="https://wa.me/<?= e($whatsappPhone) ?>">
                        WhatsApp
                    </a>
                <?php endif; ?>

                <?php if (!empty($ad['phone']) && empty($ad['hide_phone'])): ?>
                    <a class="vehicle-phone-btn" href="tel:<?= e($phoneClean) ?>">
                        ☎ <?= e($ad['phone']) ?>
                    </a>
                <?php endif; ?>
            </div>
        </aside>

    </section>

    <?php if ($features): ?>
        <section class="vehicle-card vehicle-features-strip">
            <?php foreach ($features as $feature): ?>
                <span><?= e($feature) ?></span>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

</main>

<?php require_once '../includes/footer.php'; ?>