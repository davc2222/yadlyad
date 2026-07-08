<?php
require_once '../includes/db.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/vehicle_ads.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$return_status = $_POST['return_status'] ?? 'pending';

$allowedStatuses = ['pending', 'approved', 'rejected'];

if ($id <= 0 || !in_array($status, $allowedStatuses, true)) {
    header('Location: /admin/vehicle_ads.php?status=' . urlencode($return_status));
    exit;
}

$stmt = $pdo->prepare("\n    UPDATE vehicle_ads\n    SET status = ?\n    WHERE id = ?\n      AND is_deleted = 0\n");
$stmt->execute([$status, $id]);

header('Location: /admin/vehicle_ads.php?status=' . urlencode($return_status));
exit;
