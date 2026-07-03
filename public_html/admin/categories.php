<?php
require_once '../includes/db.php';
require_once '../includes/admin_header.php';
require_once '../includes/admin_sidebar.php';

$stmt = $pdo->query("
    SELECT id, parent_id, name, sort_order, is_active
    FROM categories
    ORDER BY 
        COALESCE(parent_id, id),
        parent_id IS NOT NULL,
        sort_order,
        name
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$main_categories = [];
$children = [];

foreach ($rows as $row) {
    if ($row['parent_id'] === null) {
        $main_categories[] = $row;
    } else {
        $children[$row['parent_id']][] = $row;
    }
}
?>

<h1 class="page-title">ניהול קטגוריות</h1>

<div class="card">
    <a href="javascript:void(0)" onclick="openCategoryModal()" class="btn btn-green">➕ הוסף קטגוריה ראשית</a>
</div>

<?php foreach ($main_categories as $cat): ?>

    <div class="category-box">

        <div class="category-main">

            <div>
                <span class="category-name">📁 <?= htmlspecialchars($cat['name']) ?></span>
                <span class="muted">ID: <?= (int) $cat['id'] ?></span>
            </div>

            <div class="actions">
                <a href="javascript:void(0)" onclick="openCategoryModal(<?= $cat['id'] ?>)"
                    class="btn btn-small btn-green">➕ תת קטגוריה</a>
                <a href="javascript:void(0)" onclick="openCategoryModal(
       <?= (int) $cat['id'] ?>,
                   <?= (int) $sub['id'] ?>,
                   '<?= htmlspecialchars($sub['name'], ENT_QUOTES) ?>',
                   <?= (int) $sub['sort_order'] ?>,
                   <?= (int) $sub['is_active'] ?>
               )" class="btn btn-small btn-orange">✏️ ערוך</a>
            <?php // מחיקת קטגוריה ראשית - יתווסף בהמשך ?>
            </div>

        </div>

        <div class="category-children">

            <?php if (!empty($children[$cat['id']])): ?>

                <?php foreach ($children[$cat['id']] as $sub): ?>

                    <div class="category-child">

                        <div>
                            └── <?= htmlspecialchars($sub['name']) ?>
                            <span class="muted">ID: <?= (int) $sub['id'] ?></span>

                            <?php if ($sub['is_active']): ?>
                                <span class="status-on">פעיל</span>
                            <?php else: ?>
                                <span class="status-off">כבוי</span>
                            <?php endif; ?>
                        </div>

                        <div class="actions">
                            <a href="javascript:void(0)" onclick="openCategoryModal(
       '',
       <?= (int) $cat['id'] ?>,
                                   '<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>',
                                   <?= (int) $cat['sort_order'] ?>,
                                   <?= (int) $cat['is_active'] ?>
                               )" class="btn btn-small btn-orange">✏️ ערוך</a>
                            <a href="#" class="btn btn-small btn-red">🗑️ מחק</a>
                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-row">אין תתי קטגוריות</div>

            <?php endif; ?>

        </div>

    </div>

<?php endforeach; ?>


<?php require_once 'category_modal.php'; ?>
<script src="js/categories.js"></script>
<?php require_once '../includes/admin_footer.php'; ?>
?>