<?php

require_once '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'יצרן לא נמצא'
    ]);
    exit;
}

try {

    /* בעתיד נוסיף בדיקה אם יש דגמים ליצרן */

    $stmt = $pdo->prepare("
        DELETE FROM car_makers
        WHERE id=?
    ");

    $stmt->execute([$id]);

    echo json_encode([
        'success' => true
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

}