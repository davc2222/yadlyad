<section class="vehicle-form-page">

    <h1>פרסום מודעת רכב</h1>

    <form method="post" action="" class="vehicle-form">

        <div class="form-grid">

            <div>
                <label>יצרן</label>
                <select name="manufacturer_id" id="manufacturerSelect" required>
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
                <select name="model_id" id="modelSelect" required>
                    <option value="">בחר קודם יצרן</option>
                </select>
            </div>

            <div>
                <label>שנה</label>
                <select name="year" required>
                    <option value="">בחר שנה</option>
                    <?php for ($y = date('Y') + 1; $y >= 1980; $y--): ?>
                        <option value="<?= $y ?>"><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div>
                <label>יד</label>
                <select name="hand">
                    <option value="">בחר יד</option>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div>
                <label>ק״מ</label>
                <input type="number" name="km">
            </div>

            <div>
                <label>מחיר</label>
                <input type="number" name="price">
            </div>

            <div>
                <label>גיר</label>
                <select name="gearbox_id">
                    <option value="">בחר גיר</option>
                    <?php foreach ($gearboxes as $item): ?>
                        <option value="<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>סוג דלק</label>
                <select name="fuel_type_id">
                    <option value="">בחר סוג דלק</option>
                    <?php foreach ($fuel_types as $item): ?>
                        <option value="<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>צבע</label>
                <select name="color_id">
                    <option value="">בחר צבע</option>
                    <?php foreach ($colors as $item): ?>
                        <option value="<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>סוג מרכב</label>
                <select name="body_type_id">
                    <option value="">בחר סוג מרכב</option>
                    <?php foreach ($body_types as $item): ?>
                        <option value="<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>סוג הנעה</label>
                <select name="drive_type_id">
                    <option value="">בחר סוג הנעה</option>
                    <?php foreach ($drive_types as $item): ?>
                        <option value="<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>נפח מנוע</label>
                <input type="number" name="engine_volume">
            </div>

            <div>
                <label>כותרת</label>
                <input type="text" name="title" required>
            </div>

            <div>
                <label>טלפון</label>
                <input type="text" name="phone">
            </div>

        </div>

        <div>
            <label>תיאור</label>
            <textarea name="description" rows="5"></textarea>
        </div>

        <button type="submit" class="btn-main">שמור מודעה</button>

    </form>

</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const manufacturerSelect = document.getElementById('manufacturerSelect');
        const modelSelect = document.getElementById('modelSelect');

        manufacturerSelect.addEventListener('change', function () {
            const makerId = this.value;

            modelSelect.innerHTML = '<option value="">טוען דגמים...</option>';

            if (!makerId) {
                modelSelect.innerHTML = '<option value="">בחר קודם יצרן</option>';
                return;
            }

            fetch('/ajax/get_car_models.php?maker_id=' + encodeURIComponent(makerId))
                .then(response => response.json())
                .then(models => {
                    modelSelect.innerHTML = '<option value="">בחר דגם</option>';

                    if (models.length === 0) {
                        modelSelect.innerHTML = '<option value="">אין דגמים ליצרן זה</option>';
                        return;
                    }

                    models.forEach(model => {
                        const option = document.createElement('option');
                        option.value = model.id;
                        option.textContent = model.name;
                        modelSelect.appendChild(option);
                    });
                })
                .catch(() => {
                    modelSelect.innerHTML = '<option value="">שגיאה בטעינת דגמים</option>';
                });
        });
    });
</script>