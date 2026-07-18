<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

if (empty($_SESSION['user_id'])) {
    header('Location: /login.php?redirect=/my_ads.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function status_label(string $status): array
{
    switch ($status) {
        case 'active':
        case 'approved':
            return ['פעילה', 'active'];
        case 'pending':
            return ['ממתינה לאישור', 'pending'];
        case 'inactive':
        case 'rejected':
            return ['לא פעילה', 'inactive'];
        case 'deleted':
            return ['נמחקה', 'inactive'];
        default:
            return ['לא ידוע', 'inactive'];
    }
}

function format_ad_date(?string $date): string
{
    if (!$date)
        return '';
    $timestamp = strtotime($date);
    return $timestamp ? date('d/m/Y', $timestamp) : '';
}

function normalize_price($price): ?int
{
    if ($price === null || $price === '' || (int) $price <= 0)
        return null;
    return (int) $price;
}

$ads = [];

$vehicleStmt = $pdo->prepare("
    SELECT
        a.id, a.title, a.price, a.year, a.km, a.hand,
        a.views, a.status, a.created_at,
        m.name AS maker_name,
        cm.name AS model_name,
        (
            SELECT vi.image_path
            FROM vehicle_images vi
            WHERE vi.ad_id = a.id
            ORDER BY vi.is_main DESC, vi.sort_order ASC, vi.id ASC
            LIMIT 1
        ) AS image_path
    FROM vehicle_ads a
    LEFT JOIN car_makers m ON m.id = a.manufacturer_id
    LEFT JOIN car_models cm ON cm.id = a.model_id
    WHERE a.user_id = ? AND a.is_deleted = 0
    ORDER BY a.id DESC
");
$vehicleStmt->execute([$user_id]);

foreach ($vehicleStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $title = trim(($row['maker_name'] ?? '') . ' ' . ($row['model_name'] ?? ''));
    if ($title === '')
        $title = trim((string) ($row['title'] ?? ''));
    if ($title === '')
        $title = 'מודעת רכב';

    $meta = [];
    if (!empty($row['year']))
        $meta[] = (int) $row['year'];
    if ($row['hand'] !== null && $row['hand'] !== '')
        $meta[] = 'יד ' . (int) $row['hand'];
    if (!empty($row['km']))
        $meta[] = number_format((int) $row['km']) . ' ק״מ';

    $ads[] = [
        'id' => (int) $row['id'],
        'type_text' => 'רכב',
        'icon' => '🚗',
        'title' => $title,
        'price' => normalize_price($row['price'] ?? null),
        'views' => (int) ($row['views'] ?? 0),
        'status' => (string) ($row['status'] ?? ''),
        'created_at' => $row['created_at'] ?? null,
        'image_path' => $row['image_path'] ?? null,
        'meta' => $meta,
        'view_url' => '/vehicle/view.php?id=' . (int) $row['id'],
        'edit_url' => '/vehicle/edit.php?id=' . (int) $row['id'],
        'delete_url' => '/vehicle/delete.php?id=' . (int) $row['id'],
    ];
}

$secondhandStmt = $pdo->prepare("
    SELECT
        a.id, a.title, a.price, a.item_condition,
        a.views, a.status, a.created_at,
        c.name AS category_name,
        sc.name AS subcategory_name,
        (
            SELECT si.image_path
            FROM secondhand_images si
            WHERE si.ad_id = a.id
            ORDER BY si.is_main DESC, si.sort_order ASC, si.id ASC
            LIMIT 1
        ) AS image_path
    FROM secondhand_ads a
    LEFT JOIN categories c ON c.id = a.category_id
    LEFT JOIN categories sc ON sc.id = a.subcategory_id
    WHERE a.user_id = ? AND a.is_deleted = 0
    ORDER BY a.id DESC
");
$secondhandStmt->execute([$user_id]);

$conditionLabels = [
    'new' => 'חדש',
    'like_new' => 'כמו חדש',
    'used' => 'משומש',
    'broken' => 'דורש תיקון',
];

foreach ($secondhandStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $title = trim((string) ($row['title'] ?? '')) ?: 'מודעת יד שנייה';
    $meta = [];
    if (!empty($row['subcategory_name']))
        $meta[] = $row['subcategory_name'];
    elseif (!empty($row['category_name']))
        $meta[] = $row['category_name'];
    if (!empty($row['item_condition']))
        $meta[] = $conditionLabels[$row['item_condition']] ?? $row['item_condition'];

    $ads[] = [
        'id' => (int) $row['id'],
        'type_text' => 'יד שנייה',
        'icon' => '📦',
        'title' => $title,
        'price' => normalize_price($row['price'] ?? null),
        'views' => (int) ($row['views'] ?? 0),
        'status' => (string) ($row['status'] ?? ''),
        'created_at' => $row['created_at'] ?? null,
        'image_path' => $row['image_path'] ?? null,
        'meta' => $meta,
        'view_url' => '/secondhand/view.php?id=' . (int) $row['id'],
        'edit_url' => '/secondhand/edit.php?id=' . (int) $row['id'],
        'delete_url' => '/secondhand/delete.php?id=' . (int) $row['id'],
    ];
}

$realestateStmt = $pdo->prepare("
    SELECT
        a.id, a.title, a.price, a.rooms,
        a.views, a.status, a.created_at,
        (
            SELECT ri.image_path
            FROM realestate_images ri
            WHERE ri.ad_id = a.id
            ORDER BY ri.is_main DESC, ri.sort_order ASC, ri.id ASC
            LIMIT 1
        ) AS image_path
    FROM realestate_ads a
    WHERE a.user_id = ? AND a.is_deleted = 0
    ORDER BY a.id DESC
");
$realestateStmt->execute([$user_id]);

foreach ($realestateStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $title = trim((string) ($row['title'] ?? '')) ?: 'מודעת נדל״ן';
    $meta = [];
    if ($row['rooms'] !== null && $row['rooms'] !== '') {
        $meta[] = rtrim(rtrim(number_format((float) $row['rooms'], 1), '0'), '.') . ' חדרים';
    }

    $ads[] = [
        'id' => (int) $row['id'],
        'type_text' => 'נדל״ן',
        'icon' => '🏠',
        'title' => $title,
        'price' => normalize_price($row['price'] ?? null),
        'views' => (int) ($row['views'] ?? 0),
        'status' => (string) ($row['status'] ?? ''),
        'created_at' => $row['created_at'] ?? null,
        'image_path' => $row['image_path'] ?? null,
        'meta' => $meta,
        'view_url' => '/realestate/view.php?id=' . (int) $row['id'],
        'edit_url' => '/realestate/edit.php?id=' . (int) $row['id'],
        'delete_url' => '/realestate/delete.php?id=' . (int) $row['id'],
    ];
}

usort($ads, static function (array $a, array $b): int {
    return strtotime((string) $b['created_at']) <=> strtotime((string) $a['created_at']);
});
?>

<link rel="stylesheet" href="/css/my_ads.css?v=20260718_all_ads">

<section class="my-ads-page">
    <div class="my-ads-header">
        <div>
            <h1>המודעות שלי</h1>
            <p>כאן אפשר לנהל, לערוך ולצפות בכל המודעות שפרסמת.</p>
        </div>
        <a href="/post_ad.php" class="my-ads-post-btn">פרסם מודעה חדשה</a>
    </div>

    <?php if (!$ads): ?>
        <div class="my-ads-empty">
            <h2>עדיין אין לך מודעות</h2>
            <p>אפשר לפרסם מודעה חדשה ולהתחיל לקבל פניות.</p>
            <a href="/post_ad.php">פרסם מודעה</a>
        </div>
    <?php else: ?>
        <div class="my-ads-list">
            <?php foreach ($ads as $ad): ?>
                <?php
                [$statusText, $statusClass] = status_label($ad['status']);
                $publishedDate = format_ad_date($ad['created_at']);
                ?>

                <div class="my-ad-row">
                    <div class="my-ad-image">
                        <?php if (!empty($ad['image_path'])): ?>
                            <img src="<?= h($ad['image_path']) ?>" alt="<?= h($ad['title']) ?>">
                        <?php else: ?>
                            <div class="my-ad-no-image"><?= h($ad['icon']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="my-ad-content">
                        <div class="my-ad-top">
                            <h2><?= h($ad['title']) ?></h2>
                            <span class="status-badge <?= h($statusClass) ?>"><?= h($statusText) ?></span>
                        </div>

                        <div class="my-ad-title"><?= h($ad['icon'] . ' ' . $ad['type_text']) ?></div>

                        <div class="my-ad-meta">
                            <?php if ($ad['price'] !== null): ?>
                                <span>₪ <?= number_format($ad['price']) ?></span>
                            <?php else: ?>
                                <span>מחיר לא צוין</span>
                            <?php endif; ?>

                            <?php foreach ($ad['meta'] as $metaItem): ?>
                                <span><?= h($metaItem) ?></span>
                            <?php endforeach; ?>

                            <span><?= number_format($ad['views']) ?> צפיות</span>
                        </div>

                        <?php if ($publishedDate !== ''): ?>
                            <div class="my-ad-date">פורסם בתאריך: <?= h($publishedDate) ?></div>
                        <?php endif; ?>

                        <div class="my-ad-actions">
                            <a href="<?= h($ad['view_url']) ?>">צפייה</a>
                            <a href="<?= h($ad['edit_url']) ?>">עריכה</a>
                            <a href="<?= h($ad['delete_url']) ?>" class="danger"
                                onclick="return confirm('למחוק את המודעה?');">מחיקה</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>