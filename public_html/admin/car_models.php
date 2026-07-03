<?php
require_once '../includes/db.php';
require_once '../includes/admin_header.php';
require_once '../includes/admin_sidebar.php';

$makers = $pdo->query("
    SELECT id, name
    FROM car_makers
    WHERE is_active = 1
    ORDER BY sort_order, name
")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT 
        m.id,
        m.maker_id,
        m.name,
        m.sort_order,
        m.is_active,
        cm.name AS maker_name
    FROM car_models m
    LEFT JOIN car_makers cm ON cm.id = m.maker_id
    ORDER BY cm.name, m.sort_order, m.name
");

$models = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1 class="page-title">ניהול דגמי רכב</h1>

<div class="card">
    <button type="button" class="btn btn-green" onclick="openModelModal()">
        ➕ הוסף דגם
    </button>
</div>

<div class="card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>יצרן</th>
                <th>דגם</th>
                <th>סדר</th>
                <th>סטטוס</th>
                <th>פעולות</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($models)): ?>
                <tr>
                    <td colspan="6">אין דגמים עדיין</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($models as $model): ?>
                <tr>
                    <td><?= (int) $model['id'] ?></td>
                    <td><?= htmlspecialchars($model['maker_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($model['name']) ?></td>
                    <td><?= (int) $model['sort_order'] ?></td>
                    <td>
                        <?= $model['is_active'] ? '<span class="status-on">פעיל</span>' : '<span class="status-off">כבוי</span>' ?>
                    </td>
                    <td>
                        <button type="button" class="btn btn-small btn-orange" onclick="openModelModal(
                                    <?= (int) $model['id'] ?>,
                                    <?= (int) $model['maker_id'] ?>,
                                    '<?= htmlspecialchars($model['name'], ENT_QUOTES) ?>',
                                    <?= (int) $model['sort_order'] ?>,
                                    <?= (int) $model['is_active'] ?>
                                )">
                            ✏️ ערוך
                        </button>

                        <button type="button" class="btn btn-small btn-red" onclick="deleteModel(<?= (int) $model['id'] ?>)">
                            🗑️ מחק
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="modelModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h2 id="modelModalTitle">הוספת דגם</h2>

        <form id="modelForm">
            <input type="hidden" name="id" id="modelId">

            <label>יצרן</label>
            <select name="maker_id" id="modelMakerId" required>
                <option value="">בחר יצרן</option>
                <?php foreach ($makers as $maker): ?>
                    <option value="<?= (int) $maker['id'] ?>">
                        <?= htmlspecialchars($maker['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>שם דגם</label>
            <input type="text" name="name" id="modelName" required>

            <label>סדר תצוגה</label>
            <input type="number" name="sort_order" id="modelSortOrder" value="0">

            <label>
                <input type="checkbox" name="is_active" id="modelIsActive" value="1" checked>
                פעיל
            </label>

            <div class="modal-actions">
                <button type="submit" class="btn btn-green">שמור</button>
                <button type="button" class="btn btn-red" onclick="closeModelModal()">ביטול</button>
            </div>
        </form>
    </div>
</div>

<script src="js/car_models.js"></script>

<?php require_once '../includes/admin_footer.php'; ?>