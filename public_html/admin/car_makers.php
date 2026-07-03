<?php
require_once '../includes/db.php';
require_once '../includes/admin_header.php';
require_once '../includes/admin_sidebar.php';

$stmt = $pdo->query("
    SELECT id, name, sort_order, is_active
    FROM car_makers
    ORDER BY sort_order, name
");

$makers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1 class="page-title">ניהול יצרני רכב</h1>

<div class="card">
    <button type="button" class="btn btn-green" onclick="openMakerModal()">
        ➕ הוסף יצרן
    </button>
</div>

<div class="card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>שם יצרן</th>
                <th>סדר</th>
                <th>סטטוס</th>
                <th>פעולות</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($makers)): ?>
                <tr>
                    <td colspan="5">אין יצרנים עדיין</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($makers as $maker): ?>
                <tr>
                    <td>
                        <?= (int) $maker['id'] ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($maker['name']) ?>
                    </td>
                    <td>
                        <?= (int) $maker['sort_order'] ?>
                    </td>
                    <td>
                        <?php if ($maker['is_active']): ?>
                            <span class="status-on">פעיל</span>
                        <?php else: ?>
                            <span class="status-off">כבוי</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button type="button" class="btn btn-small btn-orange" onclick="openMakerModal(
                                    <?= (int) $maker['id'] ?>,
                                    '<?= htmlspecialchars($maker['name'], ENT_QUOTES) ?>',
                                    <?= (int) $maker['sort_order'] ?>,
                                    <?= (int) $maker['is_active'] ?>
                                )">
                            ✏️ ערוך
                        </button>

                        <button type="button" class="btn btn-small btn-red" onclick="deleteMaker(<?= (int) $maker['id'] ?>)">
                            🗑️ מחק
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="makerModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h2 id="makerModalTitle">הוספת יצרן</h2>

        <form id="makerForm">
            <input type="hidden" name="id" id="makerId">

            <label>שם יצרן</label>
            <input type="text" name="name" id="makerName" required>

            <label>סדר תצוגה</label>
            <input type="number" name="sort_order" id="makerSortOrder" value="0">

            <label>
                <input type="checkbox" name="is_active" id="makerIsActive" value="1" checked>
                פעיל
            </label>

            <div class="modal-actions">
                <button type="submit" class="btn btn-green">שמור</button>
                <button type="button" class="btn btn-red" onclick="closeMakerModal()">ביטול</button>
            </div>
        </form>

        <div id="makerMsg"></div>
    </div>
</div>

<script src="js/car_makers.js"></script>

<?php require_once '../includes/admin_footer.php'; ?>