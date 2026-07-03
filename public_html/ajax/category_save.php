<?php

require_once '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$parent_id = ($_POST['parent_id'] === '') ? null : (int) $_POST['parent_id'];
$name = trim($_POST['name'] ?? '');
$sort_order = (int) ($_POST['sort_order'] ?? 0);
$is_active = isset($_POST['is_active']) ? 1 : 0;

if ($name == '') {
    echo json_encode([
        'success' => false,
        'message' => 'יש להזין שם קטגוריה'
    ]);
    exit;
}

try {

    if ($id == 0) {

        $stmt = $pdo->prepare("
            INSERT INTO categories
            (parent_id,name,sort_order,is_active)
            VALUES (?,?,?,?)
        ");

        $stmt->execute([
            $parent_id,
            $name,
            $sort_order,
            $is_active
        ]);

    } else {

        $stmt = $pdo->prepare("
            UPDATE categories
            SET
                parent_id=?,
                name=?,
                sort_order=?,
                is_active=?
            WHERE id=?
        ");

        $stmt->execute([
            $parent_id,
            $name,
            $sort_order,
            $is_active,
            $id
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