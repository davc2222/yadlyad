<?php
require_once '../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$q = trim($_GET['q'] ?? '');
$categoryId = (int)($_GET['category_id'] ?? 0);
$subcategoryId = (int)($_GET['subcategory_id'] ?? 0);
$regionId = (int)($_GET['region_id'] ?? 0);
$cityId = (int)($_GET['city_id'] ?? 0);
$condition = trim($_GET['condition'] ?? '');
$maxPrice = trim($_GET['max_price'] ?? '');

$where = ["a.is_deleted = 0", "a.status IN ('approved','active')"];
$params = [];

if ($categoryId > 0) { $where[] = "a.category_id = ?"; $params[] = $categoryId; }
if ($subcategoryId > 0) { $where[] = "a.subcategory_id = ?"; $params[] = $subcategoryId; }
if ($regionId > 0) { $where[] = "a.region_id = ?"; $params[] = $regionId; }
if ($cityId > 0) { $where[] = "a.city_id = ?"; $params[] = $cityId; }
if (in_array($condition, ['new','like_new','used','broken'], true)) { $where[] = "a.item_condition = ?"; $params[] = $condition; }
if ($maxPrice !== '' && is_numeric($maxPrice) && (float)$maxPrice > 0) { $where[] = "a.price > 0 AND a.price <= ?"; $params[] = (float)$maxPrice; }
if ($q !== '') {
    $where[] = "(a.title LIKE ? OR a.description LIKE ? OR cat.name LIKE ? OR subcat.name LIKE ? OR r.name LIKE ? OR c.name LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like, $like);
}

$sql = "
SELECT a.*, cat.name AS category_name, subcat.name AS subcategory_name,
       r.name AS region_name, c.name AS city_name,
       img.image_path, img.image_name
FROM secondhand_ads a
LEFT JOIN categories cat ON cat.id = a.category_id
LEFT JOIN categories subcat ON subcat.id = a.subcategory_id
LEFT JOIN regions r ON r.id = a.region_id
LEFT JOIN cities c ON c.id = a.city_id
LEFT JOIN (
    SELECT si1.ad_id, si1.image_path, si1.image_name
    FROM secondhand_images si1
    INNER JOIN (
        SELECT ad_id, MIN(id) AS min_id
        FROM secondhand_images
        GROUP BY ad_id
    ) si2 ON si2.ad_id = si1.ad_id AND si2.min_id = si1.id
) img ON img.ad_id = a.id
WHERE " . implode(' AND ', $where) . "
ORDER BY a.id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ob_start();

    if (!$ads): ?>
        <section class="secondhand-empty">
            <h2>לא נמצאו מודעות</h2>
            <p>נסה לשנות את החיפוש או לנקות את הסינונים.</p>
        </section>
    <?php else: ?>
        <section class="secondhand-grid">
            <?php foreach ($ads as $ad):
                $title = trim($ad['title'] ?? '') ?: 'מודעת יד שנייה';
                $img = !empty($ad['image_path']) ? $ad['image_path'] : (!empty($ad['image_name']) ? '/uploads/secondhand/' . $ad['image_name'] : '');
                $description = trim(strip_tags($ad['description'] ?? '')) ?: 'לא נוסף תיאור למודעה';
                $locationParts = [];
                if (!empty($ad['city_name'])) $locationParts[] = $ad['city_name'];
                if (!empty($ad['region_name'])) $locationParts[] = $ad['region_name'];
                $location = implode(' · ', $locationParts);
                $hasPrice = !empty($ad['price']) && (float)$ad['price'] > 0;
                $isFlexible = !empty($ad['is_price_flexible']);
            ?>
                <a class="shv-card" href="/secondhand/view.php?id=<?= (int)$ad['id'] ?>">
                    <div class="shv-card-image">
                        <?php if ($img !== ''): ?>
                            <img src="<?= e($img) ?>" alt="<?= e($title) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="shv-no-image">
                                <svg class="shv-no-image-icon" viewBox="0 0 24 24" fill="none">
                                    <rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.7"/>
                                    <circle cx="8" cy="9" r="1.5" fill="currentColor"/>
                                    <path d="M6 17L11 12L14 15L18 10L20 17" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="shv-card-body">
                        <h3 class="shv-card-title"><?= e($title) ?></h3>
                        <?php if ($location !== ''): ?>
                            <div class="shv-card-location"><span>📍</span><span><?= e($location) ?></span></div>
                        <?php endif; ?>
                        <div class="shv-card-description-title">תיאור המודעה</div>
                        <p class="shv-card-description"><?= e(mb_strimwidth($description, 0, 82, '...')) ?></p>
                        <div class="shv-price-row">
                            <div class="shv-price<?= $hasPrice ? '' : ' shv-price-empty' ?>">
                                <?= $hasPrice ? number_format((float)$ad['price']) . ' ₪' : 'מחיר לא צוין' ?>
                            </div>
                            <?php if ($isFlexible): ?><span class="shv-flexible-price">מחיר גמיש</span><?php endif; ?>
                        </div>
                    </div>
                    <div class="shv-card-footer">
                        <span class="shv-views"><?= (int)($ad['views'] ?? 0) ?> צפיות 👁</span>
                        <span class="shv-details">לפרטים</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </section>
    <?php endif;

    echo json_encode(['success'=>true,'count'=>count($ads),'html'=>ob_get_clean()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('Secondhand AJAX search error: ' . $e->getMessage());
    echo json_encode(['success'=>false,'count'=>0,'html'=>'<section class="secondhand-empty"><h2>אירעה שגיאה בחיפוש</h2></section>','error'=>'Search failed'], JSON_UNESCAPED_UNICODE);
}