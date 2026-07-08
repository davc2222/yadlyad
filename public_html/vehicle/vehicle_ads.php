<?php
require_once '../includes/db.php';
require_once '../includes/admin_header.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($id > 0 && in_array($action, ['approve', 'reject', 'delete'], true)) {
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE vehicle_ads SET status = 'approved' WHERE id = ?");
        $stmt->execute([$id]);
    }

    if ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE vehicle_ads SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$id]);
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("UPDATE vehicle_ads SET is_deleted = 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    header('Location: /admin/vehicle_ads.php');
    exit;
}

$status = $_GET['status'] ?? 'all';
$q = trim($_GET['q'] ?? '');

$where = ["va.is_deleted = 0"];
$params = [];

if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
    $where[] = "va.status = ?";
    $params[] = $status;
}

if ($q !== '') {
    $where[] = "(
        va.title LIKE ?
        OR cm.name LIKE ?
        OR cmo.name LIKE ?
        OR u.name LIKE ?
        OR u.email LIKE ?
        OR u.phone LIKE ?
        OR va.phone LIKE ?
    )";

    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like, $like, $like);
}

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'pending') AS pending,
        SUM(status = 'approved') AS approved,
        SUM(status = 'rejected') AS rejected
    FROM vehicle_ads
    WHERE is_deleted = 0
");
$counts = $countStmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT
        va.id,
        va.title,
        va.price,
        va.phone AS ad_phone,
        va.status,
        va.created_at,
        cm.name AS maker_name,
        cmo.name AS model_name,
        u.name AS user_name,
        u.email AS user_email,
        u.phone AS user_phone,
        img.image_path
    FROM vehicle_ads va
    LEFT JOIN car_makers cm ON cm.id = va.manufacturer_id
    LEFT JOIN car_models cmo ON cmo.id = va.model_id
    LEFT JOIN users u ON u.id = va.user_id
    LEFT JOIN vehicle_images img
        ON img.ad_id = va.id
       AND img.is_main = 1
    WHERE $whereSql
    ORDER BY
        CASE va.status
            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'rejected' THEN 3
            ELSE 4
        END,
        va.created_at DESC
");
$stmt->execute($params);
$ads = $stmt->fetchAll(PDO::FETCH_ASSOC);

function statusBadge($status) {
    if ($status === 'approved') return '<span class="status-badge approved">מאושרת</span>';
    if ($status === 'rejected') return '<span class="status-badge rejected">נדחתה</span>';
    return '<span class="status-badge pending">ממתינה</span>';
}

function activeTab($current, $value) {
    return $current === $value ? ' active' : '';
}
?>

<style>
.admin-vehicles-page {
    direction: rtl;
    max-width: 100%;
}

.admin-vehicles-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
}

.admin-vehicles-header h1 {
    margin: 0;
    font-size: 28px;
    font-weight: 800;
    color: #111827;
}

.admin-vehicles-header p {
    margin: 6px 0 0;
    color: #6b7280;
    font-size: 14px;
}

.admin-new-btn {
    background: #2563eb;
    color: white;
    text-decoration: none;
    padding: 10px 15px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 14px;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(4, minmax(130px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}

.stat-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 13px 15px;
    text-decoration: none;
    color: #111827;
    min-height: 62px;
}

.stat-card.active {
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37,99,235,.12);
}

.stat-card strong {
    font-size: 15px;
}

.stat-card span {
    background: #f3f4f6;
    border-radius: 999px;
    padding: 4px 10px;
    font-weight: 800;
    font-size: 13px;
}

.stat-card.pending span {
    background: #fff3cd;
    color: #8a6d00;
}

.stat-card.approved span {
    background: #d1fae5;
    color: #065f46;
}

.stat-card.rejected span {
    background: #fee2e2;
    color: #991b1b;
}

.admin-search-bar {
    display: flex;
    gap: 10px;
    margin-bottom: 16px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    padding: 12px;
    border-radius: 12px;
}

.admin-search-bar input {
    flex: 1;
    height: 38px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 0 12px;
    font-size: 14px;
}

.admin-search-bar button,
.admin-search-bar a {
    height: 38px;
    border: 0;
    border-radius: 8px;
    padding: 0 15px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}

.admin-search-bar button {
    background: #2563eb;
    color: white;
}

.admin-search-bar a {
    background: #f3f4f6;
    color: #374151;
}

.admin-table-wrap {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow-x: auto;
}

.admin-vehicle-table {
    width: 100%;
    min-width: 980px;
    border-collapse: collapse;
    font-size: 14px;
}

.admin-vehicle-table th,
.admin-vehicle-table td {
    padding: 11px 10px;
    border-bottom: 1px solid #e5e7eb;
    text-align: right;
    vertical-align: middle;
}

.admin-vehicle-table th {
    background: #f9fafb;
    color: #374151;
    font-size: 13px;
    font-weight: 800;
}

.admin-vehicle-table tr:last-child td {
    border-bottom: 0;
}

.vehicle-admin-thumb {
    width: 82px;
    height: 58px;
    object-fit: cover;
    border-radius: 8px;
    background: #e5e7eb;
    display: block;
}

.no-thumb {
    width: 82px;
    height: 58px;
    border-radius: 8px;
    background: #f3f4f6;
    color: #9ca3af;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ad-title strong {
    display: block;
    font-size: 15px;
    color: #111827;
}

.ad-title small {
    display: block;
    margin-top: 3px;
    color: #6b7280;
}

.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
}

.status-badge.pending {
    background: #fff3cd;
    color: #8a6d00;
}

.status-badge.approved {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.rejected {
    background: #fee2e2;
    color: #991b1b;
}

.actions-cell {
    white-space: nowrap;
}

.actions-cell a {
    display: inline-block;
    margin: 2px;
    padding: 6px 9px;
    border-radius: 7px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
}

.btn-view {
    background: #e5e7eb;
    color: #111827;
}

.btn-edit {
    background: #dbeafe;
    color: #1d4ed8;
}

.btn-approve {
    background: #dcfce7;
    color: #166534;
}

.btn-reject {
    background: #fee2e2;
    color: #991b1b;
}

.btn-delete {
    background: #111827;
    color: white;
}

.empty-box {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    padding: 35px;
    border-radius: 12px;
    text-align: center;
    color: #6b7280;
}

@media (max-width: 900px) {
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }

    .admin-vehicles-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .admin-search-bar {
        flex-direction: column;
    }
}
</style>

<div class="admin-vehicles-page">

    <div class="admin-vehicles-header">
        <div>
            <h1>מודעות רכב</h1>
            <p>ניהול, אישור ודחייה של מודעות רכב באתר</p>
        </div>

        <a class="admin-new-btn" href="/vehicle/add.php" target="_blank">+ מודעה חדשה</a>
    </div>

    <div class="stats-row">
        <a class="stat-card<?= activeTab($status, 'all') ?>" href="/admin/vehicle_ads.php">
            <strong>הכל</strong>
            <span><?= (int)($counts['total'] ?? 0) ?></span>
        </a>

        <a class="stat-card pending<?= activeTab($status, 'pending') ?>" href="/admin/vehicle_ads.php?status=pending">
            <strong>ממתינות</strong>
            <span><?= (int)($counts['pending'] ?? 0) ?></span>
        </a>

        <a class="stat-card approved<?= activeTab($status, 'approved') ?>" href="/admin/vehicle_ads.php?status=approved">
            <strong>מאושרות</strong>
            <span><?= (int)($counts['approved'] ?? 0) ?></span>
        </a>

        <a class="stat-card rejected<?= activeTab($status, 'rejected') ?>" href="/admin/vehicle_ads.php?status=rejected">
            <strong>נדחו</strong>
            <span><?= (int)($counts['rejected'] ?? 0) ?></span>
        </a>
    </div>

    <form class="admin-search-bar" method="get" action="/admin/vehicle_ads.php">
        <?php if ($status !== 'all'): ?>
            <input type="hidden" name="status" value="<?= h($status) ?>">
        <?php endif; ?>

        <input type="text" name="q" value="<?= h($q) ?>" placeholder="חיפוש לפי כותרת, יצרן, דגם, שם משתמש, טלפון או אימייל">
        <button type="submit">חפש</button>

        <?php if ($q !== ''): ?>
            <a href="/admin/vehicle_ads.php<?= $status !== 'all' ? '?status=' . h($status) : '' ?>">נקה</a>
        <?php endif; ?>
    </form>

    <?php if (!$ads): ?>
        <div class="empty-box">אין מודעות להצגה.</div>
    <?php else: ?>

        <div class="admin-table-wrap">
            <table class="admin-vehicle-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>תמונה</th>
                        <th>מודעה</th>
                        <th>יצרן / דגם</th>
                        <th>מחיר</th>
                        <th>מפרסם</th>
                        <th>טלפון</th>
                        <th>תאריך</th>
                        <th>סטטוס</th>
                        <th>פעולות</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($ads as $ad): ?>
                        <tr>
                            <td>#<?= (int)$ad['id'] ?></td>

                            <td>
                                <?php if (!empty($ad['image_path'])): ?>
                                    <img class="vehicle-admin-thumb" src="<?= h($ad['image_path']) ?>" alt="">
                                <?php else: ?>
                                    <div class="no-thumb">אין תמונה</div>
                                <?php endif; ?>
                            </td>

                            <td class="ad-title">
                                <strong><?= h($ad['title']) ?></strong>
                                <small><?= h(trim(($ad['maker_name'] ?? '') . ' ' . ($ad['model_name'] ?? ''))) ?></small>
                            </td>

                            <td>
                                <?= h($ad['maker_name'] ?? '-') ?><br>
                                <small><?= h($ad['model_name'] ?? '') ?></small>
                            </td>

                            <td>
                                <?= !empty($ad['price']) ? number_format((float)$ad['price']) . ' ₪' : '-' ?>
                            </td>

                            <td>
                                <?= h($ad['user_name'] ?? '-') ?><br>
                                <small><?= h($ad['user_email'] ?? '') ?></small>
                            </td>

                            <td><?= h($ad['ad_phone'] ?: ($ad['user_phone'] ?? '-')) ?></td>

                            <td>
                                <?= !empty($ad['created_at']) ? h(date('d/m/Y H:i', strtotime($ad['created_at']))) : '-' ?>
                            </td>

                            <td><?= statusBadge($ad['status']) ?></td>

                            <td class="actions-cell">
                                <a class="btn-view" href="/admin/vehicle_ad_view.php?id=<?= (int)$ad['id'] ?>">צפה</a>
                                <a class="btn-edit" href="/vehicle/edit.php?id=<?= (int)$ad['id'] ?>" target="_blank">ערוך</a>

                                <?php if ($ad['status'] !== 'approved'): ?>
                                    <a class="btn-approve" href="/admin/vehicle_ads.php?action=approve&id=<?= (int)$ad['id'] ?>">אשר</a>
                                <?php endif; ?>

                                <?php if ($ad['status'] !== 'rejected'): ?>
                                    <a class="btn-reject" href="/admin/vehicle_ads.php?action=reject&id=<?= (int)$ad['id'] ?>">דחה</a>
                                <?php endif; ?>

                                <a class="btn-delete"
                                   href="/admin/vehicle_ads.php?action=delete&id=<?= (int)$ad['id'] ?>"
                                   onclick="return confirm('למחוק את המודעה?');">מחק</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>
        </div>

    <?php endif; ?>

</div>

<?php require_once '../includes/admin_footer.php'; ?>