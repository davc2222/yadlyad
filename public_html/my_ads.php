<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

if (empty($_SESSION['user_id'])) {
    header('Location: /login.php?redirect=/my_ads.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

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
        m.name AS maker_name,
        cm.name AS model_name,
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
    WHERE a.user_id = ?
      AND a.is_deleted = 0
    ORDER BY a.id DESC
");

$stmt->execute([$user_id]);
$ads = $stmt->fetchAll(PDO::FETCH_ASSOC);

function status_label($status)
{
    switch ($status) {
        case 'active':
            return ['פעילה', 'active'];
        case 'pending':
            return ['ממתינה לאישור', 'pending'];
        case 'inactive':
            return ['לא פעילה', 'inactive'];
        default:
            return ['לא ידוע', 'inactive'];
    }
}
?>

<link rel="stylesheet" href="/css/my_ads.css">

<section class="my-ads-page">

    <div class="my-ads-header">
        <div>
            <h1>המודעות שלי</h1>
            <p>כאן אפשר לנהל, לערוך ולצפות במודעות שפרסמת.</p>
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
                <?php [$statusText, $statusClass] = status_label($ad['status']); ?>

                <div class="my-ad-row">

                    <div class="my-ad-image">
                        <?php if (!empty($ad['image_path'])): ?>
                            <img src="<?= htmlspecialchars($ad['image_path']) ?>" alt="">
                        <?php else: ?>
                            <div class="my-ad-no-image">🚗</div>
                        <?php endif; ?>
                    </div>

                    <div class="my-ad-content">

                        <div class="my-ad-top">
                            <h2>
                                <?= htmlspecialchars(trim(($ad['maker_name'] ?? '') . ' ' . ($ad['model_name'] ?? ''))) ?>
                                <?php if (!empty($ad['year'])): ?>
                                    <span>
                                        <?= (int) $ad['year'] ?>
                                    </span>
                                <?php endif; ?>
                            </h2>

                            <span class="status-badge <?= htmlspecialchars($statusClass) ?>">
                                <?= htmlspecialchars($statusText) ?>
                            </span>
                        </div>

                        <?php if (!empty($ad['title'])): ?>
                            <div class="my-ad-title">
                                <?= htmlspecialchars($ad['title']) ?>
                            </div>
                        <?php endif; ?>

                        <div class="my-ad-meta">
                            <?php if (!empty($ad['price'])): ?>
                                <span>₪
                                    <?= number_format((int) $ad['price']) ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($ad['hand'])): ?>
                                <span>יד
                                    <?= (int) $ad['hand'] ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($ad['km'])): ?>
                                <span>
                                    <?= number_format((int) $ad['km']) ?> ק״מ
                                </span>
                            <?php endif; ?>

                            <span>
                                <?= number_format((int) $ad['views']) ?> צפיות
                            </span>
                        </div>

                        <div class="my-ad-date">
                            פורסם בתאריך:
                            <?= htmlspecialchars(date('d/m/Y', strtotime($ad['created_at']))) ?>
                        </div>

                        <div class="my-ad-actions">
                            <a href="/vehicle/view.php?id=<?= (int) $ad['id'] ?>">צפייה</a>
                            <a href="/vehicle/edit.php?id=<?= (int) $ad['id'] ?>">עריכה</a>
                            <a href="/vehicle/images.php?id=<?= (int) $ad['id'] ?>">תמונות</a>
                            <a href="/vehicle/delete.php?id=<?= (int) $ad['id'] ?>" class="danger"
                                onclick="return confirm('למחוק את המודעה?');">מחיקה</a>
                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<?php require_once 'includes/footer.php'; ?>
