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

function dealTypeText($dealType)
{
    if ($dealType === 'sale')
        return 'מכירה';
    if ($dealType === 'rent')
        return 'השכרה';
    if ($dealType === 'roommates')
        return 'שותפים';
    if ($dealType === 'commercial')
        return 'מסחרי';
    return '';
}

$q = trim($_GET['q'] ?? '');
$subcategoryId = (int) ($_GET['subcategory_id'] ?? 0);
$dealType = trim($_GET['deal_type'] ?? '');
$propertyType = trim($_GET['property_type'] ?? '');
$regionId = (int) ($_GET['region_id'] ?? 0);
$cityId = (int) ($_GET['city_id'] ?? 0);
$rooms = trim($_GET['rooms'] ?? '');
$maxPrice = trim($_GET['max_price'] ?? '');

$where = [
    "a.is_deleted = 0",
    "a.status = 'active'"
];

$params = [];

if ($subcategoryId > 0) {
    $where[] = "a.subcategory_id = ?";
    $params[] = $subcategoryId;
}

if (in_array($dealType, ['sale', 'rent', 'roommates', 'commercial'], true)) {
    $where[] = "a.deal_type = ?";
    $params[] = $dealType;
}

if ($propertyType !== '') {
    $where[] = "a.property_type = ?";
    $params[] = $propertyType;
}

if ($regionId > 0) {
    $where[] = "a.region_id = ?";
    $params[] = $regionId;
}

if ($cityId > 0) {
    $where[] = "a.city_id = ?";
    $params[] = $cityId;
}

if ($rooms !== '' && is_numeric($rooms) && (float) $rooms > 0) {
    $where[] = "a.rooms >= ?";
    $params[] = (float) $rooms;
}

if ($maxPrice !== '' && is_numeric($maxPrice) && (float) $maxPrice > 0) {
    $where[] = "a.price > 0 AND a.price <= ?";
    $params[] = (float) $maxPrice;
}

if ($q !== '') {
    $where[] = "(
        a.title LIKE ?
        OR a.description LIKE ?
        OR a.property_type LIKE ?
        OR subcat.name LIKE ?
        OR c.name LIKE ?
        OR r.name LIKE ?
        OR a.street LIKE ?
        OR a.neighborhood LIKE ?
    )";

    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like, $like, $like, $like);
}

$whereSql = implode("\n      AND ", $where);

$sql = "
    SELECT
        a.*,
        subcat.name AS subcategory_name,
        r.name AS region_name,
        c.name AS city_name,
        img.image_path,
        img.image_name
    FROM realestate_ads a
    LEFT JOIN categories subcat ON subcat.id = a.subcategory_id
    LEFT JOIN regions r ON r.id = a.region_id
    LEFT JOIN cities c ON c.id = a.city_id
    LEFT JOIN (
        SELECT ri1.ad_id, ri1.image_path, ri1.image_name
        FROM realestate_images ri1
        INNER JOIN (
            SELECT ad_id, MIN(id) AS min_id
            FROM realestate_images
            GROUP BY ad_id
        ) ri2 ON ri2.ad_id = ri1.ad_id AND ri2.min_id = ri1.id
    ) img ON img.ad_id = a.id
    WHERE {$whereSql}
    ORDER BY a.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ads = $stmt->fetchAll(PDO::FETCH_ASSOC);

$subcategories = [];
try {
    $stmt = $pdo->prepare("
        SELECT id, name
        FROM categories
        WHERE parent_id = 3
          AND is_active = 1
        ORDER BY sort_order, name
    ");
    $stmt->execute();
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $subcategories = [];
}

$regions = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM regions ORDER BY name");
    $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $regions = [];
}

$cities = [];
if ($regionId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT id, name FROM cities WHERE region_id = ? ORDER BY name");
        $stmt->execute([$regionId]);
        $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $cities = [];
    }
}

$propertyTypes = [
    'דירה',
    'דירת גן',
    'דופלקס',
    'פנטהאוז',
    'בית פרטי',
    'קוטג׳',
    'דו משפחתי',
    'יחידת דיור',
    'מגרש',
    'מחסן',
    'משרד',
    'חנות',
    'מבנה תעשייה',
    'נחלה',
    'משק חקלאי',
    'אחר'
];
?>

<link rel="stylesheet" href="/secondhand/css/secondhand.css?v=12">
<link rel="stylesheet" href="/realestate/css/realestate.css?v=2">
<section class="secondhand-hero">
    <div>
        <span class="secondhand-kicker">נדל״ן</span>
        <h1>מודעות נדל״ן</h1>
        <p>חפש נכסים לפי סוג עסקה, סוג נכס, אזור, עיר, חדרים ומחיר.</p>
    </div>
    <a class="secondhand-post-btn" href="/realestate/add.php">פרסם מודעה</a>
</section>

<section class="secondhand-toolbar">
    <form class="secondhand-search-form" id="realestateSearchForm" method="get" action="/realestate/index.php">
        <div class="secondhand-field">
            <label>קטגוריה</label>
            <select name="subcategory_id">
                <option value="0">כל הקטגוריות</option>
                <?php foreach ($subcategories as $sub): ?>
                    <option value="<?= (int) $sub['id'] ?>" <?= $subcategoryId === (int) $sub['id'] ? 'selected' : '' ?>>
                        <?= e($sub['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="secondhand-field">
            <label>סוג עסקה</label>
            <select name="deal_type">
                <option value="">כל העסקאות</option>
                <option value="sale" <?= $dealType === 'sale' ? 'selected' : '' ?>>מכירה</option>
                <option value="rent" <?= $dealType === 'rent' ? 'selected' : '' ?>>השכרה</option>
                <option value="roommates" <?= $dealType === 'roommates' ? 'selected' : '' ?>>שותפים</option>
                <option value="commercial" <?= $dealType === 'commercial' ? 'selected' : '' ?>>מסחרי</option>
            </select>
        </div>

        <div class="secondhand-field">
            <label>סוג נכס</label>
            <select name="property_type">
                <option value="">כל סוגי הנכסים</option>
                <?php foreach ($propertyTypes as $type): ?>
                    <option value="<?= e($type) ?>" <?= $propertyType === $type ? 'selected' : '' ?>>
                        <?= e($type) ?>
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
            <label>חדרים מינימום</label>
            <select name="rooms">
                <option value="">הכול</option>
                <?php foreach ([1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5, 6] as $roomValue): ?>
                    <option value="<?= e($roomValue) ?>" <?= (string) $rooms === (string) $roomValue ? 'selected' : '' ?>>
                        <?= e($roomValue) ?>+
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="realestate-search-row-2">

    <div class="secondhand-field secondhand-price-field">
        <label>עד מחיר</label>
        <input type="number"
               name="max_price"
               value="<?= e($maxPrice) ?>" min="0" placeholder="₪">
            </div>
        
            <div class="secondhand-field secondhand-free-field">
                <label>חיפוש חופשי</label>
                <input type="text" name="q" id="realestateFreeSearch" value="<?= e($q) ?>" placeholder="רחוב, שכונה או כותרת">
            </div>
        
            <div class="secondhand-actions">
                <button type="submit">חפש</button>
                <a href="/realestate/index.php">נקה</a>
            </div>
        
        </div>
    </form>
</section>

<section class="secondhand-results-head">
    <h2>תוצאות</h2>
    <span><span id="realestateResultsCount">
            <?= count($ads) ?>
        </span> מודעות נמצאו</span>
</section>

<div id="realestateSearchLoading" style="display:none;padding:12px 0;color:#64748b;font-weight:700;">מחפש...</div>

<div id="realestateResults">
    <?php if (!$ads): ?>
        <section class="secondhand-empty">
            <h2>לא נמצאו מודעות</h2>
            <p>נסה לשנות את החיפוש או לנקות את הסינונים.</p>
        </section>
    <?php else: ?>
        <section class="secondhand-grid">
            <?php foreach ($ads as $ad): ?>
                <?php
                $title = trim($ad['title'] ?? '') ?: 'מודעת נדל״ן';
                $img = !empty($ad['image_path']) ? $ad['image_path'] : (!empty($ad['image_name']) ? '/realestate/uploads/' . $ad['image_name'] : '');
                $locationParts = [];
                if (!empty($ad['city_name']))
                    $locationParts[] = $ad['city_name'];
                if (!empty($ad['region_name']))
                    $locationParts[] = $ad['region_name'];
                $location = implode(' · ', $locationParts);
                $description = trim(strip_tags($ad['description'] ?? '')) ?: 'לא נוסף תיאור למודעה';
                $hasPrice = !empty($ad['price']) && (float) $ad['price'] > 0;
                $isFlexible = !empty($ad['is_price_flexible']);
                $details = [];
                if (!empty($ad['property_type']))
                    $details[] = $ad['property_type'];
                if (!empty($ad['deal_type']) && dealTypeText($ad['deal_type']) !== '')
                    $details[] = dealTypeText($ad['deal_type']);
                if (hasVal($ad['rooms']))
                    $details[] = $ad['rooms'] . ' חדרים';
                if (!empty($ad['square_meters']))
                    $details[] = (int) $ad['square_meters'] . ' מ״ר';
                ?>

                <a class="shv-card" href="/realestate/view.php?id=<?= (int) $ad['id'] ?>">
                    <div class="shv-card-image">
                        <?php if ($img !== ''): ?>
                            <img src="<?= e($img) ?>" alt="<?= e($title) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="shv-no-image"><span class="realestate-home-placeholder">🏠</span></div>
                        <?php endif; ?>
                    </div>

                    <div class="shv-card-body">
                        <h3 class="shv-card-title">
                            <?= e($title) ?>
                        </h3>

                        <?php if ($location !== ''): ?>
                            <div class="shv-card-location"><span>📍</span><span>
                                    <?= e($location) ?>
                                </span></div>
                        <?php endif; ?>

                        <?php if ($details): ?>
                            <div class="realestate-card-details">
                                <?= e(implode(' · ', $details)) ?>
                            </div>
                        <?php endif; ?>

                        <div class="shv-card-description-title">תיאור המודעה</div>
                        <p class="shv-card-description">
                            <?= e(mb_strimwidth($description, 0, 82, '...')) ?>
                        </p>

                        <div class="shv-price-row">
                            <div class="shv-price<?= $hasPrice ? '' : ' shv-price-empty' ?>">
                                <?= $hasPrice ? number_format((float) $ad['price']) . ' ₪' : 'מחיר לא צוין' ?>
                            </div>
                            <?php if ($isFlexible): ?><span class="shv-flexible-price">מחיר גמיש</span>
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
    .realestate-card-details {
        min-height: 21px;
        margin: 0 0 11px;
        color: #536176;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.55
    }

    .realestate-home-placeholder {
        font-size: 48px;
        opacity: .65
    }

 /* בר החיפוש – 6 שדות בשורה הראשונה */
.secondhand-search-form{
    display:grid;
    grid-template-columns:repeat(6,1fr);
    gap:16px;
}
.secondhand-search-form {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 16px;
    align-items: end;
    direction: rtl;
}
.realestate-search-row-2 {
    grid-column: 1 / 4;
    display: grid;
    grid-template-columns: 1fr 1fr 1.05fr;
    gap: 16px;
    align-items: end;
}

.secondhand-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.secondhand-actions button,
.secondhand-actions a {
    width: 100%;
    height: 46px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
}
    @media (max-width:1200px){
    .secondhand-grid{
        grid-template-columns:repeat(3,1fr);
    }
}

@media (max-width:900px){
    .secondhand-grid{
        grid-template-columns:repeat(2,1fr);
    }
}

@media (max-width:600px){
    .secondhand-grid{
        grid-template-columns:1fr;
    }
}



</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('realestateSearchForm');
        const results = document.getElementById('realestateResults');
        const count = document.getElementById('realestateResultsCount');
        const loading = document.getElementById('realestateSearchLoading');
        const freeSearch = document.getElementById('realestateFreeSearch');
        const region = document.getElementById('region_id');
        const city = document.getElementById('city_id');
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
                    const response = await fetch('/ajax/search_realestate.php?' + params.toString(), {
                        headers: { 'Accept': 'application/json' }, cache: 'no-store', signal: controller.signal
                    });
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    const data = await response.json();
                    if (!data.success) throw new Error(data.error || 'Search failed');
                    results.innerHTML = data.html;
                    count.textContent = data.count;
                    history.replaceState(null, '', '/realestate/index.php' + (params.toString() ? '?' + params.toString() : ''));
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error('Realestate search error:', error);
                        results.innerHTML = '<section class="secondhand-empty"><h2>אירעה שגיאה בחיפוש</h2></section>';
                        count.textContent = '0';
                    }
                } finally {
                    loading.style.display = 'none';
                    results.style.opacity = '1';
                }
            }, delay);
        }

        form.addEventListener('submit', function (event) { event.preventDefault(); runSearch(0); });
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
    });
</script>

<?php require_once '../includes/footer.php'; ?>