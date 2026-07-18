<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/header.php';
?>
<link rel="stylesheet" href="/vehicle/css/vehicle.css?v=20260709c">

<?php
function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function hasVal($v) {
    return $v !== null && $v !== '' && $v !== '0000-00-00';
}

function fmtDate($date) {
    return hasVal($date) ? date('d/m/Y', strtotime($date)) : '';
}

function fmtMonthYear($month, $year) {
    if (!hasVal($month) || !hasVal($year)) return '';
    return str_pad((string)$month, 2, '0', STR_PAD_LEFT) . '/' . $year;
}

function imageSrc($img) {
    if (!empty($img['image_path'])) return $img['image_path'];
    if (!empty($img['image_name'])) return '/vehicle/uploads/' . $img['image_name'];
    return '';
}

function adFlag($ad, $fields) {
    foreach ((array)$fields as $field) {
        if (!empty($ad[$field])) return true;
    }
    return false;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo "<main class='vehicle-page'><div class='vehicle-not-found'>מודעה לא נמצאה</div></main>";
    require_once '../includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        va.*,
        cm.name AS manufacturer_name,
        cmo.name AS model_name,
        vcg.name AS vehicle_category_name,
        r.name AS region_name,
        c.name AS city_name,
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
    LEFT JOIN regions r ON r.id = va.region_id
    LEFT JOIN cities c ON c.id = va.city_id
    LEFT JOIN vehicle_body_types bt ON bt.id = va.body_type_id
    LEFT JOIN fuel_types ft ON ft.id = va.fuel_type_id
    LEFT JOIN vehicle_colors vc ON vc.id = va.color_id
    LEFT JOIN ownership_types ot ON ot.id = va.ownership_type_id
    LEFT JOIN vehicle_conditions cond ON cond.id = va.condition_id
    LEFT JOIN vehicle_drive_types dt ON dt.id = va.drive_type_id
    WHERE va.id = ?
      AND va.is_deleted = 0
      AND va.status IN ('approved', 'active')
    LIMIT 1
");
$stmt->execute([$id]);
$ad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ad) {
    echo "<main class='vehicle-page'><div class='vehicle-not-found'>מודעה לא נמצאה</div></main>";
    require_once '../includes/footer.php';
    exit;
}

$pdo->prepare("UPDATE vehicle_ads SET views = views + 1 WHERE id = ?")->execute([$id]);

$imgStmt = $pdo->prepare("
    SELECT *
    FROM vehicle_images
    WHERE ad_id = ?
    ORDER BY is_main DESC, sort_order ASC, id ASC
");
$imgStmt->execute([$id]);
$images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

$galleryImages = [];
foreach ($images as $img) {
    $src = imageSrc($img);
    if ($src !== '') $galleryImages[] = $src;
}

$featuresMap = [
    ['fields' => ['sunroof', 'has_sunroof'], 'label' => 'גג נפתח'],
    ['fields' => ['reverse_camera', 'has_reverse_camera'], 'label' => 'מצלמת רוורס'],
    ['fields' => ['sensors', 'has_parking_sensors'], 'label' => 'חיישני רוורס'],
    ['fields' => ['alloy_wheels', 'has_alloy_wheels'], 'label' => 'חישוקי מגנזיום'],
    ['fields' => ['tow_bar'], 'label' => 'וו גרירה'],
    ['fields' => ['navigation', 'has_navigation'], 'label' => 'ניווט'],
    ['fields' => ['multimedia', 'has_multimedia'], 'label' => 'מולטימדיה'],
    ['fields' => ['bluetooth'], 'label' => 'Bluetooth'],
    ['fields' => ['usb'], 'label' => 'USB'],
    ['fields' => ['android_auto', 'has_android_auto'], 'label' => 'Android Auto'],
    ['fields' => ['apple_carplay', 'has_apple_carplay'], 'label' => 'Apple CarPlay'],
    ['fields' => ['cruise_control', 'has_cruise_control'], 'label' => 'בקרת שיוט'],
    ['fields' => ['adaptive_cruise'], 'label' => 'בקרת שיוט אדפטיבית'],
    ['fields' => ['lane_assist'], 'label' => 'שמירה על נתיב'],
    ['fields' => ['blind_spot'], 'label' => 'זיהוי שטח מת'],
    ['fields' => ['keyless'], 'label' => 'כניסה ללא מפתח'],
    ['fields' => ['push_start'], 'label' => 'הנעה בכפתור'],
    ['fields' => ['abs', 'has_abs'], 'label' => 'ABS'],
    ['fields' => ['esp', 'has_esp'], 'label' => 'ESP'],
    ['fields' => ['alarm'], 'label' => 'אזעקה'],
    ['fields' => ['immobilizer'], 'label' => 'אימובילייזר'],
];

$features = [];
foreach ($featuresMap as $item) {
    if (adFlag($ad, $item['fields'])) $features[] = $item['label'];
}
if (hasVal($ad['airbags'] ?? '')) {
    $features[] = 'כריות אוויר: ' . $ad['airbags'];
}

$regionValue = $ad['region_name'] ?? ($ad['region'] ?? '');
$cityValue   = $ad['city_name'] ?? ($ad['city'] ?? '');

$title = trim(($ad['manufacturer_name'] ?? '') . ' ' . ($ad['model_name'] ?? '') . ' ' . ($ad['year'] ?? ''));
if ($title === '') $title = $ad['title'] ?? 'רכב למכירה';

$details = [
    ['יצרן', $ad['manufacturer_name'] ?? ''],
    ['דגם', $ad['model_name'] ?? ''],
    ['שנה', $ad['year'] ?? ''],
    ['יד', $ad['hand'] ?? ''],
    ['ק״מ', hasVal($ad['km'] ?? '') ? number_format((float)$ad['km']) : ''],
    ['אזור', $regionValue],
    ['עיר', $cityValue],
    ['קטגוריה', $ad['vehicle_category_name'] ?? ''],
    ['מרכב', $ad['body_type_name'] ?? ''],
    ['דלק', $ad['fuel_type_name'] ?? ''],
    ['נפח מנוע', $ad['engine_volume'] ?? ''],
    ['הנעה', $ad['drive_type_name'] ?? ''],
    ['צבע', $ad['color_name'] ?? ''],
    ['בעלות', $ad['ownership_name'] ?? ''],
  
    ['עלייה לכביש', fmtMonthYear($ad['road_month'] ?? '', $ad['year'] ?? '')],
    ['טסט עד', fmtDate($ad['test_until'] ?? '')],
    ['דלתות', $ad['doors'] ?? ''],
    ['מושבים', $ad['seats'] ?? ''],
    ['מספר מודעה', $ad['id'] ?? ''],
    ['צפיות', number_format((int)($ad['views'] ?? 0) + 1)],
    ['פורסם', fmtDate($ad['created_at'] ?? '')],
];

$topDetails = [
    ['שנה', $ad['year'] ?? ''],
    ['ק״מ', hasVal($ad['km'] ?? '') ? number_format((float)$ad['km']) : ''],
    ['יד', $ad['hand'] ?? ''],
    ['דלק', $ad['fuel_type_name'] ?? ''],
    ['אזור', $regionValue],
    ['עיר', $cityValue],
];

$phoneClean = preg_replace('/[^0-9]/', '', $ad['phone'] ?? '');
$whatsappPhone = $phoneClean ? '972' . ltrim($phoneClean, '0') : '';
$mainImage = $galleryImages[0] ?? '';
?>

<main class="vehicle-page">

    <section class="vehicle-ad-shell">

        <div class="vehicle-gallery-box">
            <div class="vehicle-main-photo" id="mainPhotoWrap">
                <?php if ($mainImage): ?>
                    <img id="mainVehicleImage" src="<?= e($mainImage) ?>" alt="<?= e($title) ?>" data-index="0">
                    <?php if (count($galleryImages) > 1): ?>
                        <button type="button" class="gallery-arrow gallery-prev" id="galleryPrev">‹</button>
                        <button type="button" class="gallery-arrow gallery-next" id="galleryNext">›</button>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="vehicle-no-photo">אין תמונה</div>
                <?php endif; ?>

                <div class="photo-counter">📷 <?= count($galleryImages) ?></div>
            </div>

            <?php if (count($galleryImages) > 1): ?>
                <div class="vehicle-thumbs" id="vehicleThumbs">
                    <?php foreach ($galleryImages as $index => $src): ?>
                        <img src="<?= e($src) ?>" data-index="<?= (int)$index ?>" class="<?= $index === 0 ? 'active' : '' ?>" alt="">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <aside class="vehicle-side-box">
            <div class="vehicle-headline">
                <div class="ad-label">רכב למכירה</div>
                <h1><?= e($title) ?></h1>

                <?php if (hasVal($cityValue) || hasVal($regionValue)): ?>
                    <div class="vehicle-place">
                        <?= hasVal($cityValue) ? e($cityValue) : '' ?>
                        <?= hasVal($cityValue) && hasVal($regionValue) ? ' · ' : '' ?>
                        <?= hasVal($regionValue) ? e($regionValue) : '' ?>
                    </div>
                <?php endif; ?>

                <div class="vehicle-price">
                    <?= !empty($ad['price']) ? number_format((float)$ad['price']) . ' ₪' : 'מחיר לא צוין' ?>
                </div>
            </div>

            <div class="top-detail-grid">
                <?php foreach ($topDetails as [$label, $value]): ?>
                    <?php if (hasVal($value)): ?>
                        <div>
                            <span><?= e($label) ?></span>
                            <strong><?= e($value) ?></strong>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="contact-box">
                <h2>צור קשר</h2>

                <?php if (!empty($ad['phone']) && !empty($ad['allow_whatsapp']) && $whatsappPhone): ?>
                    <a class="btn-whatsapp" target="_blank" href="https://wa.me/<?= e($whatsappPhone) ?>">WhatsApp</a>
                <?php endif; ?>

                <?php if (!empty($ad['phone']) && empty($ad['hide_phone'])): ?>
                    <a class="btn-phone" href="tel:<?= e($phoneClean) ?>">☎ <?= e($ad['phone']) ?></a>
                <?php endif; ?>
            </div>
        </aside>

    </section>

    <section class="vehicle-lower-grid">

        <section class="vehicle-card details-card">
            <h2>פרטי הרכב</h2>
            <div class="details-table">
                <?php foreach ($details as [$label, $value]): ?>
                    <?php if (hasVal($value)): ?>
                        <div class="detail-row">
                            <span><?= e($label) ?></span>
                            <strong><?= e($value) ?></strong>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="vehicle-card description-card">
            <h2>תיאור המודעה</h2>
            <div class="description-text">
                <?php if (!empty($ad['description'])): ?>
                    <?= nl2br(e($ad['description'])) ?>
                <?php else: ?>
                    לא נוסף תיאור למודעה.
                <?php endif; ?>
            </div>

            <h2 class="features-title">אבזור</h2>
            <?php if ($features): ?>
                <div class="features-list">
                    <?php foreach ($features as $feature): ?>
                        <span>✓ <?= e($feature) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="vehicle-empty">לא צוין אבזור.</div>
            <?php endif; ?>
        </section>

    </section>

</main>

<?php if (!empty($galleryImages)): ?>
<div class="vehicle-lightbox" id="vehicleLightbox">
    <button type="button" class="lightbox-close" id="vehicleLightboxClose">×</button>
    <button type="button" class="lightbox-prev" id="vehicleLightboxPrev">‹</button>
    <img id="vehicleLightboxImg" src="" alt="<?= e($title) ?>">
    <button type="button" class="lightbox-next" id="vehicleLightboxNext">›</button>
    <div class="lightbox-counter" id="vehicleLightboxCounter"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const images = <?= json_encode(array_values($galleryImages), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const mainImage = document.getElementById('mainVehicleImage');
    const mainWrap = document.getElementById('mainPhotoWrap');
    const thumbs = document.querySelectorAll('#vehicleThumbs img');
    const galleryPrev = document.getElementById('galleryPrev');
    const galleryNext = document.getElementById('galleryNext');
    const lightbox = document.getElementById('vehicleLightbox');
    const lightboxImg = document.getElementById('vehicleLightboxImg');
    const closeBtn = document.getElementById('vehicleLightboxClose');
    const prevBtn = document.getElementById('vehicleLightboxPrev');
    const nextBtn = document.getElementById('vehicleLightboxNext');
    const counter = document.getElementById('vehicleLightboxCounter');

    let currentIndex = 0;

    function setMainImage(index) {
        if (!images[index] || !mainImage) return;
        currentIndex = index;
        mainImage.src = images[index];
        mainImage.dataset.index = index;

        thumbs.forEach(function (thumb) {
            thumb.classList.toggle('active', parseInt(thumb.dataset.index, 10) === index);
        });
    }

    function step(direction) {
        setMainImage((currentIndex + direction + images.length) % images.length);
    }

    function openLightbox(index) {
        if (!images[index]) return;
        currentIndex = index;
        lightboxImg.src = images[currentIndex];
        counter.textContent = (currentIndex + 1) + ' / ' + images.length;
        lightbox.classList.add('open');
    }

    function closeLightbox() {
        lightbox.classList.remove('open');
        lightboxImg.src = '';
    }

    function lightboxStep(direction) {
        currentIndex = (currentIndex + direction + images.length) % images.length;
        lightboxImg.src = images[currentIndex];
        counter.textContent = (currentIndex + 1) + ' / ' + images.length;
        setMainImage(currentIndex);
    }

    thumbs.forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            setMainImage(parseInt(this.dataset.index, 10));
        });
    });

    if (galleryPrev) galleryPrev.addEventListener('click', function (e) { e.stopPropagation(); step(-1); });
    if (galleryNext) galleryNext.addEventListener('click', function (e) { e.stopPropagation(); step(1); });

    if (mainWrap && mainImage) {
        mainWrap.addEventListener('click', function (e) {
            if (e.target.closest('button')) return;
            openLightbox(parseInt(mainImage.dataset.index || '0', 10));
        });
    }

    if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
    if (prevBtn) prevBtn.addEventListener('click', function () { lightboxStep(-1); });
    if (nextBtn) nextBtn.addEventListener('click', function () { lightboxStep(1); });

    document.addEventListener('keydown', function (e) {
        if (!lightbox.classList.contains('open')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') lightboxStep(-1);
        if (e.key === 'ArrowRight') lightboxStep(1);
    });
});
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>