<?php
session_start();

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

require_once '../includes/db.php';

$stmt = $pdo->query("
    SELECT id, parent_id, name, sort_order, is_active
    FROM categories
    ORDER BY parent_id IS NOT NULL,
             COALESCE(parent_id,id),
             sort_order,
             name
");

$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>ניהול קטגוריות</title>

    <style>
        body {
            font-family: Arial;
            direction: rtl;
            margin: 30px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
        }

        th {
            background: #eee;
        }

        .child {
            padding-right: 35px;
            color: #444;
        }
    </style>
</head>

<body>

    <h2>ניהול קטגוריות</h2>

    <table>

        <tr>
            <th>ID</th>
            <th>שם</th>
            <th>סוג</th>
            <th>פעיל</th>
        </tr>

        <?php foreach ($categories as $row): ?>

            <tr>

                <td>
                    <?= $row['id'] ?>
                </td>

                <td class="<?= $row['parent_id'] ? 'child' : '' ?>">
                    <?= htmlspecialchars($row['name']) ?>
                </td>

                <td>
                    <?= $row['parent_id'] ? 'תת קטגוריה' : 'קטגוריה ראשית' ?>
                </td>

                <td>
                    <?= $row['is_active'] ? '✔' : '✖' ?>
                </td>

            </tr>

        <?php endforeach; ?>

    </table>

    <p><a href="index.php">חזרה לפאנל</a></p>

</body>

</html>