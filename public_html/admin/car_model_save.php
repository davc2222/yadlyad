<?php
require_once '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$name = trim($_POST['name'] ?? '');
$sort_order = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;
$is_active = isset($_POST['is_active']) ? 1 : 0;

if ($name === '') {
    echo json_encode([
        'success' => false,
        'message' => 'יש להזין שם יצרן'
    ]);
    exit;
}

try {

    if ($id > 0) {

        $stmt = $pdo->prepare("
            UPDATE car_makers
            SET
                name=?,
                sort_order=?,
                is_active=?
            WHERE id=?
        ");

        $stmt->execute([
            $name,
            $sort_order,
            $is_active,
            $id
        ]);

    } else {

        $stmt = $pdo->prepare("
            INSERT INTO car_makers
            (
                name,
                sort_order,
                is_active
            )
            VALUES
            (
                ?,
                ?,
                ?
            )
        ");

        $stmt->execute([
            $name,
            $sort_order,
            $is_active
        ]);
    }

    echo json_encode([
        'success' => true
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

}