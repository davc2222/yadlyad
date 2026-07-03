<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$main_categories = $pdo->query("
    SELECT id, name
    FROM categories
    WHERE parent_id IS NULL AND is_active = 1
    ORDER BY sort_order, name
")->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="search-page">

    <div class="search-box">
        <h1>חיפוש מודעות</h1>

        <form method="get" action="search.php" class="search-form">

            <div class="form-row">
                <label>מה מחפשים?</label>
                <input type="text" name="q" placeholder="לדוגמה: מאזדה, ספה, דירה...">
            </div>

            <div class="form-grid">
                <div>
                    <label>קטגוריה ראשית</label>
                    <select name="category_id" id="categorySelect">
                        <option value="">בחר קטגוריה</option>
                        <?php foreach ($main_categories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label>תת קטגוריה</label>
                    <select name="subcategory_id" id="subcategorySelect">
                        <option value="">בחר קודם קטגוריה</option>
                    </select>
                </div>

                <div>
                    <label>עיר / אזור</label>
                    <input type="text" name="city" placeholder="לדוגמה: תל אביב">
                </div>

                <div>
                    <label>מחיר מ־</label>
                    <input type="number" name="price_min">
                </div>

                <div>
                    <label>מחיר עד</label>
                    <input type="number" name="price_max">
                </div>
            </div>

            <button type="submit" class="btn-main search-btn">חפש</button>

        </form>
    </div>

    <div class="results-placeholder">
        <h2>תוצאות חיפוש</h2>
        <p>בשלב הבא נחבר את החיפוש לטבלאות המודעות.</p>
    </div>

</section>

<script>
    document.getElementById('categorySelect').addEventListener('change', function () {
        const categoryId = this.value;
        const subSelect = document.getElementById('subcategorySelect');

        subSelect.innerHTML = '<option>טוען...</option>';

        if (!categoryId) {
            subSelect.innerHTML = '<option value="">בחר קודם קטגוריה</option>';
            return;
        }

        fetch('/ajax/get_subcategories.php?category_id=' + categoryId)
            .then(res => res.json())
            .then(data => {
                subSelect.innerHTML = '<option value="">בחר תת קטגוריה</option>';

                data.forEach(item => {
                    subSelect.innerHTML += `
                    <option value="${item.id}">${item.name}</option>
                `;
                });
            });
    });
</script>

<?php require_once 'includes/footer.php'; ?>