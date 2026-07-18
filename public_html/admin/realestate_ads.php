<?php
require_once '../includes/db.php';
require_once '../includes/admin_header.php';
require_once '../includes/admin_sidebar.php';
require_once '../includes/mail_templates.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function statusText($status)
{
    if ($status === 'active') {
        return 'מאושרת';
    }
    if ($status === 'inactive') {
        return 'נדחתה';
    }
    return 'ממתינה לאישור';
}

function statusClass($status)
{
    if ($status === 'active') {
        return 'approved';
    }
    if ($status === 'inactive') {
        return 'rejected';
    }
    return 'pending';
}

function dealTypeText($dealType)
{
    if ($dealType === 'sale') {
        return 'מכירה';
    }
    if ($dealType === 'rent') {
        return 'השכרה';
    }
    if ($dealType === 'roommates') {
        return 'שותפים';
    }
    if ($dealType === 'commercial') {
        return 'מסחרי';
    }
    return '';
}

function redirectBack($status, $q)
{
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

    $url = '/admin/realestate_ads.php';
    if ($params) {
        $url .= '?' . http_build_query($params);
    }

    header('Location: ' . $url);
    exit;
}

function activeTab($current, $value)
{
    return $current === $value ? ' active' : '';
}

function statusUrl($status, $q = '')
{
    $params = [];
    if ($status !== 'all') {
        $params['status'] = $status;
    }
    if ($q !== '') {
        $params['q'] = $q;
    }

    return '/admin/realestate_ads.php' . ($params ? '?' . http_build_query($params) : '');
}

function actionUrl($action, $id, $status, $q = '')
{
    $params = [
        'action' => $action,
        'id' => (int) $id,
    ];

    if ($status !== 'all') {
        $params['status'] = $status;
    }
    if ($q !== '') {
        $params['q'] = $q;
    }

    return '/admin/realestate_ads.php?' . http_build_query($params);
}

$status = $_GET['status'] ?? 'all';
$q = trim($_GET['q'] ?? '');

$allowedStatuses = ['all', 'pending', 'active', 'inactive'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'all';
}

$action = $_GET['action'] ?? '';
$id = (int) ($_GET['id'] ?? 0);

if ($id > 0 && in_array($action, ['approve', 'delete'], true)) {
    if ($action === 'delete') {
        $stmt = $pdo->prepare("UPDATE realestate_ads SET is_deleted = 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    if ($action === 'approve') {
        $stmt = $pdo->prepare("
            SELECT user_id, title
            FROM realestate_ads
            WHERE id = ?
              AND is_deleted = 0
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $adForMail = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($adForMail) {
            $stmt = $pdo->prepare("UPDATE realestate_ads SET status = 'active' WHERE id = ?");
            $stmt->execute([$id]);

            $mail = mailAdApproved(
                $pdo,
                (int) $adForMail['user_id'],
                $id,
                'realestate',
                $adForMail['title'] ?: 'מודעת נדל״ן'
            );

            if (!$mail['success']) {
                error_log('Realestate approval mail error: ' . $mail['error']);
            }
        }
    }

    redirectBack($status, $q);
}

$where = ['a.is_deleted = 0'];
$params = [];

if ($status !== 'all') {
    $where[] = 'a.status = ?';
    $params[] = $status;
}

if ($q !== '') {
    if (ctype_digit($q)) {
        $where[] = '(a.id = ? OR a.phone LIKE ? OR u.phone LIKE ?)';
        $params[] = (int) $q;
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
    } else {
        $where[] = '(a.phone LIKE ? OR u.phone LIKE ? OR a.title LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
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
    FROM realestate_ads
    WHERE is_deleted = 0
");
$counts = $countStmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT
        a.id,
        a.user_id,
        a.title,
        a.price,
        a.is_price_flexible,
        a.deal_type,
        a.property_type,
        a.rooms,
        a.square_meters,
        a.floor,
        a.parking_spaces,
        a.views,
        a.status,
        a.created_at,
        a.phone AS ad_phone,
        r.name AS region_name,
        c.name AS city_name,
        u.name AS user_name,
        u.email AS user_email,
        u.phone AS user_phone,
        (
            SELECT image_path
            FROM realestate_images
            WHERE ad_id = a.id
            ORDER BY is_main DESC, sort_order ASC, id ASC
            LIMIT 1
        ) AS image_path
    FROM realestate_ads a
    LEFT JOIN regions r ON r.id = a.region_id
    LEFT JOIN cities c ON c.id = a.city_id
    LEFT JOIN users u ON u.id = a.user_id
    WHERE {$whereSql}
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
?>

<link rel="stylesheet" href="/vehicle/css/vehicle_list.css">

<style>
    .realestate-admin-page {
        direction: rtl;
    }

    .realestate-admin-page .vehicle-list-header {
        margin-bottom: 18px;
    }

    .realestate-admin-page .vehicle-list-header h1 {
        margin: 0;
    }

    .realestate-admin-tabs {
        display: grid;
        grid-template-columns: repeat(4, minmax(120px, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .realestate-admin-tab {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 13px 15px;
        color: #111827;
        text-decoration: none;
        font-weight: 900;
    }

    .realestate-admin-tab.active {
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, .13);
    }

    .realestate-admin-tab span {
        background: #f3f4f6;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 13px;
    }

    .realestate-admin-tab.pending span {
        background: #fff3cd;
        color: #8a6d00;
    }

    .realestate-admin-tab.approved span {
        background: #d1fae5;
        color: #065f46;
    }

    .realestate-admin-tab.rejected span {
        background: #fee2e2;
        color: #991b1b;
    }

    .realestate-admin-search {
        display: flex;
        gap: 10px;
        margin: 0 0 20px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 12px;
        align-items: center;
    }

    .realestate-admin-search input {
        width: 360px;
        max-width: 100%;
        height: 42px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        padding: 0 14px;
        font-size: 15px;
    }

    .realestate-admin-search button {
        height: 42px;
        padding: 0 18px;
        border: 0;
        border-radius: 10px;
        background: #2563eb;
        color: #fff;
        cursor: pointer;
        font-weight: 900;
    }

    .realestate-admin-search a {
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

    .realestate-admin-page .vehicle-card {
        cursor: default;
        text-decoration: none;
        min-width: 0;
    }

    .realestate-admin-page .vehicle-card:hover {
        transform: none;
    }

    .realestate-admin-page .vehicle-card-body h2 {
        overflow-wrap: anywhere;
        word-break: break-word;
        white-space: normal;
    }

    .realestate-admin-status {
        display: inline-block;
        margin-top: 8px;
        padding: 7px 14px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 900;
    }

    .realestate-admin-status.pending {
        background: #fff3cd;
        color: #8a6d00;
    }

    .realestate-admin-status.approved {
        background: #d1fae5;
        color: #065f46;
    }

    .realestate-admin-status.rejected {
        background: #fee2e2;
        color: #991b1b;
    }

    .realestate-admin-owner {
        margin-top: 8px;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.65;
        overflow-wrap: anywhere;
    }

    .realestate-admin-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 14px;
        padding-top: 12px;
        border-top: 1px solid #e5e7eb;
    }

    .realestate-admin-actions a {
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
        color: #fff;
    }

    .admin-btn-delete {
        background: #111827;
        color: #fff;
    }

    .realestate-admin-page .empty-state {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 35px;
        text-align: center;
        color: #6b7280;
        font-weight: 800;
    }

    .realestate-property-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        z-index: 2;
        padding: 7px 10px;
        border-radius: 9px;
        background: #2563eb;
        color: #fff;
        font-size: 12px;
        font-weight: 900;
    }

    @media (max-width: 800px) {
        .realestate-admin-tabs {
            grid-template-columns: repeat(2, 1fr);
        }

        .realestate-admin-search {
            flex-direction: column;
            align-items: stretch;
        }

        .realestate-admin-search input {
            width: 100%;
        }
    }
</style>

<section class="vehicle-list-page realestate-admin-page">
    <div class="vehicle-list-header">
        <div>
            <h1>ניהול מודעות נדל״ן</h1>
            <p>סה״כ <?= (int) ($counts['total'] ?? 0) ?> מודעות פעילות במערכת</p>
        </div>
    </div>

    <div class="realestate-admin-tabs">
        <a class="realestate-admin-tab<?= activeTab($status, 'all') ?>" href="<?= h(statusUrl('all', $q)) ?>">
            הכל
            <span><?= (int) ($counts['total'] ?? 0) ?></span>
        </a>
        <a class="realestate-admin-tab pending<?= activeTab($status, 'pending') ?>"
            href="<?= h(statusUrl('pending', $q)) ?>">
            ממתינות
            <span><?= (int) ($counts['pending'] ?? 0) ?></span>
        </a>
        <a class="realestate-admin-tab approved<?= activeTab($status, 'active') ?>"
            href="<?= h(statusUrl('active', $q)) ?>">
            מאושרות
            <span><?= (int) ($counts['approved'] ?? 0) ?></span>
        </a>
        <a class="realestate-admin-tab rejected<?= activeTab($status, 'inactive') ?>"
            href="<?= h(statusUrl('inactive', $q)) ?>">
            נדחו
            <span><?= (int) ($counts['rejected'] ?? 0) ?></span>
        </a>
    </div>

    <form class="realestate-admin-search" method="get" action="/admin/realestate_ads.php">
        <?php if ($status !== 'all'): ?>
            <input type="hidden" name="status" value="<?= h($status) ?>">
        <?php endif; ?>

        <input type="text" name="q" value="<?= h($q) ?>" placeholder="מספר מודעה, טלפון או כותרת">
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
                $title = trim($ad['title'] ?? '') ?: 'מודעת נדל״ן';
                $price = !empty($ad['price'])
                    ? '₪' . number_format((float) $ad['price'])
                    : 'מחיר לא צוין';
                $created = !empty($ad['created_at'])
                    ? date('d/m/Y', strtotime($ad['created_at']))
                    : '-';
                $phone = $ad['ad_phone'] ?: ($ad['user_phone'] ?? '');
                $location = trim(($ad['city_name'] ?? '') . (($ad['city_name'] ?? '') && ($ad['region_name'] ?? '') ? ' · ' : '') . ($ad['region_name'] ?? ''));
                $dealText = dealTypeText($ad['deal_type'] ?? '');
                ?>

                <div class="vehicle-card">
                    <div class="vehicle-card-image">
                        <?php if (!empty($ad['image_path'])): ?>
                            <img src="<?= h($ad['image_path']) ?>" alt="">
                        <?php else: ?>
                            <div class="no-image">🏠</div>
                        <?php endif; ?>

                        <?php if (!empty($ad['property_type'])): ?>
                            <span class="realestate-property-badge"><?= h($ad['property_type']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="vehicle-card-body">
                        <h2><?= h($title) ?></h2>

                        <span class="realestate-admin-status <?= h(statusClass($ad['status'] ?? 'pending')) ?>">
                            <?= h(statusText($ad['status'] ?? 'pending')) ?>
                        </span>

                        <div class="vehicle-meta">
                            <?php if ($dealText !== ''): ?><span>🏷 <?= h($dealText) ?></span><?php endif; ?>
                            <?php if ($location !== ''): ?><span>📍 <?= h($location) ?></span><?php endif; ?>
                        </div>

                        <div class="vehicle-meta">
                            <?php if ($ad['rooms'] !== null && $ad['rooms'] !== ''): ?><span>🛏 <?= h($ad['rooms']) ?>
                                    חדרים</span><?php endif; ?>
                            <?php if (!empty($ad['square_meters'])): ?><span>📐 <?= number_format((int) $ad['square_meters']) ?>
                                    מ״ר</span><?php endif; ?>
                            <?php if ($ad['floor'] !== null && $ad['floor'] !== ''): ?><span>🏢 קומה
                                    <?= h($ad['floor']) ?></span><?php endif; ?>
                            <?php if ((int) ($ad['parking_spaces'] ?? 0) > 0): ?><span>🚗 <?= (int) $ad['parking_spaces'] ?>
                                    חניות</span><?php endif; ?>
                        </div>

                        <div class="vehicle-price">
                            <?= h($price) ?>
                            <?php if ((int) ($ad['is_price_flexible'] ?? 0) === 1): ?>
                                <small>גמיש</small>
                            <?php endif; ?>
                        </div>

                        <div class="realestate-admin-owner">
                            מספר מודעה: <?= (int) $ad['id'] ?>
                            <?php if ($phone !== ''): ?> | טלפון: <?= h($phone) ?><?php endif; ?>
                            | מפרסם: <?= h($ad['user_name'] ?? '-') ?>
                            | פורסם: <?= h($created) ?>
                            | צפיות: <?= number_format((int) ($ad['views'] ?? 0)) ?>
                        </div>

                        <div class="realestate-admin-actions">
                            <a class="admin-btn-view" href="/realestate/view.php?id=<?= (int) $ad['id'] ?>" target="_blank">👁
                                צפה</a>

                            <?php if (($ad['status'] ?? '') === 'pending'): ?>
                                <a class="admin-btn-approve" href="<?= h(actionUrl('approve', (int) $ad['id'], $status, $q)) ?>"
                                    onclick="return confirm('לאשר את המודעה?');">✔ אשר</a>
                            <?php endif; ?>

                            <a class="admin-btn-delete" href="<?= h(actionUrl('delete', (int) $ad['id'], $status, $q)) ?>"
                                onclick="return confirm('למחוק את המודעה?');">🗑 מחק</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once '../includes/admin_footer.php'; ?>