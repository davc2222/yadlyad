<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/header.php';
?>
<link rel="stylesheet" href="/vehicle/css/vehicle.css?v=20260709c">

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

function imageSrc($img)
{
    if (!empty($img['image_path']))
        return $img['image_path'];
    if (!empty($img['image_name']))
        return '/uploads/secondhand/' . $img['image_name'];
    return '';
}

function conditionText($condition)
{
    if ($condition === 'new')
        return 'חדש';
    if ($condition === 'like_new')
        return 'כמו חדש';
    if ($condition === 'used')
        return 'משומש';
    if ($condition === 'broken')
        return 'לתיקון / לא תקין';
    return '';
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    echo "<main class='vehicle-page'><div class='vehicle-not-found'>מודעה לא נמצאה</div></main>";
    require_once '../includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        a.*,
        cat.name AS category_name,
        subcat.name AS subcategory_name,
        r.name AS region_name,
        c.name AS city_name,
        u.name AS seller_name
    FROM secondhand_ads a
    LEFT JOIN categories cat ON cat.id = a.category_id
    LEFT JOIN categories subcat ON subcat.id = a.subcategory_id
    LEFT JOIN regions r ON r.id = a.region_id
    LEFT JOIN cities c ON c.id = a.city_id
    LEFT JOIN users u ON u.id = a.user_id
    WHERE a.id = ?
      AND a.is_deleted = 0
      AND a.status IN ('approved', 'active')
    LIMIT 1
");
$stmt->execute([$id]);
$ad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ad) {
    echo "<main class='vehicle-page'><div class='vehicle-not-found'>מודעה לא נמצאה</div></main>";
    require_once '../includes/footer.php';
    exit;
}

$pdo->prepare("UPDATE secondhand_ads SET views = views + 1 WHERE id = ?")->execute([$id]);

$imgStmt = $pdo->prepare("
    SELECT *
    FROM secondhand_images
    WHERE ad_id = ?
    ORDER BY is_main DESC, sort_order ASC, id ASC
");
$imgStmt->execute([$id]);
$images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

$galleryImages = [];
foreach ($images as $img) {
    $src = imageSrc($img);
    if ($src !== '')
        $galleryImages[] = $src;
}

$regionValue = $ad['region_name'] ?? '';
$cityValue = $ad['city_name'] ?? '';
$conditionValue = conditionText($ad['item_condition'] ?? '');

$title = trim($ad['title'] ?? '');
if ($title === '')
    $title = 'מודעת יד שנייה';

$details = [
    ['קטגוריה', $ad['category_name'] ?? ''],
    ['תת קטגוריה', $ad['subcategory_name'] ?? ''],
    ['מצב המוצר', $conditionValue],
    ['אזור', $regionValue],
    ['עיר', $cityValue],
    ['מספר מודעה', $ad['id'] ?? ''],
    ['צפיות', number_format((int) ($ad['views'] ?? 0) + 1)],
    ['פורסם', fmtDate($ad['created_at'] ?? '')],
];

$topDetails = [
    ['קטגוריה', $ad['category_name'] ?? ''],
    ['תת קטגוריה', $ad['subcategory_name'] ?? ''],
    ['מצב', $conditionValue],
    ['אזור', $regionValue],
    ['עיר', $cityValue],
];

$extraInfo = [];
if (hasVal($ad['seller_name'] ?? ''))
    $extraInfo[] = 'מפרסם: ' . $ad['seller_name'];
if (hasVal($ad['category_name'] ?? ''))
    $extraInfo[] = $ad['category_name'];
if (hasVal($ad['subcategory_name'] ?? ''))
    $extraInfo[] = $ad['subcategory_name'];
if (hasVal($conditionValue))
    $extraInfo[] = 'מצב: ' . $conditionValue;
if (!empty($ad['is_price_flexible']))
    $extraInfo[] = 'מחיר גמיש';

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
                        <img src="<?= e($src) ?>" data-index="<?= (int) $index ?>" class="<?= $index === 0 ? 'active' : '' ?>"
                            alt="">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <aside class="vehicle-side-box">
            <div class="vehicle-headline">
                <div class="ad-label">יד שנייה</div>
                <h1><?= e($title) ?></h1>

                <?php if (hasVal($cityValue) || hasVal($regionValue)): ?>
                    <div class="vehicle-place">
                        <?= hasVal($cityValue) ? e($cityValue) : '' ?>
                        <?= hasVal($cityValue) && hasVal($regionValue) ? ' · ' : '' ?>
                        <?= hasVal($regionValue) ? e($regionValue) : '' ?>
                    </div>
                <?php endif; ?>

                <div class="vehicle-price">
                    <?= !empty($ad['price']) ? number_format((float) $ad['price']) . ' ₪' : 'מחיר לא צוין' ?>
                    <?php if ((int) ($ad['is_price_flexible'] ?? 0) === 1): ?>
                        <small>גמיש</small>
                    <?php endif; ?>
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

                <?php if (empty($ad['phone']) || !empty($ad['hide_phone'])): ?>
                    <div class="vehicle-empty">המוכר בחר להסתיר טלפון.</div>
                <?php endif; ?>
            </div>
        </aside>

    </section>

    <section class="vehicle-lower-grid">

        <section class="vehicle-card details-card">
            <h2>פרטי המוצר</h2>
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

            <h2 class="features-title">מידע נוסף</h2>
            <?php if ($extraInfo): ?>
                <div class="features-list">
                    <?php foreach ($extraInfo as $item): ?>
                        <span>✓ <?= e($item) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="vehicle-empty">לא צוין מידע נוסף.</div>
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