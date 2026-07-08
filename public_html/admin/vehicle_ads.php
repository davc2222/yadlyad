<?php
require_once '../includes/db.php';
require_once '../includes/admin_header.php';
require_once '../includes/admin_sidebar.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function statusText($status) {
    if ($status === 'active') return 'מאושרת';
    if ($status === 'inactive') return 'נדחתה';
    return 'ממתינה לאישור';
}

function statusClass($status) {
    if ($status === 'active') return 'approved';
    if ($status === 'inactive') return 'rejected';
    return 'pending';
}

function redirectBack($status, $q) {
    $allowed = ['all', 'pending', 'active', 'inactive'];
    if (!in_array($status, $allowed, true)) {
        $status = 'all';
    }

    $params = [];

    if ($status !== 'all') {
        $params['status'] = $status;
    }

    if ($q !== '') {
        $params['q'] = $q;
    }

    $url = '/admin/vehicle_ads.php';
    if ($params) {
        $url .= '?' . http_build_query($params);
    }

    header('Location: ' . $url);
    exit;
}

$status = $_GET['status'] ?? 'all';
$q = trim($_GET['q'] ?? '');

$allowedStatuses = ['all', 'pending', 'active', 'inactive'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'all';
}

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($id > 0 && in_array($action, ['approve', 'delete'], true)) {

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE vehicle_ads SET status = 'active' WHERE id = ?");
        $stmt->execute([$id]);
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("UPDATE vehicle_ads SET is_deleted = 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    redirectBack($status, $q);
}

$where = ["a.is_deleted = 0"];
$params = [];

if ($status !== 'all') {
    $where[] = "a.status = ?";
    $params[] = $status;
}

if ($q !== '') {
    if (ctype_digit($q)) {
        $where[] = "(a.id = ? OR a.phone LIKE ? OR u.phone LIKE ?)";
        $params[] = (int)$q;

        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
    } else {
        $where[] = "(a.phone LIKE ? OR u.phone LIKE ?)";
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
    }
}

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'pending') AS pending,
        SUM(status = 'active') AS approved,
        SUM(status = 'inactive') AS rejected
    FROM vehicle_ads
    WHERE is_deleted = 0
");

$counts = $countStmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT
        a.id,
        a.title,
        a.price,
        a.year,
        a.km,
        a.hand,
        a.views,
        a.status,
        a.created_at,
        a.is_price_flexible,
        a.phone AS ad_phone,
        m.name AS maker_name,
        cm.name AS model_name,
        g.name AS gearbox_name,
        f.name AS fuel_name,
        u.name AS user_name,
        u.email AS user_email,
        u.phone AS user_phone,
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
    LEFT JOIN users u ON u.id = a.user_id
    WHERE $whereSql
    ORDER BY
        CASE a.status
            WHEN 'pending' THEN 1
            WHEN 'active' THEN 2
            WHEN 'inactive' THEN 3
            ELSE 4
        END,
        a.id DESC
");

$stmt->execute($params);
$ads = $stmt->fetchAll(PDO::FETCH_ASSOC);

function activeTab($current, $value) {
    return $current === $value ? ' active' : '';
}

function statusUrl($status, $q = '') {
    $params = [];

    if ($status !== 'all') {
        $params['status'] = $status;
    }

    if ($q !== '') {
        $params['q'] = $q;
    }

    return '/admin/vehicle_ads.php' . ($params ? '?' . http_build_query($params) : '');
}

function actionUrl($action, $id, $status, $q = '') {
    $params = [
        'action' => $action,
        'id' => (int)$id
    ];

    if ($status !== 'all') {
        $params['status'] = $status;
    }

    if ($q !== '') {
        $params['q'] = $q;
    }

    return '/admin/vehicle_ads.php?' . http_build_query($params);
}
?>

<link rel="stylesheet" href="/vehicle/css/vehicle_list.css">

<style>
.vehicle-admin-page {
    direction: rtl;
}

.vehicle-admin-page .vehicle-list-header {
    margin-bottom: 18px;
}

.vehicle-admin-page .vehicle-list-header h1 {
    margin: 0;
}

.vehicle-admin-tabs {
    display: grid;
    grid-template-columns: repeat(4, minmax(120px, 1fr));
    gap: 12px;
    margin-bottom: 18px;
}

.vehicle-admin-tab {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 13px 15px;
    color: #111827;
    text-decoration: none;
    font-weight: 900;
}

.vehicle-admin-tab.active {
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, .13);
}

.vehicle-admin-tab span {
    background: #f3f4f6;
    border-radius: 999px;
    padding: 4px 10px;
    font-size: 13px;
}

.vehicle-admin-tab.pending span {
    background: #fff3cd;
    color: #8a6d00;
}

.vehicle-admin-tab.approved span {
    background: #d1fae5;
    color: #065f46;
}

.vehicle-admin-tab.rejected span {
    background: #fee2e2;
    color: #991b1b;
}

.vehicle-admin-search {
    display: flex;
    gap: 10px;
    margin: 0 0 20px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 12px;
    align-items: center;
}

.vehicle-admin-search input {
    width: 360px;
    max-width: 100%;
    height: 42px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    padding: 0 14px;
    font-size: 15px;
}

.vehicle-admin-search button {
    height: 42px;
    padding: 0 18px;
    border: 0;
    border-radius: 10px;
    background: #2563eb;
    color: #ffffff;
    cursor: pointer;
    font-weight: 900;
}

.vehicle-admin-search a {
    height: 42px;
    padding: 0 14px;
    border-radius: 10px;
    background: #f3f4f6;
    color: #374151;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    font-weight: 800;
}

.vehicle-admin-page .vehicle-card {
    cursor: default;
    text-decoration: none;
}

.vehicle-admin-page .vehicle-card:hover {
    transform: none;
}

.vehicle-admin-status {
    display: inline-block;
    margin-top: 8px;
    padding: 7px 14px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 900;
}

.vehicle-admin-status.pending {
    background: #fff3cd;
    color: #8a6d00;
}

.vehicle-admin-status.approved {
    background: #d1fae5;
    color: #065f46;
}

.vehicle-admin-status.rejected {
    background: #fee2e2;
    color: #991b1b;
}

.vehicle-admin-owner {
    margin-top: 8px;
    color: #6b7280;
    font-size: 13px;
}

.vehicle-admin-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid #e5e7eb;
}

.vehicle-admin-actions a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 34px;
    padding: 8px 13px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 900;
    text-decoration: none;
}

.admin-btn-view {
    background: #dbeafe;
    color: #1d4ed8;
}

.admin-btn-approve {
    background: #16a34a;
    color: #ffffff;
}

.admin-btn-delete {
    background: #111827;
    color: #ffffff;
}

.vehicle-admin-page .empty-state {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 35px;
    text-align: center;
    color: #6b7280;
    font-weight: 800;
}

@media (max-width: 800px) {
    .vehicle-admin-tabs {
        grid-template-columns: repeat(2, 1fr);
    }

    .vehicle-admin-search {
        flex-direction: column;
        align-items: stretch;
    }

    .vehicle-admin-search input {
        width: 100%;
    }
}
</style>

<section class="vehicle-list-page vehicle-admin-page">

    <div class="vehicle-list-header">
        <div>
            <h1>ניהול מודעות רכב</h1>
            <p>סה״כ <?= (int)($counts['total'] ?? 0) ?> מודעות פעילות במערכת</p>
        </div>
    </div>

    <div class="vehicle-admin-tabs">
        <a class="vehicle-admin-tab<?= activeTab($status, 'all') ?>" href="<?= h(statusUrl('all', $q)) ?>">
            הכל
            <span><?= (int)($counts['total'] ?? 0) ?></span>
        </a>

        <a class="vehicle-admin-tab pending<?= activeTab($status, 'pending') ?>" href="<?= h(statusUrl('pending', $q)) ?>">
            ממתינות
            <span><?= (int)($counts['pending'] ?? 0) ?></span>
        </a>

        <a class="vehicle-admin-tab approved<?= activeTab($status, 'active') ?>" href="<?= h(statusUrl('active', $q)) ?>">
            מאושרות
            <span><?= (int)($counts['approved'] ?? 0) ?></span>
        </a>

        <a class="vehicle-admin-tab rejected<?= activeTab($status, 'inactive') ?>" href="<?= h(statusUrl('inactive', $q)) ?>">
            נדחו
            <span><?= (int)($counts['rejected'] ?? 0) ?></span>
        </a>
    </div>

    <form class="vehicle-admin-search" method="get" action="/admin/vehicle_ads.php">

        <?php if ($status !== 'all'): ?>
            <input type="hidden" name="status" value="<?= h($status) ?>">
        <?php endif; ?>

        <input
            type="text"
            name="q"
            value="<?= h($q) ?>"
            placeholder="מספר מודעה או טלפון">

        <button type="submit">🔍 חפש</button>

        <?php if ($q !== ''): ?>
            <a href="<?= h(statusUrl($status, '')) ?>">נקה</a>
        <?php endif; ?>

    </form>

    <?php if (!$ads): ?>
        <div class="empty-state">אין מודעות להצגה.</div>
    <?php else: ?>

        <div class="vehicle-grid">
            <?php foreach ($ads as $ad): ?>
                <?php
                    $adTitle = trim(($ad['maker_name'] ?? '') . ' ' . ($ad['model_name'] ?? ''));

                    if ($adTitle === '') {
                        $adTitle = $ad['title'] ?: 'מודעת רכב';
                    }

                    $price = !empty($ad['price'])
                        ? '₪' . number_format((int)$ad['price'])
                        : 'מחיר לא צוין';

                    $created = !empty($ad['created_at'])
                        ? date('d/m/Y', strtotime($ad['created_at']))
                        : '-';

                    $phone = $ad['ad_phone'] ?: ($ad['user_phone'] ?? '');
                ?>

                <div class="vehicle-card">

                    <div class="vehicle-card-image">
                        <?php if (!empty($ad['image_path'])): ?>
                            <img src="<?= h($ad['image_path']) ?>" alt="">
                        <?php else: ?>
                            <div class="no-image">🚗</div>
                        <?php endif; ?>

                        <?php if (!empty($ad['year'])): ?>
                            <span class="year-badge"><?= (int)$ad['year'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="vehicle-card-body">
                        <h2><?= h($adTitle) ?></h2>

                        <span class="vehicle-admin-status <?= h(statusClass($ad['status'] ?? 'pending')) ?>">
                            <?= h(statusText($ad['status'] ?? 'pending')) ?>
                        </span>

                        <div class="vehicle-meta">
                            <?php if (!empty($ad['year'])): ?>
                                <span>📅 <?= h($ad['year']) ?></span>
                            <?php endif; ?>

                            <?php if (!empty($ad['hand'])): ?>
                                <span>🤝 יד <?= h($ad['hand']) ?></span>
                            <?php endif; ?>

                            <?php if (!empty($ad['km'])): ?>
                                <span>🛣 <?= number_format((int)$ad['km']) ?> ק״מ</span>
                            <?php endif; ?>
                        </div>

                        <div class="vehicle-meta">
                            <?php if (!empty($ad['gearbox_name'])): ?>
                                <span>⚙ <?= h($ad['gearbox_name']) ?></span>
                            <?php endif; ?>

                            <?php if (!empty($ad['fuel_name'])): ?>
                                <span>⛽ <?= h($ad['fuel_name']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="vehicle-price">
                            <?= h($price) ?>

                            <?php if ((int)($ad['is_price_flexible'] ?? 0) === 1): ?>
                                <small>גמיש</small>
                            <?php endif; ?>
                        </div>

                        <div class="vehicle-admin-owner">
                            מספר מודעה: <?= (int)$ad['id'] ?>
                            <?php if ($phone !== ''): ?>
                                | טלפון: <?= h($phone) ?>
                            <?php endif; ?>
                            | מפרסם: <?= h($ad['user_name'] ?? '-') ?>
                            | פורסם: <?= h($created) ?>
                            | צפיות: <?= number_format((int)($ad['views'] ?? 0)) ?>
                        </div>

                        <div class="vehicle-admin-actions">

                            <a class="admin-btn-view"
                               href="/vehicle/view.php?id=<?= (int)$ad['id'] ?>"
                               target="_blank">
                                👁 צפה
                            </a>

                            <?php if (($ad['status'] ?? '') === 'pending'): ?>
                                <a class="admin-btn-approve"
                                   href="<?= h(actionUrl('approve', (int)$ad['id'], $status, $q)) ?>"
                                   onclick="return confirm('לאשר את המודעה?');">
                                    ✔ אשר
                                </a>
                            <?php endif; ?>

                            <a class="admin-btn-delete"
                               href="<?= h(actionUrl('delete', (int)$ad['id'], $status, $q)) ?>"
                               onclick="return confirm('למחוק את המודעה?');">
                                🗑 מחק
                            </a>

                        </div>

                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</section>

<?php require_once '../includes/admin_footer.php'; ?>