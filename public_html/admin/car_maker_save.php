<?php
require_once '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$maker_id = isset($_POST['maker_id']) ? (int) $_POST['maker_id'] : 0;
$name = trim($_POST['name'] ?? '');
$sort_order = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;
$is_active = isset($_POST['is_active']) ? 1 : 0;

if ($maker_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'יש לבחור יצרן']);
    exit;
}

if ($name === '') {
    echo json_encode(['success' => false, 'message' => 'יש להזין שם דגם']);
    exit;
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("
            UPDATE car_models
            SET maker_id=?, name=?, sort_order=?, is_active=?
            WHERE id=?
        ");

        $stmt->execute([
            $maker_id,
            $name,
            $sort_order,
            $is_active,
            $id
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO car_models
            (maker_id, name, sort_order, is_active)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $maker_id,
            $name,
            $sort_order,
            $is_active
        ]);
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}