<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

function fmtDateTime($date)
{
    return hasVal($date) ? date('d/m/Y H:i', strtotime($date)) : '';
}

function fmtMonthYear($month, $year)
{
    if (!hasVal($month) || !hasVal($year)) {
        return '';
    }

    return str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '/' . $year;
}

function imageSrc($img)
{
    if (!empty($img['image_path'])) {
        return $img['image_path'];
    }

    if (!empty($img['image_name'])) {
        return '/uploads/vehicles/' . $img['ad_id'] . '/' . $img['image_name'];
    }

    return '';
}

function statusText($status)
{
    if ($status === 'approved') return 'מאושרת';
    if ($status === 'rejected') return 'נדחתה';
    return 'ממתינה לאישור';
}

function statusClass($status)
{
    if ($status === 'approved') return 'approved';
    if ($status === 'rejected') return 'rejected';
    return 'pending';
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header('Location: /admin/vehicle_ads.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE vehicle_ads SET status = 'approved' WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: /admin/vehicle_ad_view.php?id=' . $id);
        exit;
    }

    if ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE vehicle_ads SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: /admin/vehicle_ad_view.php?id=' . $id);
        exit;
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("UPDATE vehicle_ads SET is_deleted = 1 WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: /admin/vehicle_ads.php');
        exit;
    }
}

require_once '../includes/admin_header.php';
require_once '../includes/admin_sidebar.php';
?>
<link rel="stylesheet" href="/vehicle/css/vehicle.css">

<?php
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
        dt.name AS drive_type_name,
        u.name AS user_name,
        u.email AS user_email,
        u.phone AS user_phone,
        u.created_at AS user_created_at
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
    LEFT JOIN users u ON u.id = va.user_id
    WHERE va.id = ?
      AND va.is_deleted = 0
    LIMIT 1
");
$stmt->execute([$id]);
$ad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ad) {
    echo "<div class='card'><h1>המודעה לא נמצאה</h1><p><a class='btn btn-blue' href='/admin/vehicle_ads.php'>חזרה לרשימה</a></p></div>";
    require_once '../includes/admin_footer.php';
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

$galleryImages = [];
foreach ($images as $img) {
    $src = imageSrc($img);
    if ($src !== '') {
        $galleryImages[] = $src;
    }
}

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

$title = trim(($ad['manufacturer_name'] ?? '') . ' ' . ($ad['model_name'] ?? '') . ' ' . ($ad['year'] ?? ''));
if ($title === '') {
    $title = $ad['title'] ?? 'רכב למכירה';
}

$mainImage = $galleryImages[0] ?? '';
?>

<style>
.admin-ad-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 12px 14px;
    margin-bottom: 18px;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
.admin-ad-toolbar h1 {
    margin: 0;
    font-size: 22px;
    font-weight: 900;
    color: #111827;
}
.admin-ad-toolbar small {
    display: block;
    margin-top: 4px;
    color: #6b7280;
}
.admin-ad-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.admin-ad-actions a,
.admin-ad-actions button {
    border: 0;
    border-radius: 9px;
    padding: 9px 14px;
    font-size: 14px;
    font-weight: 900;
    cursor: pointer;
    text-decoration: none;
}
.admin-btn-back { background: #f3f4f6; color: #111827; border: 1px solid #d1d5db !important; }
.admin-btn-edit { background: #dbeafe; color: #1d4ed8; }
.admin-btn-approve { background: #16a34a; color: #fff; }
.admin-btn-reject { background: #dc2626; color: #fff; }
.admin-btn-delete { background: #111827; color: #fff; }
.admin-status-pill {
    display: inline-block;
    padding: 5px 11px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 900;
}
.admin-status-pill.pending { background: #fff3cd; color: #8a6d00; }
.admin-status-pill.approved { background: #d1fae5; color: #065f46; }
.admin-status-pill.rejected { background: #fee2e2; color: #991b1b; }
.admin-meta-box {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 12px 14px;
    margin-bottom: 18px;
    display: grid;
    grid-template-columns: repeat(5, minmax(120px, 1fr));
    gap: 10px;
}
.admin-meta-item {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 9px 10px;
}
.admin-meta-item span { display:block; color:#6b7280; font-size:12px; margin-bottom:4px; font-weight:800; }
.admin-meta-item strong { color:#111827; font-size:14px; }
.vehicle-small-actions { display:none !important; }
.vehicle-fav-top { display:none !important; }
.admin-contact-list {
    margin-top: 16px;
    border-top: 1px solid #e5e7eb;
    padding-top: 14px;
}
.admin-contact-line {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}
.admin-contact-line span { color:#6b7280; font-weight:800; }
.admin-contact-line strong { color:#111827; }
.vehicle-main-photo-wrap.has-image { cursor: zoom-in; }
.vehicle-thumbs img {
    cursor: pointer;
    opacity: 0.72;
    transition: opacity 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
    border: 2px solid transparent;
}
.vehicle-thumbs img.active,
.vehicle-thumbs img:hover { opacity: 1; border-color: #1d6fe8; transform: translateY(-1px); }
.vehicle-lightbox { position: fixed; inset: 0; z-index: 99999; display: none; align-items: center; justify-content: center; background: rgba(0,0,0,.88); direction:ltr; }
.vehicle-lightbox.open { display:flex; }
.vehicle-lightbox-inner { position:relative; width:100%; height:100%; display:flex; align-items:center; justify-content:center; padding:60px 90px; }
.vehicle-lightbox-img { max-width:100%; max-height:100%; object-fit:contain; border-radius:10px; box-shadow:0 20px 70px rgba(0,0,0,.45); user-select:none; }
.vehicle-lightbox-close,
.vehicle-lightbox-prev,
.vehicle-lightbox-next { position:absolute; border:0; background:rgba(255,255,255,.16); color:#fff; cursor:pointer; transition:background .2s ease; }
.vehicle-lightbox-close:hover,
.vehicle-lightbox-prev:hover,
.vehicle-lightbox-next:hover { background:rgba(255,255,255,.28); }
.vehicle-lightbox-close { top:20px; right:24px; width:44px; height:44px; border-radius:50%; font-size:34px; line-height:40px; }
.vehicle-lightbox-prev,
.vehicle-lightbox-next { top:50%; transform:translateY(-50%); width:54px; height:72px; border-radius:16px; font-size:46px; line-height:1; }
.vehicle-lightbox-prev { left:22px; }
.vehicle-lightbox-next { right:22px; }
.vehicle-lightbox-counter { position:absolute; bottom:24px; left:50%; transform:translateX(-50%); color:#fff; background:rgba(0,0,0,.45); padding:8px 16px; border-radius:999px; font-size:14px; }
body.vehicle-lightbox-lock { overflow:hidden; }
@media (max-width: 900px) { .admin-meta-box { grid-template-columns: repeat(2, 1fr); } }
</style>

<div class="admin-ad-toolbar">
    <div>
        <h1>ניהול מודעה #<?= (int)$ad['id'] ?></h1>
        <small>
            <?= e($title) ?> ·
            <span class="admin-status-pill <?= statusClass($ad['status'] ?? 'pending') ?>"><?= statusText($ad['status'] ?? 'pending') ?></span>
        </small>
    </div>

    <div class="admin-ad-actions">
        <a class="admin-btn-back" href="/admin/vehicle_ads.php">← חזרה לרשימה</a>
        <a class="admin-btn-edit" href="/vehicle/edit.php?id=<?= (int)$ad['id'] ?>" target="_blank">✏ ערוך</a>

        <?php if (($ad['status'] ?? '') !== 'approved'): ?>
            <form method="post">
                <input type="hidden" name="action" value="approve">
                <button class="admin-btn-approve" type="submit">✔ אשר</button>
            </form>
        <?php endif; ?>

        <?php if (($ad['status'] ?? '') !== 'rejected'): ?>
            <form method="post">
                <input type="hidden" name="action" value="reject">
                <button class="admin-btn-reject" type="submit">✖ דחה</button>
            </form>
        <?php endif; ?>

        <form method="post" onsubmit="return confirm('למחוק את המודעה?');">
            <input type="hidden" name="action" value="delete">
            <button class="admin-btn-delete" type="submit">🗑 מחק</button>
        </form>
    </div>
</div>

<div class="admin-meta-box">
    <div class="admin-meta-item"><span>סטטוס</span><strong><?= statusText($ad['status'] ?? 'pending') ?></strong></div>
    <div class="admin-meta-item"><span>מפרסם</span><strong><?= e($ad['user_name'] ?? '-') ?></strong></div>
    <div class="admin-meta-item"><span>אימייל</span><strong><?= e($ad['user_email'] ?? '-') ?></strong></div>
    <div class="admin-meta-item"><span>טלפון</span><strong><?= e($ad['phone'] ?: ($ad['user_phone'] ?? '-')) ?></strong></div>
    <div class="admin-meta-item"><span>נוצרה</span><strong><?= e(fmtDateTime($ad['created_at'] ?? '')) ?></strong></div>
</div>

<main class="vehicle-view">

    <section class="vehicle-hero-card">

        <div class="vehicle-gallery">
            <div class="vehicle-main-photo-wrap<?= $mainImage ? ' has-image' : '' ?>" id="mainPhotoWrap">
                <?php if ($mainImage): ?>
                    <img id="mainVehicleImage" class="vehicle-main-image" src="<?= e($mainImage) ?>" alt="<?= e($title) ?>" data-index="0">
                <?php else: ?>
                    <div class="vehicle-placeholder">אין תמונה</div>
                <?php endif; ?>

                <div class="vehicle-photo-count">📷 <?= count($galleryImages) ?: 0 ?></div>
            </div>

            <?php if (count($galleryImages) > 1): ?>
                <div class="vehicle-thumbs" id="vehicleThumbs">
                    <?php foreach ($galleryImages as $index => $src): ?>
                        <img src="<?= e($src) ?>" alt="<?= e($title) ?>" data-index="<?= (int) $index ?>" class="<?= $index === 0 ? 'active' : '' ?>">
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
                <?php if (hasVal($ad['year'] ?? '')): ?><span><?= e($ad['year']) ?></span><?php endif; ?>
                <?php if (hasVal($ad['km'] ?? '')): ?><span><?= number_format((float) $ad['km']) ?> ק״מ</span><?php endif; ?>
                <?php if (hasVal($ad['hand'] ?? '')): ?><span>יד <?= e($ad['hand']) ?></span><?php endif; ?>
                <?php if (hasVal($ad['fuel_type_name'] ?? '')): ?><span><?= e($ad['fuel_type_name']) ?></span><?php endif; ?>
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

            <div class="admin-contact-list">
                <div class="admin-contact-line"><span>שם</span><strong><?= e($ad['user_name'] ?? '-') ?></strong></div>
                <div class="admin-contact-line"><span>אימייל</span><strong><?= e($ad['user_email'] ?? '-') ?></strong></div>
                <div class="admin-contact-line"><span>טלפון מודעה</span><strong><?= e($ad['phone'] ?? '-') ?></strong></div>
                <div class="admin-contact-line"><span>טלפון משתמש</span><strong><?= e($ad['user_phone'] ?? '-') ?></strong></div>
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

<?php if (!empty($galleryImages)): ?>
    <div class="vehicle-lightbox" id="vehicleLightbox" aria-hidden="true">
        <div class="vehicle-lightbox-inner" id="vehicleLightboxInner">
            <button type="button" class="vehicle-lightbox-close" id="vehicleLightboxClose" aria-label="סגור">×</button>
            <button type="button" class="vehicle-lightbox-prev" id="vehicleLightboxPrev" aria-label="תמונה קודמת">‹</button>
            <img class="vehicle-lightbox-img" id="vehicleLightboxImg" src="" alt="<?= e($title) ?>">
            <button type="button" class="vehicle-lightbox-next" id="vehicleLightboxNext" aria-label="תמונה הבאה">›</button>
            <div class="vehicle-lightbox-counter" id="vehicleLightboxCounter"></div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const images = <?= json_encode(array_values($galleryImages), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        const mainImage = document.getElementById('mainVehicleImage');
        const mainWrap = document.getElementById('mainPhotoWrap');
        const thumbs = document.querySelectorAll('#vehicleThumbs img');
        const lightbox = document.getElementById('vehicleLightbox');
        const lightboxInner = document.getElementById('vehicleLightboxInner');
        const lightboxImg = document.getElementById('vehicleLightboxImg');
        const closeBtn = document.getElementById('vehicleLightboxClose');
        const prevBtn = document.getElementById('vehicleLightboxPrev');
        const nextBtn = document.getElementById('vehicleLightboxNext');
        const counter = document.getElementById('vehicleLightboxCounter');
        let currentIndex = 0;
        let touchStartX = 0;
        let touchEndX = 0;

        function setMainImage(index) {
            if (!images[index] || !mainImage) return;
            currentIndex = index;
            mainImage.src = images[index];
            mainImage.dataset.index = index;
            thumbs.forEach(function (thumb) {
                thumb.classList.toggle('active', parseInt(thumb.dataset.index, 10) === index);
            });
        }

        function openLightbox(index) {
            if (!images[index]) return;
            currentIndex = index;
            lightboxImg.src = images[currentIndex];
            counter.textContent = (currentIndex + 1) + ' / ' + images.length;
            lightbox.classList.add('open');
            lightbox.setAttribute('aria-hidden', 'false');
            document.body.classList.add('vehicle-lightbox-lock');
        }

        function closeLightbox() {
            lightbox.classList.remove('open');
            lightbox.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('vehicle-lightbox-lock');
            lightboxImg.src = '';
        }

        function showNext() {
            currentIndex = (currentIndex + 1) % images.length;
            lightboxImg.src = images[currentIndex];
            counter.textContent = (currentIndex + 1) + ' / ' + images.length;
            setMainImage(currentIndex);
        }

        function showPrev() {
            currentIndex = (currentIndex - 1 + images.length) % images.length;
            lightboxImg.src = images[currentIndex];
            counter.textContent = (currentIndex + 1) + ' / ' + images.length;
            setMainImage(currentIndex);
        }

        thumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function () { setMainImage(parseInt(this.dataset.index, 10)); });
            thumb.addEventListener('dblclick', function () { openLightbox(parseInt(this.dataset.index, 10)); });
        });

        if (mainWrap && mainImage) {
            mainWrap.addEventListener('click', function () {
                openLightbox(parseInt(mainImage.dataset.index || '0', 10));
            });
        }

        closeBtn.addEventListener('click', closeLightbox);
        nextBtn.addEventListener('click', showNext);
        prevBtn.addEventListener('click', showPrev);

        lightbox.addEventListener('click', function (event) {
            if (event.target === lightbox || event.target === lightboxInner) closeLightbox();
        });

        document.addEventListener('keydown', function (event) {
            if (!lightbox.classList.contains('open')) return;
            if (event.key === 'Escape') closeLightbox();
            if (event.key === 'ArrowRight') showNext();
            if (event.key === 'ArrowLeft') showPrev();
        });

        lightbox.addEventListener('touchstart', function (event) {
            touchStartX = event.changedTouches[0].screenX;
        }, { passive: true });

        lightbox.addEventListener('touchend', function (event) {
            touchEndX = event.changedTouches[0].screenX;
            const diff = touchEndX - touchStartX;
            if (Math.abs(diff) < 50) return;
            if (diff < 0) showNext(); else showPrev();
        }, { passive: true });
    });
    </script>
<?php endif; ?>

<?php require_once '../includes/admin_footer.php'; ?>
