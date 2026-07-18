<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$makers = $pdo->query("
    SELECT id, name
    FROM car_makers
    WHERE is_active = 1
    ORDER BY sort_order, name
")->fetchAll(PDO::FETCH_ASSOC);

$regions = $pdo->query("
    SELECT id, name
    FROM regions
    WHERE is_active = 1
    ORDER BY sort_order, name
")->fetchAll(PDO::FETCH_ASSOC);

$q = trim($_GET['q'] ?? '');
$manufacturer_id = (int) ($_GET['manufacturer_id'] ?? 0);
$model_id = (int) ($_GET['model_id'] ?? 0);
$region_id = (int) ($_GET['region_id'] ?? 0);
$city_id = (int) ($_GET['city_id'] ?? 0);
$year_from = (int) ($_GET['year_from'] ?? 0);
$year_to = (int) ($_GET['year_to'] ?? 0);
$price_to = (int) ($_GET['price_to'] ?? 0);
$km_to = (int) ($_GET['km_to'] ?? 0);
$hand_to = (int) ($_GET['hand_to'] ?? 0);

$models = [];
if ($manufacturer_id > 0) {
    $stmtModels = $pdo->prepare("
        SELECT id, name
        FROM car_models
        WHERE maker_id = ?
          AND is_active = 1
        ORDER BY sort_order, name
    ");
    $stmtModels->execute([$manufacturer_id]);
    $models = $stmtModels->fetchAll(PDO::FETCH_ASSOC);
}

$cities = [];
if ($region_id > 0) {
    $stmtCities = $pdo->prepare("
        SELECT id, name
        FROM cities
        WHERE region_id = ?
          AND is_active = 1
        ORDER BY sort_order, name
    ");
    $stmtCities->execute([$region_id]);
    $cities = $stmtCities->fetchAll(PDO::FETCH_ASSOC);
}

$where = ["a.status = 'active'", "a.is_deleted = 0"];
$params = [];

if ($q !== '') {
    $where[] = "(m.name LIKE ? OR cm.name LIKE ? OR a.title LIKE ? OR a.description LIKE ? OR r.name LIKE ? OR c.name LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like, $like);
}

if ($manufacturer_id > 0) {
    $where[] = "a.manufacturer_id = ?";
    $params[] = $manufacturer_id;
}

if ($model_id > 0) {
    $where[] = "a.model_id = ?";
    $params[] = $model_id;
}

if ($region_id > 0) {
    $where[] = "a.region_id = ?";
    $params[] = $region_id;
}

if ($city_id > 0) {
    $where[] = "a.city_id = ?";
    $params[] = $city_id;
}

if ($year_from > 0) {
    $where[] = "a.year >= ?";
    $params[] = $year_from;
}

if ($year_to > 0) {
    $where[] = "a.year <= ?";
    $params[] = $year_to;
}

if ($price_to > 0) {
    $where[] = "a.price <= ?";
    $params[] = $price_to;
}

if ($km_to > 0) {
    $where[] = "a.km <= ?";
    $params[] = $km_to;
}

if ($hand_to > 0) {
    $where[] = "a.hand <= ?";
    $params[] = $hand_to;
}

$sql = "
    SELECT
        a.id,
        a.price,
        a.year,
        a.km,
        a.hand,
        a.views,
        a.is_price_flexible,
        m.name AS maker_name,
        cm.name AS model_name,
        g.name AS gearbox_name,
        f.name AS fuel_name,
        r.name AS region_name,
        c.name AS city_name,
        (
            SELECT image_path
            FROM vehicle_images
            WHERE ad_id = a.id
            ORDER BY is_main DESC, sort_order ASC, id ASC
            LIMIT 1
        ) AS image_path
    FROM vehicle_ads a
    LEFT JOIN car_makers m ON m.id = a.manufacturer_id
    LEFT JOIN car_models cm ON cm.id = a.model_id
    LEFT JOIN gearboxes g ON g.id = a.gearbox_id
    LEFT JOIN fuel_types f ON f.id = a.fuel_type_id
    LEFT JOIN regions r ON r.id = a.region_id
    LEFT JOIN cities c ON c.id = a.city_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY a.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="/vehicle/css/vehicle_list.css?v=6">
<section class="vehicle-list-page">

    <div class="vehicle-list-header">
        <div>
            <h1>רכבים למכירה</h1>
            <p>נמצאו <span id="vehicleResultsCount"><?= count($ads) ?></span> מודעות</p>
        </div>
        <a href="/vehicle/add.php" class="add-vehicle-btn">פרסם רכב</a>
    </div>

    <form method="get" action="/vehicle/index.php" class="vehicle-search-bar" id="vehicleSearchForm" autocomplete="off">

        <div class="search-field">
            <label>אזור</label>
            <select name="region_id" id="region_id">
                <option value="0">כל האזורים</option>
                <?php foreach ($regions as $region): ?>
                    <option value="<?= (int) $region['id'] ?>" <?= $region_id === (int) $region['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($region['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="search-field">
            <label>עיר</label>
            <select name="city_id" id="city_id" <?= $region_id <= 0 ? 'disabled' : '' ?>>
                <option value="0">כל הערים</option>
                <?php foreach ($cities as $city): ?>
                    <option value="<?= (int) $city['id'] ?>" <?= $city_id === (int) $city['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($city['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="search-field">
            <label>יצרן</label>
            <select name="manufacturer_id" id="manufacturer_id">
                <option value="0">כל היצרנים</option>
                <?php foreach ($makers as $maker): ?>
                    <option value="<?= (int) $maker['id'] ?>" <?= $manufacturer_id === (int) $maker['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($maker['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="search-field">
            <label>דגם</label>
            <select name="model_id" id="model_id" <?= $manufacturer_id <= 0 ? 'disabled' : '' ?>>
                <option value="0">כל הדגמים</option>
                <?php foreach ($models as $model): ?>
                    <option value="<?= (int) $model['id'] ?>" <?= $model_id === (int) $model['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($model['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="search-field search-field-small">
            <label>משנה</label>
            <input type="number" name="year_from" value="<?= $year_from ?: '' ?>" placeholder="2015">
        </div>

        <div class="search-field search-field-small">
            <label>עד שנה</label>
            <input type="number" name="year_to" value="<?= $year_to ?: '' ?>" placeholder="2026">
        </div>

        <div class="search-field search-field-small">
            <label>מחיר עד</label>
            <input type="number" name="price_to" value="<?= $price_to ?: '' ?>" placeholder="80000">
        </div>

        <div class="search-field search-field-small">
            <label>ק״מ עד</label>
            <input type="number" name="km_to" value="<?= $km_to ?: '' ?>" placeholder="120000">
        </div>

        <div class="search-field search-field-small">
            <label>יד עד</label>
            <input type="number" name="hand_to" value="<?= $hand_to ?: '' ?>" placeholder="2">
        </div>

        <div class="search-actions">
            <button type="submit" class="vehicle-search-btn">חפש</button>
            <a href="/vehicle/index.php" class="vehicle-clear-btn">נקה</a>
        </div>

        <div class="search-field search-field-wide">
            <label>חיפוש חופשי</label>
            <input type="text" name="q" id="vehicleFreeSearch" value="<?= htmlspecialchars($q) ?>"
                placeholder="לדוגמה: יונדאי אלנטרה">
        </div>
    </form>

    <div id="vehicleSearchLoading" style="display:none;padding:12px 0;color:#64748b;font-weight:700;">
        מחפש...
    </div>

    <div id="vehicleResults">
        <?php if (!$ads): ?>
            <div class="empty-state">לא נמצאו מודעות מתאימות.</div>
        <?php else: ?>
            <div class="vehicle-grid">
                <?php foreach ($ads as $ad): ?>
                    <a class="vehicle-card" href="/vehicle/view.php?id=<?= (int) $ad['id'] ?>">
                        <div class="vehicle-card-image">
                            <?php if (!empty($ad['image_path'])): ?>
                                <img src="<?= htmlspecialchars($ad['image_path']) ?>" alt="">
                            <?php else: ?>
                                <div class="no-image">🚗</div>
                            <?php endif; ?>

                            <?php if (!empty($ad['year'])): ?>
                                <span class="year-badge"><?= (int) $ad['year'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="vehicle-card-body">
                            <h2><?= htmlspecialchars(trim(($ad['maker_name'] ?? '') . ' ' . ($ad['model_name'] ?? ''))) ?></h2>

                            <div class="vehicle-meta">
                                <?php if (!empty($ad['region_name'])): ?><span>📍
                                        <?= htmlspecialchars($ad['region_name']) ?></span><?php endif; ?>
                                <?php if (!empty($ad['city_name'])): ?><span><?= htmlspecialchars($ad['city_name']) ?></span><?php endif; ?>
                            </div>

                            <div class="vehicle-meta">
                                <span>📅 <?= htmlspecialchars($ad['year'] ?? '') ?></span>
                                <span>🤝 יד <?= htmlspecialchars($ad['hand'] ?? '') ?></span>
                                <span>🛣 <?= number_format((int) $ad['km']) ?> ק״מ</span>
                            </div>

                            <div class="vehicle-meta">
                                <?php if (!empty($ad['gearbox_name'])): ?><span>⚙
                                        <?= htmlspecialchars($ad['gearbox_name']) ?></span><?php endif; ?>
                                <?php if (!empty($ad['fuel_name'])): ?><span>⛽
                                        <?= htmlspecialchars($ad['fuel_name']) ?></span><?php endif; ?>
                            </div>

                            <div class="vehicle-price">
                                ₪<?= number_format((int) $ad['price']) ?>
                                <?php if ((int) $ad['is_price_flexible'] === 1): ?><small>גמיש</small><?php endif; ?>
                            </div>

                            <div class="vehicle-card-footer">
                                <span>👁 <?= number_format((int) $ad['views']) ?> צפיות</span>
                                <span>לפרטים</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('vehicleSearchForm');
        const results = document.getElementById('vehicleResults');
        const resultsCount = document.getElementById('vehicleResultsCount');
        const loading = document.getElementById('vehicleSearchLoading');
        const freeSearch = document.getElementById('vehicleFreeSearch');

        const regionSelect = document.getElementById('region_id');
        const citySelect = document.getElementById('city_id');
        const makerSelect = document.getElementById('manufacturer_id');
        const modelSelect = document.getElementById('model_id');

        let searchTimer = null;
        let activeController = null;

        function scheduleSearch(delay = 300) {
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(runSearch, delay);
        }

        async function runSearch() {
            if (!form || !results) {
                return;
            }

            if (activeController) {
                activeController.abort();
            }

            activeController = new AbortController();

            const params = new URLSearchParams(new FormData(form));

            loading.style.display = 'block';
            results.style.opacity = '0.55';

            try {
                const response = await fetch(
                    '/ajax/search_ads.php?' + params.toString(),
                    {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json'
                        },
                        cache: 'no-store',
                        signal: activeController.signal
                    }
                );

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Search failed');
                }

                results.innerHTML = data.html;
                resultsCount.textContent = String(data.count);

                const newUrl = '/vehicle/index.php' +
                    (params.toString() ? '?' + params.toString() : '');

                window.history.replaceState(null, '', newUrl);

            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Vehicle AJAX search error:', error);
                    results.innerHTML =
                        '<div class="empty-state">אירעה שגיאה בחיפוש.</div>';
                    resultsCount.textContent = '0';
                }
            } finally {
                loading.style.display = 'none';
                results.style.opacity = '1';
            }
        }

        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                scheduleSearch(0);
            });

            form.querySelectorAll('select, input[type="number"]').forEach(function (field) {
                field.addEventListener('change', function () {
                    scheduleSearch(0);
                });
            });
        }

        if (freeSearch) {
            freeSearch.addEventListener('input', function () {
                scheduleSearch(350);
            });
        }

        if (regionSelect && citySelect) {
            regionSelect.addEventListener('change', async function () {
                const regionId = this.value;

                citySelect.innerHTML =
                    '<option value="0">טוען ערים...</option>';
                citySelect.disabled = true;

                if (!regionId || regionId === '0') {
                    citySelect.innerHTML =
                        '<option value="0">כל הערים</option>';
                    scheduleSearch(0);
                    return;
                }

                try {
                    const response = await fetch(
                        '/ajax/get_cities.php?region_id=' +
                        encodeURIComponent(regionId),
                        { cache: 'no-store' }
                    );

                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }

                    const cities = await response.json();

                    citySelect.innerHTML =
                        '<option value="0">כל הערים</option>';

                    cities.forEach(function (city) {
                        const option = document.createElement('option');
                        option.value = city.id;
                        option.textContent = city.name;
                        citySelect.appendChild(option);
                    });

                    citySelect.disabled = false;
                    scheduleSearch(0);

                } catch (error) {
                    console.error('Cities AJAX error:', error);
                    citySelect.innerHTML =
                        '<option value="0">שגיאה בטעינת ערים</option>';
                }
            });
        }

        if (makerSelect && modelSelect) {
            makerSelect.addEventListener('change', async function () {
                const makerId = this.value;

                modelSelect.innerHTML =
                    '<option value="0">טוען דגמים...</option>';
                modelSelect.disabled = true;

                if (!makerId || makerId === '0') {
                    modelSelect.innerHTML =
                        '<option value="0">כל הדגמים</option>';
                    scheduleSearch(0);
                    return;
                }

                try {
                    const response = await fetch(
                        '/vehicle/ajax/get_models.php?maker_id=' +
                        encodeURIComponent(makerId),
                        { cache: 'no-store' }
                    );

                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }

                    const models = await response.json();

                    modelSelect.innerHTML =
                        '<option value="0">כל הדגמים</option>';

                    models.forEach(function (model) {
                        const option = document.createElement('option');
                        option.value = model.id;
                        option.textContent = model.name;
                        modelSelect.appendChild(option);
                    });

                    modelSelect.disabled = false;
                    scheduleSearch(0);

                } catch (error) {
                    console.error('Models AJAX error:', error);
                    modelSelect.innerHTML =
                        '<option value="0">שגיאה בטעינת דגמים</option>';
                }
            });
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>