<?php
require_once 'includes/db.php';
require_once 'includes/header.php';






if (empty($_SESSION['user_id'])) {
    header('Location: /login.php?redirect=/my_ads.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT id, title, price, year, km, status, created_at
    FROM vehicle_ads
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>המודעות שלי</h1>

<?php if (!$ads): ?>
    <p>אין לך מודעות עדיין.</p>
<?php else: ?>
    <?php foreach ($ads as $ad): ?>
        <div style="border:1px solid #ddd;padding:15px;margin-bottom:12px;">
            <strong>
                <?= htmlspecialchars($ad['title']) ?>
            </strong><br>
            שנה:
            <?= htmlspecialchars($ad['year']) ?> |
            ק״מ:
            <?= htmlspecialchars($ad['km']) ?> |
            מחיר:
            <?= htmlspecialchars($ad['price']) ?> ₪<br>
            סטטוס:
            <?= htmlspecialchars($ad['status']) ?><br><br>

            <a href="/vehicle/view.php?id=<?= (int) $ad['id'] ?>">צפייה</a> |
            <a href="/vehicle/edit.php?id=<?= (int) $ad['id'] ?>">עריכה</a>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>