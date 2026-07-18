<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$q = trim($_GET['q'] ?? '');
$vehicleAds = [];
$secondhandAds = [];

if ($q !== '') {
    $like = '%' . $q . '%';

    $vehicleStmt = $pdo->prepare("
        SELECT a.id, a.price, a.year, a.km, a.hand, a.views,
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
        LEFT JOIN car_makers m ON m.id = a.manufacturer_id
        LEFT JOIN car_models cm ON cm.id = a.model_id
        LEFT JOIN gearboxes g ON g.id = a.gearbox_id
        LEFT JOIN fuel_types f ON f.id = a.fuel_type_id
        LEFT JOIN regions r ON r.id = a.region_id
        LEFT JOIN cities c ON c.id = a.city_id
        WHERE a.status = 'active'
          AND a.is_deleted = 0
          AND (
              a.title LIKE ? OR a.description LIKE ? OR
              m.name LIKE ? OR cm.name LIKE ? OR
              g.name LIKE ? OR f.name LIKE ? OR
              r.name LIKE ? OR c.name LIKE ?
          )
        ORDER BY a.id DESC
        LIMIT 100
    ");

    $vehicleStmt->execute([$like, $like, $like, $like, $like, $like, $like, $like]);
    $vehicleAds = $vehicleStmt->fetchAll(PDO::FETCH_ASSOC);

    $secondhandStmt = $pdo->prepare("
        SELECT a.id, a.title, a.description, a.price, a.views,
               a.is_price_flexible, a.item_condition,
               cat.name AS category_name,
               subcat.name AS subcategory_name,
               r.name AS region_name,
               c.name AS city_name,
               (
                   SELECT image_path
                   FROM secondhand_images
                   WHERE ad_id = a.id
                   ORDER BY is_main DESC, sort_order ASC, id ASC
                   LIMIT 1
               ) AS image_path
        FROM secondhand_ads a
        LEFT JOIN categories cat ON cat.id = a.category_id
        LEFT JOIN categories subcat ON subcat.id = a.subcategory_id
        LEFT JOIN regions r ON r.id = a.region_id
        LEFT JOIN cities c ON c.id = a.city_id
        WHERE a.status IN ('approved', 'active')
          AND a.is_deleted = 0
          AND (
              a.title LIKE ? OR a.description LIKE ? OR
              cat.name LIKE ? OR subcat.name LIKE ? OR
              r.name LIKE ? OR c.name LIKE ?
          )
        ORDER BY a.id DESC
        LIMIT 100
    ");

    $secondhandStmt->execute([$like, $like, $like, $like, $like, $like]);
    $secondhandAds = $secondhandStmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalResults = count($vehicleAds) + count($secondhandAds);
?>

<link rel="stylesheet" href="/vehicle/css/vehicle_list.css?v=6">
<link rel="stylesheet" href="/secondhand/css/secondhand.css?v=11">

<style>
    .site-search-page {
        max-width: 1180px;
        margin: 28px auto 50px;
        padding: 0 20px;
        direction: rtl
    }

    .site-search-head {
        margin-bottom: 24px;
        padding: 22px 26px;
        background: linear-gradient(135deg, #eef6ff, #fff);
        border: 1px solid #dbe7f5;
        border-radius: 20px
    }

    .site-search-head h1 {
        margin: 0 0 8px;
        color: #0f172a;
        font-size: 30px
    }

    .site-search-head p {
        margin: 0;
        color: #64748b;
        font-size: 15px
    }

    .site-search-section {
        margin-top: 30px
    }

    .site-search-section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0 0 16px;
        color: #0f172a;
        font-size: 24px
    }

    .site-search-section-title span {
        color: #64748b;
        font-size: 14px;
        font-weight: 700
    }

    .site-search-empty {
        padding: 42px 20px;
        text-align: center;
        background: #fff;
        border: 1px solid #dbe3ef;
        border-radius: 18px;
        color: #64748b;
        font-weight: 700
    }

    .search-secondhand-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 22px;
        align-items: start
    }

    .search-secondhand-card {
        display: block;
        overflow: hidden;
        color: #111827;
        text-decoration: none;
        background: linear-gradient(180deg, #fff 0%, #fbfdff 100%);
        border: 1px solid #dbe5f0;
        border-radius: 16px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .09);
        transition: .2s
    }

    .search-secondhand-card:hover {
        transform: translateY(-4px);
        border-color: #b9cee5;
        box-shadow: 0 14px 32px rgba(15, 23, 42, .14)
    }

    .search-secondhand-image {
        width: 100%;
        height: 185px;
        overflow: hidden;
        background: #edf2f7
    }

    .search-secondhand-image img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover
    }

    .search-secondhand-no-image {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        color: #94a3b8;
        font-size: 52px;
        background: linear-gradient(135deg, #eef3f8, #f8fafc)
    }

    .search-secondhand-body {
        padding: 16px
    }

    .search-secondhand-category {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        color: #0d6fdc;
        font-size: 11px;
        font-weight: 800;
        background: #eaf3ff;
        border: 1px solid #cfe1f7;
        border-radius: 999px
    }

    .search-secondhand-title {
        margin: 10px 0 7px;
        overflow: hidden;
        color: #111827;
        font-size: 19px;
        font-weight: 900;
        line-height: 1.35;
        text-overflow: ellipsis;
        white-space: nowrap
    }

    .search-secondhand-location {
        margin-bottom: 10px;
        color: #64748b;
        font-size: 13px
    }

    .search-secondhand-desc {
        min-height: 40px;
        margin: 0 0 12px;
        overflow: hidden;
        color: #667085;
        font-size: 13px;
        line-height: 1.45
    }

    .search-secondhand-price {
        margin-bottom: 10px;
        color: #111827;
        font-size: 21px;
        font-weight: 900
    }

    .search-secondhand-price small {
        margin-right: 7px;
        padding: 4px 8px;
        color: #0d8d48;
        font-size: 11px;
        background: #e8fff1;
        border: 1px solid #b8efce;
        border-radius: 999px
    }

    .search-secondhand-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px solid #eceff3;
        color: #647084;
        font-size: 13px
    }

    .search-secondhand-footer strong {
        color: #0d6fdc
    }

    @media(max-width:800px) {
        .search-secondhand-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr))
        }
    }

    @media(max-width:520px) {
        .site-search-page {
            padding: 0 12px
        }

        .search-secondhand-grid {
            grid-template-columns: 1fr
        }
    }
</style>

<section class="site-search-page">
    <div class="site-search-head">
        <h1>תוצאות חיפוש</h1>
        <?php if ($q !== ''): ?>
            <p>נמצאו <?= $totalResults ?> תוצאות עבור <strong><?= e($q) ?></strong></p>
        <?php else: ?>
            <p>הזן ביטוי בשורת החיפוש העליונה.</p>
        <?php endif; ?>
    </div>

    <?php if ($q !== '' && $totalResults === 0): ?>
        <div class="site-search-empty">לא נמצאו מודעות מתאימות.</div>
    <?php endif; ?>

    <?php if ($vehicleAds): ?>
        <section class="site-search-section">
            <h2 class="site-search-section-title">רכב <span><?= count($vehicleAds) ?> תוצאות</span></h2>
            <div class="vehicle-grid">
                <?php foreach ($vehicleAds as $ad): ?>
                    <a class="vehicle-card" href="/vehicle/view.php?id=<?= (int) $ad['id'] ?>">
                        <div class="vehicle-card-image">
                            <?php if (!empty($ad['image_path'])): ?>
                                <img src="<?= e($ad['image_path']) ?>" alt="">
                            <?php else: ?>
                                <div class="no-image">🚗</div>
                            <?php endif; ?>
                            <?php if (!empty($ad['year'])): ?>
                                <span class="year-badge"><?= (int) $ad['year'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="vehicle-card-body">
                            <h2><?= e(trim(($ad['maker_name'] ?? '') . ' ' . ($ad['model_name'] ?? ''))) ?></h2>

                            <div class="vehicle-meta">
                                <?php if (!empty($ad['region_name'])): ?><span>📍
                                        <?= e($ad['region_name']) ?></span><?php endif; ?>
                                <?php if (!empty($ad['city_name'])): ?><span><?= e($ad['city_name']) ?></span><?php endif; ?>
                            </div>

                            <div class="vehicle-meta">
                                <span>📅 <?= e($ad['year'] ?? '') ?></span>
                                <span>🤝 יד <?= e($ad['hand'] ?? '') ?></span>
                                <span>🛣 <?= number_format((int) ($ad['km'] ?? 0)) ?> ק״מ</span>
                            </div>

                            <div class="vehicle-meta">
                                <?php if (!empty($ad['gearbox_name'])): ?><span>⚙
                                        <?= e($ad['gearbox_name']) ?></span><?php endif; ?>
                                <?php if (!empty($ad['fuel_name'])): ?><span>⛽ <?= e($ad['fuel_name']) ?></span><?php endif; ?>
                            </div>

                            <div class="vehicle-price">
                                <?php if (!empty($ad['price'])): ?>₪<?= number_format((int) $ad['price']) ?><?php else: ?>מחיר לא
                                    צוין<?php endif; ?>
                                <?php if ((int) ($ad['is_price_flexible'] ?? 0) === 1): ?><small>גמיש</small><?php endif; ?>
                            </div>

                            <div class="vehicle-card-footer">
                                <span>👁 <?= number_format((int) ($ad['views'] ?? 0)) ?> צפיות</span>
                                <span>לפרטים</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($secondhandAds): ?>
        <section class="site-search-section">
            <h2 class="site-search-section-title">יד שנייה <span><?= count($secondhandAds) ?> תוצאות</span></h2>
            <div class="search-secondhand-grid">
                <?php foreach ($secondhandAds as $ad): ?>
                    <?php
                    $title = trim($ad['title'] ?? '') ?: 'מודעת יד שנייה';
                    $description = trim(strip_tags($ad['description'] ?? '')) ?: 'לא נוסף תיאור למודעה';
                    $locationParts = [];
                    if (!empty($ad['city_name']))
                        $locationParts[] = $ad['city_name'];
                    if (!empty($ad['region_name']))
                        $locationParts[] = $ad['region_name'];
                    $location = implode(' · ', $locationParts);
                    $categoryText = $ad['subcategory_name'] ?: ($ad['category_name'] ?? 'יד שנייה');
                    ?>

                    <a class="search-secondhand-card" href="/secondhand/view.php?id=<?= (int) $ad['id'] ?>">
                        <div class="search-secondhand-image">
                            <?php if (!empty($ad['image_path'])): ?>
                                <img src="<?= e($ad['image_path']) ?>" alt="<?= e($title) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="search-secondhand-no-image">🖼️</div>
                            <?php endif; ?>
                        </div>

                        <div class="search-secondhand-body">
                            <span class="search-secondhand-category"><?= e($categoryText) ?></span>
                            <h3 class="search-secondhand-title"><?= e($title) ?></h3>
                            <?php if ($location !== ''): ?>
                                <div class="search-secondhand-location">📍 <?= e($location) ?></div><?php endif; ?>
                            <p class="search-secondhand-desc"><?= e(mb_strimwidth($description, 0, 82, '...')) ?></p>

                            <div class="search-secondhand-price">
                                <?php if (!empty($ad['price']) && (float) $ad['price'] > 0): ?>
                                    <?= number_format((float) $ad['price']) ?> ₪
                                <?php else: ?>
                                    מחיר לא צוין
                                <?php endif; ?>
                                <?php if (!empty($ad['is_price_flexible'])): ?><small>מחיר גמיש</small><?php endif; ?>
                            </div>

                            <div class="search-secondhand-footer">
                                <span><?= (int) ($ad['views'] ?? 0) ?> צפיות 👁</span>
                                <strong>לפרטים</strong>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>