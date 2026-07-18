<?php
require_once '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$region_id = isset($_GET['region_id']) ? (int)$_GET['region_id'] : 0;

if ($region_id <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, name
    FROM cities
    WHERE region_id = ?
      AND is_active = 1
    ORDER BY sort_order ASC, name ASC
");

$stmt->execute([$region_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);