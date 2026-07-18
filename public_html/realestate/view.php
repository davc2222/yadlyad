<?php
// VERSION: REAL_ESTATE_FINAL_V9_2026_07_18
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = (int) ($_GET['id'] ?? 0);
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function hasValue($value): bool
{
    return $value !== null && $value !== '' && $value !== '0000-00-00';
}

function dealTypeText(string $value): string
{
    return match ($value) {
        'sale' => 'מכירה',
        'rent' => 'השכרה',
        'roommates' => 'שותפים',
        'commercial' => 'מסחרי',
        default => 'לא צוין',
    };
}

function yesNo($value): string
{
    return (int) $value === 1 ? 'כן' : 'לא';
}

if ($id <= 0) {
    http_response_code(404);
    require_once '../includes/header.php';
    echo '<main class="re-view-page"><div class="re-message">לא התקבל מספר מודעה תקין.</div></main>';
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
        u.name AS seller_name,
        u.phone AS seller_phone
    FROM realestate_ads a
    LEFT JOIN categories cat ON cat.id = a.category_id
    LEFT JOIN categories subcat ON subcat.id = a.subcategory_id
    LEFT JOIN regions r ON r.id = a.region_id
    LEFT JOIN cities c ON c.id = a.city_id
    LEFT JOIN users u ON u.id = a.user_id
    WHERE a.id = ?
      AND a.is_deleted = 0
    LIMIT 1
");
$stmt->execute([$id]);
$ad = $stmt->fetch(PDO::FETCH_ASSOC);

$isOwner = $ad && $currentUserId > 0 && $currentUserId === (int) $ad['user_id'];

if (!$ad || ($ad['status'] !== 'active' && !$isOwner)) {
    http_response_code(404);
    require_once '../includes/header.php';
    echo '<main class="re-view-page"><div class="re-message">המודעה לא נמצאה או שעדיין לא אושרה.</div></main>';
    require_once '../includes/footer.php';
    exit;
}

if (!$isOwner) {
    $pdo->prepare("UPDATE realestate_ads SET views = COALESCE(views, 0) + 1 WHERE id = ?")->execute([$id]);
    $ad['views'] = (int) ($ad['views'] ?? 0) + 1;
}

$imageStmt = $pdo->prepare("
    SELECT id, image_name, image_path, sort_order, is_main
    FROM realestate_images
    WHERE ad_id = ?
    ORDER BY is_main DESC, sort_order ASC, id ASC
");
$imageStmt->execute([$id]);
$images = $imageStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($images as &$image) {
    if (empty($image['image_path']) && !empty($image['image_name'])) {
        $image['image_path'] = '/realestate/uploads/' . $image['image_name'];
    }
}
unset($image);

$title = trim((string) ($ad['title'] ?? '')) ?: 'מודעת נדל״ן';
$phone = trim((string) ($ad['phone'] ?? ''));
if ($phone === '') {
    $phone = trim((string) ($ad['seller_phone'] ?? ''));
}
$phoneDigits = preg_replace('/\D+/', '', $phone);
$whatsappPhone = str_starts_with($phoneDigits, '0') ? '972' . substr($phoneDigits, 1) : $phoneDigits;

$streetAddress = trim(implode(' ', array_filter([
    $ad['street'] ?? '',
    $ad['house_number'] ?? '',
])));

$locationParts = array_filter([
    $streetAddress,
    $ad['neighborhood'] ?? '',
    $ad['city_name'] ?? '',
    $ad['region_name'] ?? '',
]);
$location = implode(' · ', $locationParts);

$entranceText = '';
if (!empty($ad['immediate_entrance'])) {
    $entranceText = 'מיידית';
} elseif (hasValue($ad['entrance_date'] ?? null)) {
    $entranceText = date('d/m/Y', strtotime((string) $ad['entrance_date']));
}

$detailItems = [
    ['סוג עסקה', dealTypeText((string) ($ad['deal_type'] ?? '')), '🏷️'],
    ['סוג נכס', $ad['property_type'] ?? '', '🏠'],
    ['חדרים', hasValue($ad['rooms'] ?? null) ? $ad['rooms'] : '', '🚪'],
    ['שטח', hasValue($ad['square_meters'] ?? null) ? number_format((float) $ad['square_meters']) . ' מ״ר' : '', '📐'],
    ['קומה', hasValue($ad['floor'] ?? null) ? $ad['floor'] : '', '🏢'],
    ['מתוך קומות', hasValue($ad['total_floors'] ?? null) ? $ad['total_floors'] : '', '🏙️'],
    ['חניות', array_key_exists('parking_spaces', $ad) ? ((int) $ad['parking_spaces'] > 0 ? (string) (int) $ad['parking_spaces'] : 'אין') : '', '🚗'],
    ['מרפסות', array_key_exists('balconies', $ad) ? ((int) $ad['balconies'] > 0 ? (string) (int) $ad['balconies'] : 'אין') : '', '🌤️'],
    ['חדרי רחצה', array_key_exists('bathrooms', $ad) ? (string) (int) $ad['bathrooms'] : '', '🛁'],
    ['מעלית', array_key_exists('has_elevator', $ad) ? yesNo($ad['has_elevator']) : '', '🛗'],
    ['מיזוג אוויר', array_key_exists('has_air_conditioning', $ad) ? yesNo($ad['has_air_conditioning']) : '', '❄️'],
    ['מחסן', array_key_exists('has_storage', $ad) ? yesNo($ad['has_storage']) : '', '📦'],
    ['ממ״ד', array_key_exists('has_safe_room', $ad) ? yesNo($ad['has_safe_room']) : '', '🛡️'],
    ['סורגים', array_key_exists('has_bars', $ad) ? yesNo($ad['has_bars']) : '', '🪟'],
    ['נגישות', array_key_exists('has_accessibility', $ad) ? yesNo($ad['has_accessibility']) : '', '♿'],
    ['מרוהט', array_key_exists('has_furniture', $ad) ? yesNo($ad['has_furniture']) : '', '🛋️'],
    ['משופץ', array_key_exists('has_renovation', $ad) ? yesNo($ad['has_renovation']) : '', '✨'],
    ['מתאים לבעלי חיים', array_key_exists('has_pets', $ad) ? yesNo($ad['has_pets']) : '', '🐾'],
    ['כניסה', $entranceText, '📅'],
];
$detailItems = array_values(array_filter($detailItems, fn($item) => $item[1] !== '' && $item[1] !== null));

require_once '../includes/header.php';
?>

<main class="re-view-page">
    <?php if ($isOwner && $ad['status'] !== 'active'): ?>
        <div class="re-owner-notice">המודעה מוצגת לך כבעל המודעה. היא עדיין אינה מוצגת לציבור.</div>
    <?php endif; ?>

    <section class="re-view-head-card">
        <div class="re-view-head-top">
            <div class="re-view-title-wrap">
                <div class="re-view-kicker">
                    <?= e($ad['subcategory_name'] ?? 'נדל״ן') ?>
                </div>
                <h1>
                    <?= e($title) ?>
                </h1>
            </div>

            <div class="re-view-price-box">
                <?php if (!empty($ad['price']) && (float) $ad['price'] > 0): ?>
                    <div class="re-view-price-value">
                        <?= number_format((float) $ad['price']) ?> ₪
                    </div>
                    <?php if (!empty($ad['is_price_flexible'])): ?>
                        <div class="re-flexible-badge">מחיר גמיש</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="re-view-price-missing">מחיר לא צוין</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="re-view-head-bottom">
            <?php if ($location !== ''): ?>
                <div class="re-view-location">📍
                    <?= e($location) ?>
                </div>
            <?php endif; ?>

            <div class="re-view-meta-row">
                <span>🆔 מודעה
                    <?= (int) $ad['id'] ?>
                </span>
                <span>👁
                    <?= (int) ($ad['views'] ?? 0) ?> צפיות
                </span>
                <?php if (hasValue($ad['created_at'] ?? null)): ?>
                    <span>📅
                        <?= e(date('d/m/Y', strtotime((string) $ad['created_at']))) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="re-view-layout">

        <div class="re-left-column">
            <section class="re-gallery">
                <?php if ($images): ?>
                    <div class="re-main-image">
                        <img id="realestateMainImage" src="<?= e($images[0]['image_path']) ?>" alt="<?= e($title) ?>">
                    </div>

                    <?php if (count($images) > 1): ?>
                        <div class="re-thumbnails">
                            <?php foreach ($images as $index => $image): ?>
                                <button type="button" class="re-thumbnail<?= $index === 0 ? ' active' : '' ?>"
                                    data-src="<?= e($image['image_path']) ?>">
                                    <img src="<?= e($image['image_path']) ?>" alt="">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="re-no-image">
                        🏠
                        <span>אין תמונה למודעה</span>
                    </div>
                <?php endif; ?>
            </section>

            <?php if ($detailItems): ?>
                <section class="re-panel re-table-panel">
                    <div class="re-panel-title">
                        <span class="re-section-heading-icon">📄</span>
                        פרטי הנכס
                    </div>

                    <div class="re-property-table">
                        <?php foreach ($detailItems as [$label, $value, $icon]): ?>
                            <div class="re-property-table-row">
                                <div class="re-property-table-label"><?= e($label) ?></div>
                                <div class="re-property-table-value"><?= e($value) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="re-panel re-description-panel">
                <div class="re-panel-title">
                    <span class="re-section-heading-icon">📄</span>
                    תיאור הנכס
                </div>

                <div class="re-description">
                    <?= nl2br(e(trim((string) ($ad['description'] ?? '')) ?: 'לא נוסף תיאור למודעה.')) ?>
                </div>
            </section>
        </div>

        <div class="re-right-column">
            <aside class="re-contact-card">
                <div class="re-contact-title">
                    <span class="re-section-heading-icon">👤</span>
                    פרטי המפרסם
                </div>

                <div class="re-seller-name">
                    <?= e($ad['seller_name'] ?? 'מפרסם פרטי') ?>
                </div>

                <div class="re-contact-meta">
                    <?= (int) ($ad['views'] ?? 0) ?> צפיות
                </div>

                <?php if (!empty($ad['hide_phone'])): ?>
                    <div class="re-phone-hidden">המפרסם בחר להסתיר את מספר הטלפון.</div>
                <?php elseif ($phone !== ''): ?>
                    <div class="re-contact-buttons">
                        <a class="re-phone-btn" href="tel:<?= e($phoneDigits) ?>">
                            📞 <?= e($phone) ?>
                        </a>

                        <?php if (!empty($ad['allow_whatsapp']) && $whatsappPhone !== ''): ?>
                            <a class="re-whatsapp-btn"
                                href="https://wa.me/<?= e($whatsappPhone) ?>?text=<?= rawurlencode('שלום, אני מתעניין במודעת הנדל״ן: ' . $title) ?>"
                                target="_blank" rel="noopener">
                                WhatsApp
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="re-phone-hidden">לא הוזן מספר טלפון.</div>
                <?php endif; ?>

                <?php if ($isOwner): ?>
                    <div class="re-owner-actions">
                        <a href="/realestate/edit.php?id=<?= (int) $ad['id'] ?>">עריכת המודעה</a>
                        <a href="/realestate/my_ads.php">המודעות שלי</a>
                    </div>
                <?php endif; ?>
            </aside>

            <?php if ($detailItems): ?>
                <section class="re-panel re-feature-panel">
                    <div class="re-panel-title">
                        <span class="re-section-heading-icon">🏢</span>
                        פרטי הנכס
                    </div>

                    <div class="re-details-grid">
                        <?php foreach ($detailItems as [$label, $value, $icon]): ?>
                            <div class="re-detail">
                                <div class="re-detail-icon"><?= $icon ?></div>
                                <div>
                                    <span><?= e($label) ?></span>
                                    <strong><?= e($value) ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

        </div>

    </section>
</main>

<style>
    .re-view-page {
        width: min(1260px, calc(100% - 28px));
        margin: 6px auto 12px;
        direction: rtl;
        color: #0f172a;
    }

    .re-view-page * {
        box-sizing: border-box;
    }

    .re-message,
    .re-owner-notice {
        background: #fff;
        border: 1px solid #dbe4ee;
        border-radius: 16px;
        padding: 14px;
        font-weight: 800;
        color: #475569;
    }

    .re-owner-notice {
        margin-bottom: 8px;
        background: #fff8db;
        border-color: #f1d775;
        color: #755b00;
    }

    .re-view-head-card,
    .re-gallery,
    .re-panel,
    .re-contact-card {
        background: #fff;
        border: 1px solid #dbe4ee;
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .055);
    }

    .re-view-head-card {
        margin-bottom: 7px;
        overflow: hidden;
    }

    .re-view-head-top {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 285px;
        gap: 10px;
        align-items: center;
        padding: 11px 16px 9px;
    }

    .re-view-title-wrap {
        min-width: 0;
    }

    .re-view-kicker {
        color: #1677e8;
        font-size: 13px;
        font-weight: 900;
        margin-bottom: 4px;
    }

    .re-view-head-card h1 {
        margin: 0;
        font-size: 31px;
        line-height: 1.15;
        font-weight: 950;
        color: #0b1220;
        overflow-wrap: anywhere;
    }

    .re-view-price-box {
        padding: 10px 13px;
        text-align: center;
        background: #f8fafc;
        border: 1px solid #dbe4ee;
        border-radius: 15px;
    }

    .re-view-price-value {
        font-size: 31px;
        line-height: 1.08;
        font-weight: 950;
        color: #0b1220;
        white-space: nowrap;
    }

    .re-flexible-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 6px;
        padding: 4px 11px;
        border: 1px solid #86efac;
        border-radius: 999px;
        background: #f0fdf4;
        color: #15803d;
        font-size: 12px;
        font-weight: 950;
    }

    .re-view-price-missing {
        font-size: 17px;
        font-weight: 900;
        color: #64748b;
    }

    .re-view-head-bottom {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
        padding: 6px 16px;
        border-top: 1px solid #e2e8f0;
        background: #fcfdff;
    }

    .re-view-location {
        color: #334155;
        font-size: 14px;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .re-view-meta-row {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        color: #475569;
        font-size: 13px;
        font-weight: 850;
        white-space: nowrap;
    }

    .re-view-meta-row span {
        padding: 0 13px;
        border-inline-start: 1px solid #dbe4ee;
    }

    .re-view-meta-row span:first-child {
        border-inline-start: 0;
    }

    .re-view-layout {
        direction: ltr;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 400px;
        gap: 6px;
        align-items: start;
    }

    .re-left-column,
    .re-right-column {
        direction: rtl;
        min-width: 0;
    }

    .re-gallery {
        padding: 8px;
    }

    .re-main-image {
        height: 410px;
        border-radius: 14px;
        overflow: hidden;
        background: linear-gradient(135deg, #f8fafc, #eef2f7);
    }

    .re-main-image img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
    }

    .re-thumbnails {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(78px, 1fr));
        gap: 7px;
        margin-top: 7px;
    }

    .re-thumbnail {
        height: 62px;
        padding: 0;
        border: 2px solid transparent;
        border-radius: 9px;
        overflow: hidden;
        background: #eef2f6;
        cursor: pointer;
    }

    .re-thumbnail.active {
        border-color: #1677e8;
    }

    .re-thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .re-no-image {
        height: 300px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: linear-gradient(135deg, #f8fafc, #eef2f7);
        border-radius: 14px;
        font-size: 82px;
        color: #94a3b8;
    }

    .re-no-image span {
        font-size: 17px;
        font-weight: 900;
        color: #7c8798;
    }

    .re-contact-card {
        padding: 14px;
        text-align: center;
    }

    .re-contact-title,
    .re-panel-title {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 6px;
        font-size: 18px;
        font-weight: 950;
        color: #0f172a;
    }

    .re-section-heading-icon {
        font-size: 21px;
        line-height: 1;
    }

    .re-seller-name {
        font-size: 20px;
        font-weight: 950;
        margin-top: 8px;
    }

    .re-contact-meta {
        font-size: 14px;
        color: #64748b;
        margin-top: 5px;
    }

    .re-contact-buttons {
        display: grid;
        gap: 7px;
        margin-top: 8px;
    }

    .re-contact-buttons a {
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: #fff;
        font-size: 17px;
        font-weight: 950;
        box-shadow: 0 5px 14px rgba(15, 23, 42, .08);
    }

    .re-phone-btn {
        background: #1677e8;
    }

    .re-whatsapp-btn {
        background: #16a34a;
    }

    .re-phone-hidden {
        margin-top: 10px;
        padding: 11px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        color: #64748b;
        font-weight: 800;
    }

    .re-owner-actions {
        display: grid;
        gap: 6px;
        margin-top: 10px;
        padding-top: 14px;
        border-top: 1px solid #e2e8f0;
    }

    .re-owner-actions a {
        text-decoration: none;
        text-align: center;
        background: #eef4ff;
        color: #1859b7;
        border-radius: 11px;
        padding: 9px;
        font-weight: 900;
    }

    .re-panel {
        padding: 12px;
        margin-top: 8px;
    }

    .re-panel-title {
        padding-bottom: 7px;
        margin-bottom: 7px;
        border-bottom: 1px solid #dbe4ee;
    }

    .re-details-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 6px;
    }

    .re-detail {
        min-height: 64px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 6px 4px;
        text-align: center;
        background: #f8fafc;
        border: 1px solid #dbe4ee;
        border-radius: 12px;
    }

    .re-detail-icon {
        font-size: 20px;
        line-height: 1;
    }

    .re-detail span {
        display: block;
        color: #64748b;
        font-size: 11px;
        font-weight: 850;
        line-height: 1.15;
    }

    .re-detail strong {
        display: block;
        margin-top: 2px;
        color: #0f172a;
        font-size: 14px;
        font-weight: 950;
        line-height: 1.2;
    }

    .re-property-table {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        border-top: 1px solid #dbe4ee;
        border-inline-start: 1px solid #dbe4ee;
    }

    .re-property-table-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        min-height: 38px;
        border-inline-end: 1px solid #dbe4ee;
        border-bottom: 1px solid #dbe4ee;
    }

    .re-property-table-label,
    .re-property-table-value {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 7px 9px;
        text-align: center;
        font-size: 13px;
    }

    .re-property-table-label {
        color: #475569;
        font-weight: 850;
        background: #fbfdff;
        border-inline-end: 1px solid #dbe4ee;
    }

    .re-property-table-value {
        color: #0f172a;
        font-weight: 950;
    }

    .re-description {
        font-size: 16px;
        line-height: 1.6;
        color: #334155;
    }

    @media(max-width:1050px) {
        .re-view-layout {
            grid-template-columns: minmax(0, 1fr) 330px;
        }

        .re-details-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media(max-width:850px) {
        .re-view-layout {
            direction: rtl;
            grid-template-columns: 1fr;
        }

        .re-right-column {
            display: contents;
        }

        .re-contact-card {
            order: -1;
        }

        .re-property-table {
            grid-template-columns: 1fr;
        }

        .re-view-head-top {
            grid-template-columns: 1fr;
        }

        .re-view-price-box {
            width: 280px;
            max-width: 100%;
            justify-self: start;
        }
    }

    @media(max-width:620px) {
        .re-view-page {
            width: calc(100% - 18px);
            margin-top: 8px;
        }

        .re-view-head-card h1 {
            font-size: 27px;
        }

        .re-view-head-top {
            padding: 14px;
        }

        .re-view-head-bottom {
            display: block;
            padding: 9px 14px;
        }

        .re-view-meta-row {
            justify-content: flex-start;
            flex-wrap: wrap;
            margin-top: 7px;
            white-space: normal;
        }

        .re-view-meta-row span {
            padding: 3px 8px;
            border: 0;
        }

        .re-main-image {
            height: 270px;
        }

        .re-no-image {
            height: 250px;
            font-size: 68px;
        }

        .re-details-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media(max-width:430px) {
        .re-details-grid {
            grid-template-columns: 1fr 1fr;
        }

        .re-property-table-row {
            grid-template-columns: 1fr 1fr;
        }

        .re-view-price-value {
            font-size: 28px;
        }
    }

    .re-table-panel {
        margin-top: 8px;
        width: 100%;
    }

    .re-description-panel {
        width: 100%;
        margin-top: 10px;
    }

    .re-left-column {
        width: 100%;
    }

    .re-property-table {
        width: 100%;
    }

    @media(max-width:1050px) {
        .re-view-layout {
            grid-template-columns: minmax(0, 1fr) 360px;
        }
    }


    .re-table-panel {
        margin-top: 14px;
        width: 100%;
    }

    .re-feature-panel {
        margin-top: 14px;
    }

    .re-description-panel {
        margin-top: 8px;
        width: 100%;
    }

    .re-left-column,
    .re-right-column {
        align-self: start;
    }

    .re-gallery,
    .re-table-panel,
    .re-description-panel,
    .re-contact-card,
    .re-feature-panel {
        width: 100%;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () { const mainImage = document.getElementById('realestateMainImage'); const buttons = document.querySelectorAll('.re-thumbnail'); buttons.forEach(function (button) { button.addEventListener('click', function () { if (!mainImage) return; mainImage.src = this.dataset.src || ''; buttons.forEach(function (item) { item.classList.remove('active') }); this.classList.add('active') }) }) });
</script>

<?php require_once '../includes/footer.php'; ?>