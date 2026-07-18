<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: /login.php?redirect=/realestate/my_ads.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function statusInfo(string $status): array
{
    return match ($status) {
        'active' => ['מאושרת', 'active'],
        'pending' => ['ממתינה לאישור', 'pending'],
        'inactive' => ['לא פעילה', 'inactive'],
        'deleted' => ['נמחקה', 'deleted'],
        default => ['לא ידוע', 'inactive'],
    };
}

function dealTypeText(string $value): string
{
    return match ($value) {
        'sale' => 'מכירה',
        'rent' => 'השכרה',
        'roommates' => 'שותפים',
        'commercial' => 'מסחרי',
        default => '',
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $adId = (int) ($_POST['id'] ?? 0);
    if ($adId > 0) {
        $stmt = $pdo->prepare("UPDATE realestate_ads SET is_deleted = 1, status = 'deleted' WHERE id = ? AND user_id = ?");
        $stmt->execute([$adId, $userId]);
    }
    header('Location: /realestate/my_ads.php?deleted=1');
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        a.*,
        r.name AS region_name,
        c.name AS city_name,
        (
            SELECT COALESCE(NULLIF(image_path, ''), CONCAT('/realestate/uploads/', image_name))
            FROM realestate_images
            WHERE ad_id = a.id
            ORDER BY is_main DESC, sort_order ASC, id ASC
            LIMIT 1
        ) AS image_path
    FROM realestate_ads a
    LEFT JOIN regions r ON r.id = a.region_id
    LEFT JOIN cities c ON c.id = a.city_id
    WHERE a.user_id = ?
      AND a.is_deleted = 0
    ORDER BY a.id DESC
");
$stmt->execute([$userId]);
$ads = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<main class="re-my-page">
    <section class="re-my-head">
        <div><span>אזור אישי</span>
            <h1>מודעות הנדל״ן שלי</h1>
            <p>צפייה, עריכה וניהול של המודעות שפרסמת.</p>
        </div>
        <a href="/realestate/add.php">＋ פרסום מודעת נדל״ן</a>
    </section>

    <?php if (!empty($_GET['deleted'])): ?>
        <div class="re-my-success">המודעה נמחקה בהצלחה.</div>
    <?php endif; ?>

    <?php if (!$ads): ?>
        <section class="re-my-empty">
            <div>🏠</div>
            <h2>עדיין אין לך מודעות נדל״ן</h2>
            <p>אפשר לפרסם מודעה חדשה ולהתחיל לקבל פניות.</p><a href="/realestate/add.php">פרסם מודעה</a>
        </section>
    <?php else: ?>
        <section class="re-my-list">
            <?php foreach ($ads as $ad): ?>
                <?php
                [$statusText, $statusClass] = statusInfo((string) $ad['status']);
                $title = trim((string) ($ad['title'] ?? '')) ?: 'מודעת נדל״ן';
                $locationParts = array_filter([$ad['city_name'] ?? '', $ad['region_name'] ?? '']);
                $meta = [];
                if (!empty($ad['property_type']))
                    $meta[] = $ad['property_type'];
                if (!empty($ad['deal_type']) && dealTypeText((string) $ad['deal_type']) !== '')
                    $meta[] = dealTypeText((string) $ad['deal_type']);
                if ($ad['rooms'] !== null && $ad['rooms'] !== '')
                    $meta[] = $ad['rooms'] . ' חדרים';
                if (!empty($ad['square_meters']))
                    $meta[] = number_format((float) $ad['square_meters']) . ' מ״ר';
                ?>

                <article class="re-my-card">
                    <div class="re-my-image">
                        <?php if (!empty($ad['image_path'])): ?><img src="<?= e($ad['image_path']) ?>" alt="<?= e($title) ?>">
                        <?php else: ?>
                            <div>🏠</div>
                        <?php endif; ?>
                    </div>
                    <div class="re-my-content">
                        <div class="re-my-title-row">
                            <div>
                                <h2>
                                    <?= e($title) ?>
                                </h2>
                                <?php if ($locationParts): ?>
                                    <div class="re-my-location">📍
                                        <?= e(implode(' · ', $locationParts)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span class="re-status <?= e($statusClass) ?>">
                                <?= e($statusText) ?>
                            </span>
                        </div>
                        <?php if ($meta): ?>
                            <div class="re-my-meta">
                                <?= e(implode(' · ', $meta)) ?>
                            </div>
                        <?php endif; ?>
                        <div class="re-my-info">
                            <span>
                                <?= !empty($ad['price']) && (float) $ad['price'] > 0 ? number_format((float) $ad['price']) . ' ₪' : 'מחיר לא צוין' ?>
                            </span>
                            <span>
                                <?= (int) ($ad['views'] ?? 0) ?> צפיות
                            </span>
                            <span>פורסמה:
                                <?= !empty($ad['created_at']) ? e(date('d/m/Y', strtotime($ad['created_at']))) : '-' ?>
                            </span>
                            <span>מספר מודעה:
                                <?= (int) $ad['id'] ?>
                            </span>
                        </div>
                        <div class="re-my-actions">
                            <a href="/realestate/view.php?id=<?= (int) $ad['id'] ?>">צפייה</a>
                            <a href="/realestate/edit.php?id=<?= (int) $ad['id'] ?>">עריכה</a>
                            <form method="post" onsubmit="return confirm('למחוק את המודעה?');"><input type="hidden"
                                    name="action" value="delete"><input type="hidden" name="id"
                                    value="<?= (int) $ad['id'] ?>"><button type="submit">מחיקה</button></form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>

<style>
    .re-my-page {
        max-width: 1120px;
        margin: 30px auto;
        padding: 0 18px;
        direction: rtl
    }

    .re-my-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 20px
    }

    .re-my-head span {
        color: #1677e8;
        font-weight: 900
    }

    .re-my-head h1 {
        margin: 4px 0 5px;
        color: #0f172a;
        font-size: 31px
    }

    .re-my-head p {
        margin: 0;
        color: #64748b;
        font-weight: 700
    }

    .re-my-head>a {
        background: #1677e8;
        color: #fff;
        text-decoration: none;
        padding: 13px 18px;
        border-radius: 12px;
        font-weight: 950
    }

    .re-my-success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #86efac;
        border-radius: 13px;
        padding: 13px 16px;
        font-weight: 900;
        margin-bottom: 16px
    }

    .re-my-empty {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 50px 20px;
        text-align: center;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .05)
    }

    .re-my-empty>div {
        font-size: 58px
    }

    .re-my-empty h2 {
        margin: 10px 0 6px
    }

    .re-my-empty p {
        color: #64748b
    }

    .re-my-empty a {
        display: inline-block;
        margin-top: 10px;
        background: #1677e8;
        color: #fff;
        text-decoration: none;
        padding: 12px 19px;
        border-radius: 11px;
        font-weight: 900
    }

    .re-my-list {
        display: grid;
        gap: 14px
    }

    .re-my-card {
        display: grid;
        grid-template-columns: 230px minmax(0, 1fr);
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(15, 23, 42, .05)
    }

    .re-my-image {
        min-height: 195px;
        background: #eef2f6
    }

    .re-my-image img {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover
    }

    .re-my-image>div {
        height: 100%;
        min-height: 195px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 58px;
        color: #94a3b8
    }

    .re-my-content {
        padding: 18px
    }

    .re-my-title-row {
        display: flex;
        justify-content: space-between;
        gap: 15px
    }

    .re-my-title-row h2 {
        margin: 0;
        color: #0f172a;
        font-size: 22px
    }

    .re-my-location {
        margin-top: 6px;
        color: #64748b;
        font-weight: 800;
        font-size: 14px
    }

    .re-status {
        height: max-content;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 950;
        white-space: nowrap
    }

    .re-status.active {
        background: #dcfce7;
        color: #166534
    }

    .re-status.pending {
        background: #fff3cd;
        color: #856404
    }

    .re-status.inactive {
        background: #fee2e2;
        color: #991b1b
    }

    .re-status.deleted {
        background: #e5e7eb;
        color: #374151
    }

    .re-my-meta {
        margin-top: 14px;
        color: #475569;
        font-weight: 850
    }

    .re-my-info {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 14px
    }

    .re-my-info span {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 7px 10px;
        color: #475569;
        font-size: 13px;
        font-weight: 800
    }

    .re-my-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 17px;
        padding-top: 15px;
        border-top: 1px solid #e2e8f0
    }

    .re-my-actions a,
    .re-my-actions button {
        border: 0;
        border-radius: 10px;
        padding: 9px 15px;
        font-family: inherit;
        font-size: 13px;
        font-weight: 900;
        cursor: pointer;
        text-decoration: none
    }

    .re-my-actions a {
        background: #eaf2ff;
        color: #1859b7
    }

    .re-my-actions form {
        margin: 0
    }

    .re-my-actions button {
        background: #111827;
        color: #fff
    }

    @media(max-width:720px) {
        .re-my-head {
            display: block
        }

        .re-my-head>a {
            display: inline-block;
            margin-top: 14px
        }

        .re-my-card {
            grid-template-columns: 1fr
        }

        .re-my-image {
            height: 230px
        }

        .re-my-title-row {
            align-items: flex-start
        }
    }
</style>

<?php require_once '../includes/footer.php'; ?>