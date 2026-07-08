<?php
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$ad_id = (int)($_GET['id'] ?? 0);

if ($ad_id > 0) {
    $stmt = $pdo->prepare("
        UPDATE vehicle_ads
        SET is_deleted = 1
        WHERE id = ?
          AND user_id = ?
    ");
    $stmt->execute([$ad_id, $user_id]);
}


header('Location: /my_ads.php');

exit;