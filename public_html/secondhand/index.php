<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../includes/header.php';

function e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function hasVal($v)
{
    return $v !== null && $v !== '' && $v !== '0000-00-00';
}

function priceText($price)
{
    if (!hasVal($price) || (float) $price <= 0) {
        return 'מחיר לא צוין';
    }
    return number_format((float) $price) . ' ₪';
}

function conditionText($condition)
{
    if ($condition === 'new')
        return 'חדש';
    if ($condition === 'like_new')
        return 'כמו חדש';
    if ($condition === 'used')
        return 'משומש';
    if ($condition === 'broken')
        return 'לתיקון / לא תקין';
    return '';
}

$q = trim($_GET['q'] ?? '');
$categoryId = (int) ($_GET['category_id'] ?? 0);
$subcategoryId = (int) ($_GET['subcategory_id'] ?? 0);
$regionId = (int) ($_GET['region_id'] ?? 0);
$cityId = (int) ($_GET['city_id'] ?? 0);
$condition = trim($_GET['condition'] ?? '');
$maxPrice = trim($_GET['max_price'] ?? '');

$where = [
    "a.is_deleted = 0",
    "a.status IN ('approved', 'active')"
];

$params = [];

if ($categoryId > 0) {
    $where[] = "a.subcategory_id = ?";
    $params[] = $categoryId;
}

if ($regionId > 0) {
    $where[] = "a.region_id = ?";
    $params[] = $regionId;
}

if ($subcategoryId > 0) {
    $where[] = "a.subcategory_id = ?";
    $params[] = $subcategoryId;
}

if ($cityId > 0) {
    $where[] = "a.city_id = ?";
    $params[] = $cityId;
}

if (in_array($condition, ['new', 'like_new', 'used', 'broken'], true)) {
    $where[] = "a.item_condition = ?";
    $params[] = $condition;
}

if ($maxPrice !== '' && is_numeric($maxPrice) && (float) $maxPrice > 0) {
    $where[] = "a.price > 0 AND a.price <= ?";
    $params[] = (float) $maxPrice;
}

if ($q !== '') {
    $where[] = "(a.title LIKE ? OR a.description LIKE ? OR subcat.name LIKE ? OR c.name LIKE ? OR r.name LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like);
}

$whereSql = implode("\n      AND ", $where);

$sql = "
    SELECT
        a.*,
        cat.name AS category_name,
        subcat.name AS subcategory_name,
        r.name AS region_name,
        c.name AS city_name,
        img.image_path,
        img.image_name
    FROM secondhand_ads a
    LEFT JOIN categories cat ON cat.id = a.category_id
    LEFT JOIN categories subcat ON subcat.id = a.subcategory_id
    LEFT JOIN regions r ON r.id = a.region_id
    LEFT JOIN cities c ON c.id = a.city_id
    LEFT JOIN (
        SELECT si1.ad_id, si1.image_path, si1.image_name
        FROM secondhand_images si1
        INNER JOIN (
            SELECT ad_id, MIN(id) AS min_id
            FROM secondhand_images
            GROUP BY ad_id
        ) si2 ON si2.ad_id = si1.ad_id AND si2.min_id = si1.id
    ) img ON img.ad_id = a.id
    WHERE {$whereSql}
    ORDER BY a.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ads = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = [];
try {
    $catStmt = $pdo->prepare("
        SELECT id, name
        FROM categories
        WHERE parent_id = 1
          AND is_active = 1
        ORDER BY sort_order ASC, name ASC
    ");
    $catStmt->execute();
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $categories = [];
}


$subcategories = [];

if ($categoryId > 0) {

    $stmt = $pdo->prepare("
        SELECT id, name
        FROM categories
        WHERE parent_id = ?
          AND is_active = 1
        ORDER BY sort_order, name
    ");

    $stmt->execute([$categoryId]);

    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
}



$regions = [];
try {
    $regionStmt = $pdo->query("
        SELECT id, name
        FROM regions
        ORDER BY name ASC
    ");
    $regions = $regionStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $regions = [];
}

$cities = [];

if ($regionId > 0) {

    $cityStmt = $pdo->prepare("
        SELECT id, name
        FROM cities
        WHERE region_id = ?
          AND is_active = 1
        ORDER BY sort_order, name
    ");

    $cityStmt->execute([$regionId]);

    $cities = $cityStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<link rel="stylesheet" href="/secondhand/css/secondhand.css?v=11">

<main class="secondhand-page">

    <section class="secondhand-hero">
        <div>
            <span class="secondhand-kicker">יד שנייה</span>
            <h1>מודעות יד שנייה</h1>
            <p>חפש מוצרים לפי קטגוריה, אזור, עיר, מצב, מחיר וטקסט חופשי.</p>
        </div>

        <a class="secondhand-post-btn" href="/secondhand/add.php">פרסם מודעה</a>
    </section>

    <section class="secondhand-toolbar">
        <form class="secondhand-search-form" id="secondhandSearchForm" method="get" action="/secondhand/index.php">

            <div class="secondhand-field">
                <label>קטגוריה</label>
                <select name="category_id" id="category_id">
                    <option value="0">כל הקטגוריות</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>" <?= $categoryId === (int) $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="secondhand-field">
                <label>תת קטגוריה</label>

                <select name="subcategory_id" id="subcategory_id" <?= $categoryId <= 0 ? 'disabled' : '' ?>>

                    <option value="0">כל תתי הקטגוריות</option>

                    <?php foreach ($subcategories as $sub): ?>
                        <option value="<?= (int) $sub['id'] ?>" <?= $subcategoryId === (int) $sub['id'] ? 'selected' : '' ?>>
                            <?= e($sub['name']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div class="secondhand-field">
                <label>אזור</label>

                <select name="region_id" id="region_id">
                    <option value="0">כל האזורים</option>

                    <?php foreach ($regions as $region): ?>
                        <option value="<?= (int) $region['id'] ?>" <?= $regionId === (int) $region['id'] ? 'selected' : '' ?>>
                            <?= e($region['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="secondhand-field">
                <label>עיר</label>

                <select name="city_id" id="city_id" <?= $regionId <= 0 ? 'disabled' : '' ?>>
                    <option value="0">כל הערים</option>

                    <?php foreach ($cities as $city): ?>
                        <option value="<?= (int) $city['id'] ?>" <?= $cityId === (int) $city['id'] ? 'selected' : '' ?>>
                            <?= e($city['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="secondhand-field">
                <label>מצב</label>

                <select name="condition">
                    <option value="">כל המצבים</option>
                    <option value="new" <?= $condition === 'new' ? 'selected' : '' ?>>חדש</option>
                    <option value="like_new" <?= $condition === 'like_new' ? 'selected' : '' ?>>כמו חדש</option>
                    <option value="used" <?= $condition === 'used' ? 'selected' : '' ?>>משומש</option>
                    <option value="broken" <?= $condition === 'broken' ? 'selected' : '' ?>>לתיקון / לא תקין</option>
                </select>
            </div>

            <div class="secondhand-field secondhand-price-field">
                <label>עד מחיר</label>
                <input type="number" name="max_price" value="<?= e($maxPrice) ?>" min="0" placeholder="₪">
            </div>

            <div class="secondhand-field secondhand-free-field">
                <label>חיפוש חופשי</label>
                <input type="text" name="q" id="secondhandFreeSearch" value="<?= e($q) ?>" placeholder="חיפוש חופשי">
            </div>

            <div class="secondhand-actions">
                <button type="submit">חפש</button>
                <a href="/secondhand/index.php">נקה</a>
            </div>

        </form>
    </section>

    <section class="secondhand-results-head">
        <h2>תוצאות</h2>
        <span><span id="secondhandResultsCount"><?= count($ads) ?></span> מודעות נמצאו</span>
    </section>

    <div id="secondhandSearchLoading" style="display:none;padding:12px 0;color:#64748b;font-weight:700;">מחפש...</div>

    <div id="secondhandResults">
        <?php if (!$ads): ?>

            <section class="secondhand-empty">
                <h2>לא נמצאו מודעות</h2>
                <p>נסה לשנות את החיפוש או לנקות את הסינונים.</p>
            </section>

        <?php else: ?>

            <section class="secondhand-grid">
                <?php foreach ($ads as $ad): ?>
                    <?php
                    $title = trim($ad['title'] ?? '') ?: 'מודעת יד שנייה';

                    $img = '';
                    if (!empty($ad['image_path'])) {
                        $img = $ad['image_path'];
                    } elseif (!empty($ad['image_name'])) {
                        $img = '/uploads/secondhand/' . $ad['image_name'];
                    }

                    $conditionText = conditionText($ad['item_condition'] ?? '');

                    $locationParts = [];
                    if (!empty($ad['city_name'])) {
                        $locationParts[] = $ad['city_name'];
                    }
                    if (!empty($ad['region_name'])) {
                        $locationParts[] = $ad['region_name'];
                    }
                    $location = implode(' · ', $locationParts);
                    ?>

                    <?php
                    $description = trim(strip_tags($ad['description'] ?? ''));
                    if ($description === '') {
                        $description = 'לא נוסף תיאור למודעה';
                    }

                    $hasPrice = !empty($ad['price']) && (float) $ad['price'] > 0;
                    $isFlexible = !empty($ad['is_price_flexible']);
                    ?>

                    <a class="shv-card" href="/secondhand/view.php?id=<?= (int) $ad['id'] ?>">

                        <div class="shv-card-image">
                            <?php if (!empty($img)): ?>
                                <img src="<?= e($img) ?>" alt="<?= e($title) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="shv-no-image">

                                    <svg class="shv-no-image-icon" viewBox="0 0 24 24" fill="none">

                                        <rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.7" />

                                        <circle cx="8" cy="9" r="1.5" fill="currentColor" />

                                        <path d="M6 17L11 12L14 15L18 10L20 17" stroke="currentColor" stroke-width="1.7"
                                            stroke-linecap="round" stroke-linejoin="round" />

                                    </svg>

                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="shv-card-body">

                            <h3 class="shv-card-title"><?= e($title) ?></h3>

                            <?php if ($location !== ''): ?>
                                <div class="shv-card-location">
                                    <span>📍</span>
                                    <span><?= e($location) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="shv-card-description-title">תיאור המודעה</div>

                            <p class="shv-card-description">
                                <?= e(mb_strimwidth($description, 0, 82, '...')) ?>
                            </p>

                            <div class="shv-price-row">
                                <div class="shv-price<?= $hasPrice ? '' : ' shv-price-empty' ?>">
                                    <?php if ($hasPrice): ?>
                                        <?= number_format((float) $ad['price']) ?> ₪
                                    <?php else: ?>
                                        מחיר לא צוין
                                    <?php endif; ?>
                                </div>

                                <?php if ($isFlexible): ?>
                                    <span class="shv-flexible-price">מחיר גמיש</span>
                                <?php endif; ?>
                            </div>

                        </div>

                        <div class="shv-card-footer">
                            <span class="shv-views">
                                <?= (int) ($ad['views'] ?? 0) ?> צפיות 👁
                            </span>

                            <span class="shv-details">לפרטים</span>
                        </div>

                    </a>

                <?php endforeach; ?>
            </section>

        <?php endif; ?>

    </div>

</main>

<style>
    .secondhand-toolbar {
        background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
        border: 1px solid #dbe7f7;
        box-shadow: 0 10px 28px rgba(34, 76, 130, 0.08);
    }

    .secondhand-field label {
        color: #405b7f;
        font-weight: 700;
    }

    .secondhand-field select,
    .secondhand-field input {
        background: #ffffff;
        border: 1px solid #cbd9eb;
        color: #26364d;
    }

    .secondhand-field select:hover,
    .secondhand-field input:hover {
        border-color: #8fb5eb;
    }

    .secondhand-field select:focus,
    .secondhand-field input:focus {
        border-color: #2f7ae5;
        box-shadow: 0 0 0 3px rgba(47, 122, 229, 0.12);
        outline: none;
    }

    .secondhand-actions button {
        background: linear-gradient(135deg, #1f73e8, #125fc9);
        border: 1px solid #125fc9;
        color: #fff;
        box-shadow: 0 5px 12px rgba(31, 115, 232, 0.22);
    }

    .secondhand-actions button:hover {
        background: linear-gradient(135deg, #1768d8, #0e54b6);
    }

    .secondhand-actions a {
        background: #eef4fb;
        border: 1px solid #cbd9eb;
        color: #506782;
    }

    .secondhand-actions a:hover {
        background: #e2edf9;
        border-color: #aac2df;
    }

    .secondhand-field:nth-child(1) select,
    .secondhand-field:nth-child(2) select {
        background-color: #f7fbff;
    }

    .secondhand-field:nth-child(3) select,
    .secondhand-field:nth-child(4) select {
        background-color: #f8fcfa;
    }

    .secondhand-field:nth-child(5) select {
        background-color: #fffaf3;
    }

    .secondhand-price-field input {
        background-color: #f8f7ff;
    }

    .secondhand-free-field input {
        background-color: #f7faff;
    }


    .secondhand-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, 262px) !important;
        justify-content: start !important;
        gap: 22px !important;
        width: 100% !important;
    }

    .shv-card {
        width: 262px !important;
        min-width: 262px !important;
        max-width: 262px !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
        background: #ffffff !important;
        border: 1px solid #dbe8ff;
        border-radius: 17px !important;
        box-shadow: 0 9px 25px rgba(34, 52, 84, 0.10) !important;
        color: inherit !important;
        text-decoration: none !important;
        direction: rtl !important;
        transition: transform .2s ease, box-shadow .2s ease !important;
    }

    .shv-card:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 14px 32px rgba(34, 52, 84, 0.15) !important;
        border-color: #4b8df8 !important;
        ;
        box-shadow: 0 16px 35px rgba(30, 90, 200, .18) !important;
        ;
        ;
    }


    .shv-card-image {
        width: 100% !important;
        height: 155px !important;
        overflow: hidden !important;
        background: #edf1f5 !important;
    }

    .shv-card-image img {
        width: 100%;
        height: 155px;
        object-fit: cover;
        display: block;
    }

    .shv-no-image {
        width: 100% !important;
        height: 155px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        color: #7b8493 !important;
        background: #eef1f5 !important;
        font-size: 14px !important;
    }

    .shv-no-image {
        width: 100%;
        height: 155px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: #f4f6f9;
        color: #8b97a8;
    }

    .shv-no-image-icon {
        font-size: 42px;
        opacity: .55;
    }

    .shv-no-image span {
        font-size: 13px;
        font-weight: 600;
    }

    .shv-card-body {
        flex: 1 1 auto !important;
        padding: 15px 18px 16px !important;
        text-align: right !important;
    }

    .shv-card-title {
        margin: 0 0 8px !important;
        overflow: hidden !important;
        color: #3b6fc8 !important;
        font-size: 20px !important;
        line-height: 1.25 !important;
        font-weight: 600 !important;
        white-space: nowrap !important;
        text-overflow: ellipsis !important;
    }

    .shv-card-location {
        display: flex !important;
        align-items: center !important;
        gap: 4px !important;
        margin-bottom: 13px !important;
        color: #536176 !important;
        font-size: 13px !important;
    }

    .shv-card-description-title {
        padding-top: 12px !important;
        margin-bottom: 5px !important;
        border-top: 1px solid #e5e9ef !important;
        color: #0886ee79 !important;
        font-size: 13px !important;
        font-weight: 600 !important;
    }

    .shv-card-description {
        height: 42px !important;
        margin: 0 !important;
        overflow: hidden !important;
        color: #716e8b !important;
        font-size: 13px !important;
        line-height: 1.55 !important;
    }

    .shv-price-row {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        margin-top: 13px !important;
    }

    .shv-price {
        color: #175cf6 !important;
        font-size: 25px !important;
        line-height: 1 !important;
        font-weight: 900 !important;
        white-space: nowrap !important;
    }

    .shv-price-empty {
        color: #526078 !important;
        font-size: 15px !important;
        font-weight: 700 !important;
    }

    .shv-flexible-price {
        padding: 5px 8px !important;
        color: #0d8d48 !important;
        background: #e8fff1 !important;
        border-radius: 999px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        white-space: nowrap !important;
        border: 1px solid #b8efce !important;
    }

    .shv-card-footer {
        height: 50px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding: 0 17px !important;
        border-top: 1px solid #e5e9ef !important;
        background: #fff !important;
        color: #1565ff !important;
        border-top: 1px solid #dbe8ff !important;
        ;

    }

    .shv-details {
        color: #1565ff;
        font-weight: 700;
    }

    .shv-details:hover {
        color: #0b4bcc;
    }


    .shv-card {
        position: relative;
    }

    .shv-card::before {
        content: "";
        position: absolute;
        top: 0;
        right: 0;
        left: 0;
        height: 5px;
        background: linear-gradient(90deg, #1f6fff, #4aa3ff);
    }

    .shv-views,
    .shv-details {
        color: #647084 !important;
        font-size: 13px !important;
    }

    .shv-details {
        font-weight: 600 !important;
    }

    @media (max-width: 620px) {
        .secondhand-grid {
            grid-template-columns: 1fr !important;
            justify-items: center !important;
        }

        .shv-card {
            width: min(100%, 340px) !important;
            min-width: 0 !important;
            max-width: 340px !important;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('secondhandSearchForm');
        const results = document.getElementById('secondhandResults');
        const count = document.getElementById('secondhandResultsCount');
        const loading = document.getElementById('secondhandSearchLoading');
        const freeSearch = document.getElementById('secondhandFreeSearch');
        const region = document.getElementById('region_id');
        const city = document.getElementById('city_id');
        const category = document.getElementById('category_id');
        const subcategory = document.getElementById('subcategory_id');
        let timer = null;
        let controller = null;

        function runSearch(delay = 350) {
            clearTimeout(timer);
            timer = setTimeout(async function () {
                if (controller) controller.abort();
                controller = new AbortController();
                const params = new URLSearchParams(new FormData(form));
                loading.style.display = 'block';
                results.style.opacity = '.55';
                try {
                    const response = await fetch('/ajax/search_secondhand.php?' + params.toString(), {
                        headers: { 'Accept': 'application/json' }, cache: 'no-store', signal: controller.signal
                    });
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    const data = await response.json();
                    if (!data.success) throw new Error(data.error || 'Search failed');
                    results.innerHTML = data.html;
                    count.textContent = data.count;
                    history.replaceState(null, '', '/secondhand/index.php' + (params.toString() ? '?' + params.toString() : ''));
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error('Secondhand search error:', error);
                        results.innerHTML = '<section class="secondhand-empty"><h2>אירעה שגיאה בחיפוש</h2></section>';
                        count.textContent = '0';
                    }
                } finally {
                    loading.style.display = 'none';
                    results.style.opacity = '1';
                }
            }, delay);
        }

        form.addEventListener('submit', function (e) { e.preventDefault(); runSearch(0); });
        freeSearch.addEventListener('input', function () { runSearch(350); });
        form.querySelectorAll('select, input[type="number"]').forEach(function (field) {
            field.addEventListener('change', function () { runSearch(0); });
        });

        region.addEventListener('change', async function () {
            city.innerHTML = '<option value="0">טוען ערים...</option>';
            city.disabled = true;
            if (!this.value || this.value === '0') {
                city.innerHTML = '<option value="0">כל הערים</option>';
                runSearch(0);
                return;
            }
            try {
                const response = await fetch('/ajax/get_cities.php?region_id=' + encodeURIComponent(this.value), { cache: 'no-store' });
                const cities = await response.json();
                city.innerHTML = '<option value="0">כל הערים</option>';
                cities.forEach(function (item) {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.name;
                    city.appendChild(option);
                });
                city.disabled = false;
                runSearch(0);
            } catch (error) {
                console.error(error);
                city.innerHTML = '<option value="0">שגיאה בטעינת ערים</option>';
            }
        });

        category.addEventListener('change', async function () {
            subcategory.innerHTML = '<option value="0">טוען...</option>';
            subcategory.disabled = true;
            if (!this.value || this.value === '0') {
                subcategory.innerHTML = '<option value="0">כל תתי הקטגוריות</option>';
                runSearch(0);
                return;
            }
            try {
                const response = await fetch('/ajax/get_subcategories.php?category_id=' + encodeURIComponent(this.value), { cache: 'no-store' });
                const data = await response.json();
                subcategory.innerHTML = '<option value="0">כל תתי הקטגוריות</option>';
                data.forEach(function (item) {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.name;
                    subcategory.appendChild(option);
                });
                subcategory.disabled = false;
                runSearch(0);
            } catch (error) {
                console.error(error);
                subcategory.innerHTML = '<option value="0">שגיאה בטעינת תתי קטגוריות</option>';
            }
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>