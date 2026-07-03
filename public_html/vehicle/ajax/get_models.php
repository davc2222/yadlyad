<?php
require_once '../../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$manufacturer_id = (int) ($_GET['manufacturer_id'] ?? 0);

if ($manufacturer_id <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, name
    FROM car_models
    WHERE maker_id = ?
      AND is_active = 1
    ORDER BY sort_order, name
");

$stmt->execute([$manufacturer_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);