<?php
require_once '../includes/db.php';

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
    exit('Invalid ID');
}

try {

    $stmt = $pdo->prepare("
        DELETE FROM car_models
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    echo "OK";

} catch (PDOException $e) {
    echo $e->getMessage();
}