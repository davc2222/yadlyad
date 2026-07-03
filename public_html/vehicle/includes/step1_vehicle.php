<h2>פרטי הרכב</h2>

<div class="form-grid">

    <div>
        <label>קטגוריה</label>
        <select name="vehicle_category_id" required>
            <option value="">בחר קטגוריה</option>

            <?php foreach ($vehicle_categories as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label>יצרן</label>
        <select name="manufacturer_id" id="manufacturer_id" required>
            <option value="">בחר יצרן</option>

            <?php foreach ($makers as $maker): ?>
                <option value="<?= (int) $maker['id'] ?>">
                    <?= htmlspecialchars($maker['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label>דגם</label>
        <select name="model_id" id="model_id" required>
            <option value="">בחר דגם</option>
        </select>
    </div>

    <div>
        <label>שנת ייצור</label>
        <input type="number" name="year" min="1950" max="<?= date('Y') + 1 ?>" required>
    </div>

    <div>
        <label>חודש עליה לכביש</label>
        <select name="road_month">
            <option value="">בחר חודש</option>
            <?php for ($i = 1; $i <= 12; $i++): ?>
                <option value="<?= $i ?>"><?= $i ?></option>
            <?php endfor; ?>
        </select>
    </div>

    <div>
        <label>יד</label>
        <select name="hand">
            <option value="">בחר יד</option>
            <?php for ($i = 0; $i <= 10; $i++): ?>
                <option value="<?= $i ?>"><?= $i ?></option>
            <?php endfor; ?>
        </select>
    </div>

    <div>
        <label>קילומטר</label>
        <input type="number" name="km" min="0">
    </div>

    <div>
        <label>מחיר</label>
        <input type="number" name="price" min="0">
    </div>

    <div>
    <label>מחיר גמיש</label>
    <select name="is_price_flexible">
        <option value="">לא משנה</option>
        <option value="1">כן</option>
        <option value="0">לא</option>
    </select>
</div>
    <div>
        <label>תיבת הילוכים</label>
        <select name="gearbox_id">
            <option value="">בחר</option>
            <?php foreach ($gearboxes as $row): ?>
                <option value="<?= (int) $row['id'] ?>">
                    <?= htmlspecialchars($row['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label>סוג דלק</label>
        <select name="fuel_type_id">
            <option value="">בחר</option>
            <?php foreach ($fuel_types as $row): ?>
                <option value="<?= (int) $row['id'] ?>">
                    <?= htmlspecialchars($row['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label>נפח מנוע</label>
        <input type="number" name="engine_volume" min="0">
    </div>

    <div>
        <label>סוג מרכב</label>
        <select name="body_type_id">
            <option value="">בחר</option>
            <?php foreach ($body_types as $row): ?>
                <option value="<?= (int) $row['id'] ?>">
                    <?= htmlspecialchars($row['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label>הנעה</label>
        <select name="drive_type_id">
            <option value="">בחר</option>
            <?php foreach ($drive_types as $row): ?>
                <option value="<?= (int) $row['id'] ?>">
                    <?= htmlspecialchars($row['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label>דלתות</label>
        <input type="number" name="doors" min="0">
    </div>

    <div>
        <label>מקומות ישיבה</label>
        <input type="number" name="seats" min="0">
    </div>

    <div>
        <label>בעלות</label>
        <select name="ownership_type_id">
            <option value="">בחר</option>
            <?php foreach ($ownership_types as $row): ?>
                <option value="<?= (int) $row['id'] ?>">
                    <?= htmlspecialchars($row['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label>מצב הרכב</label>
        <select name="condition_id">
            <option value="">בחר</option>
            <?php foreach ($conditions as $row): ?>
                <option value="<?= (int) $row['id'] ?>">
                    <?= htmlspecialchars($row['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label>טסט עד</label>
        <input type="month" name="test_until">
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const manufacturerSelect = document.getElementById('manufacturer_id');
    const modelSelect = document.getElementById('model_id');

    manufacturerSelect.addEventListener('change', function () {
        const manufacturerId = this.value;

        modelSelect.innerHTML = '<option value="">טוען...</option>';

        fetch('/vehicle/ajax/get_models.php?manufacturer_id=' + manufacturerId)
            .then(response => response.json())
            .then(data => {
                modelSelect.innerHTML = '<option value="">בחר דגם</option>';

                data.forEach(item => {
                    modelSelect.innerHTML +=
                        '<option value="' + item.id + '">' + item.name + '</option>';
                });
            })
            .catch(error => {
                console.log(error);
                modelSelect.innerHTML = '<option value="">שגיאה בטעינת דגמים</option>';
            });
    });
});
</script>