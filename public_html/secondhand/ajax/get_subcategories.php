<?php
require_once '../../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$category_id = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;

if ($category_id <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, name
    FROM secondhand_subcategories
    WHERE category_id = ?
      AND is_active = 1
    ORDER BY sort_order, name
");

$stmt->execute([$category_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);