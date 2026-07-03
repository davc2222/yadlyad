<?php
require_once '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$maker_id = isset($_GET['maker_id']) ? (int) $_GET['maker_id'] : 0;

if ($maker_id <= 0) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, name
        FROM car_models
        WHERE maker_id = ?
          AND is_active = 1
        ORDER BY sort_order ASC, name ASC
    ");

    $stmt->execute([$maker_id]);
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($models, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode([]);
}