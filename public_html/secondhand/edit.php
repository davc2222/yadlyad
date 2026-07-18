<?php
// VERSION: SECONDHAND_EDIT_V1_2026_07_18
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    $redirect = urlencode('/secondhand/edit.php?id=' . (int) ($_GET['id'] ?? 0));
    header("Location: /login.php?redirect={$redirect}");
    exit;
}

ini_set('display_errors', 1);
error_reporting(E_ALL);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user_id = (int) $_SESSION['user_id'];
$ad_id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$message = '';
$error = '';
$main_category_id = 1;

function cleanIntOrNull($value)
{
    return ($value ?? '') !== '' ? (int) $value : null;
}

if ($ad_id <= 0) {
    http_response_code(404);
    exit('לא התקבל מספר מודעה תקין');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM secondhand_ads
    WHERE id = ?
      AND user_id = ?
      AND is_deleted = 0
    LIMIT 1
");
$stmt->execute([$ad_id, $user_id]);
$ad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ad) {
    http_response_code(404);
    exit('המודעה לא נמצאה או שאינה שייכת למשתמש המחובר');
}

$form = [
    'category_id' => (int) ($ad['category_id'] ?? 0),
    'subcategory_id' => (int) ($ad['subcategory_id'] ?? 0),
    'title' => (string) ($ad['title'] ?? ''),
    'description' => (string) ($ad['description'] ?? ''),
    'price' => (string) ($ad['price'] ?? ''),
    'is_price_flexible' => (int) ($ad['is_price_flexible'] ?? 0),
    'item_condition' => (string) ($ad['item_condition'] ?? ''),
    'region_id' => (int) ($ad['region_id'] ?? 0),
    'city_id' => (int) ($ad['city_id'] ?? 0),
    'phone' => (string) ($ad['phone'] ?? ''),
    'hide_phone' => (int) ($ad['hide_phone'] ?? 0),
    'allow_whatsapp' => (int) ($ad['allow_whatsapp'] ?? 0),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['category_id'] = (int) ($_POST['category_id'] ?? 0);
    $form['subcategory_id'] = (int) ($_POST['subcategory_id'] ?? 0);
    $form['title'] = trim((string) ($_POST['title'] ?? ''));
    $form['description'] = trim((string) ($_POST['description'] ?? ''));
    $form['price'] = trim((string) ($_POST['price'] ?? ''));
    $form['is_price_flexible'] = isset($_POST['is_price_flexible']) ? 1 : 0;
    $form['item_condition'] = (string) ($_POST['item_condition'] ?? '');
    $form['region_id'] = (int) ($_POST['region_id'] ?? 0);
    $form['city_id'] = (int) ($_POST['city_id'] ?? 0);
    $form['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $form['hide_phone'] = isset($_POST['hide_phone']) ? 1 : 0;
    $form['allow_whatsapp'] = isset($_POST['allow_whatsapp']) ? 1 : 0;

    $allowed_conditions = ['new', 'like_new', 'used', 'broken'];
    if (!in_array($form['item_condition'], $allowed_conditions, true)) {
        $form['item_condition'] = '';
    }

    $price = cleanIntOrNull($form['price']);

    if ($form['category_id'] <= 0) {
        $error = 'יש לבחור קטגוריה';
    } elseif ($form['subcategory_id'] <= 0) {
        $error = 'יש לבחור תת קטגוריה';
    } elseif ($form['title'] === '') {
        $error = 'יש להזין כותרת למודעה';
    } elseif ($form['region_id'] <= 0) {
        $error = 'יש לבחור אזור';
    } elseif ($form['city_id'] <= 0) {
        $error = 'יש לבחור עיר';
    }

    if ($error === '') {
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM categories
            WHERE id = ?
              AND parent_id = ?
              AND is_active = 1
        ");
        $checkStmt->execute([$form['subcategory_id'], $form['category_id']]);

        if ((int) $checkStmt->fetchColumn() === 0) {
            $error = 'תת הקטגוריה אינה מתאימה לקטגוריה שנבחרה';
        }
    }

    if ($error === '') {
        $cityStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM cities
            WHERE id = ?
              AND region_id = ?
        ");
        $cityStmt->execute([$form['city_id'], $form['region_id']]);

        if ((int) $cityStmt->fetchColumn() === 0) {
            $error = 'העיר אינה מתאימה לאזור שנבחר';
        }
    }

    if ($error === '') {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE secondhand_ads SET
                    category_id = ?,
                    subcategory_id = ?,
                    title = ?,
                    description = ?,
                    price = ?,
                    is_price_flexible = ?,
                    item_condition = ?,
                    region_id = ?,
                    city_id = ?,
                    phone = ?,
                    hide_phone = ?,
                    allow_whatsapp = ?,
                    updated_at = NOW()
                WHERE id = ?
                  AND user_id = ?
                  AND is_deleted = 0
            ");

            $stmt->execute([
                $form['category_id'],
                $form['subcategory_id'],
                $form['title'],
                $form['description'],
                $price,
                $form['is_price_flexible'],
                $form['item_condition'] !== '' ? $form['item_condition'] : null,
                $form['region_id'],
                $form['city_id'],
                $form['phone'],
                $form['hide_phone'],
                $form['allow_whatsapp'],
                $ad_id,
                $user_id
            ]);

            if (!empty($_FILES['images']['name'][0])) {
                $uploadBaseDir = realpath(__DIR__ . '/../uploads');

                if ($uploadBaseDir === false) {
                    mkdir(__DIR__ . '/../uploads', 0775, true);
                    $uploadBaseDir = realpath(__DIR__ . '/../uploads');
                }

                $secondhandUploadDir = $uploadBaseDir . '/secondhand/' . $ad_id;

                if (!is_dir($secondhandUploadDir)) {
                    mkdir($secondhandUploadDir, 0775, true);
                }

                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM secondhand_images WHERE ad_id = ?");
                $countStmt->execute([$ad_id]);
                $existingCount = (int) $countStmt->fetchColumn();

                $mainStmt = $pdo->prepare("SELECT COUNT(*) FROM secondhand_images WHERE ad_id = ? AND is_main = 1");
                $mainStmt->execute([$ad_id]);
                $hasMain = (int) $mainStmt->fetchColumn() > 0;

                $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
                $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];

                $imageStmt = $pdo->prepare("
                    INSERT INTO secondhand_images
                    (ad_id, image_name, image_path, sort_order, is_main, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");

                $uploadedCount = 0;

                foreach ($_FILES['images']['name'] as $index => $originalName) {
                    if ($existingCount + $uploadedCount >= 12) {
                        break;
                    }

                    if (($_FILES['images']['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $tmpName = $_FILES['images']['tmp_name'][$index];
                    $size = (int) ($_FILES['images']['size'][$index] ?? 0);

                    if ($size <= 0 || $size > 5 * 1024 * 1024 || !is_uploaded_file($tmpName)) {
                        continue;
                    }

                    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExt, true)) {
                        continue;
                    }

                    $mime = mime_content_type($tmpName);
                    if (!in_array($mime, $allowedMime, true)) {
                        continue;
                    }

                    $newName = uniqid('secondhand_', true) . '.' . $ext;
                    $targetPath = $secondhandUploadDir . '/' . $newName;

                    if (!move_uploaded_file($tmpName, $targetPath)) {
                        continue;
                    }

                    $relativePath = '/uploads/secondhand/' . $ad_id . '/' . $newName;
                    $isMain = $hasMain ? 0 : 1;

                    $imageStmt->execute([
                        $ad_id,
                        $newName,
                        $relativePath,
                        $existingCount + $uploadedCount,
                        $isMain
                    ]);

                    $hasMain = true;
                    $uploadedCount++;
                }
            }

            $pdo->commit();
            header('Location: /secondhand/view.php?id=' . $ad_id . '&updated=1');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'שגיאה בעדכון המודעה: ' . $e->getMessage();
        }
    }
}

$categories = $pdo->prepare("
    SELECT id, name
    FROM categories
    WHERE parent_id = ?
      AND is_active = 1
    ORDER BY sort_order, name
");
$categories->execute([$main_category_id]);
$categories = $categories->fetchAll(PDO::FETCH_ASSOC);

$selected_category_id = (int) $form['category_id'];

$subcategories = [];
if ($selected_category_id > 0) {
    $subStmt = $pdo->prepare("
        SELECT id, name
        FROM categories
        WHERE parent_id = ?
          AND is_active = 1
        ORDER BY sort_order, name
    ");
    $subStmt->execute([$selected_category_id]);
    $subcategories = $subStmt->fetchAll(PDO::FETCH_ASSOC);
}

$regions = $pdo->query("
    SELECT id, name
    FROM regions
    WHERE is_active = 1
    ORDER BY sort_order, name
")->fetchAll(PDO::FETCH_ASSOC);

$cities = [];
if ($form['region_id'] > 0) {
    $cityStmt = $pdo->prepare("
        SELECT id, name
        FROM cities
        WHERE region_id = ?
        ORDER BY name
    ");
    $cityStmt->execute([$form['region_id']]);
    $cities = $cityStmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once '../includes/header.php';
?>

<link rel="stylesheet" href="/vehicle/css/vehicle_form.css">

<section class="vehicle-form-page">

    <?php if ($message): ?>
        <div class="form-alert success">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="form-alert error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="/secondhand/edit.php?id=<?= $ad_id ?>" class="vehicle-form"
        enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $ad_id ?>">

        <div class="form-topline">
            <div>
                <span class="form-eyebrow">עריכת מודעת יד שנייה</span>
                <strong>עדכן את פרטי המוצר ושמור את השינויים</strong>
            </div>
            <a href="/secondhand/index.php" class="top-back-link">חזרה ליד שנייה</a>
        </div>

        <div class="form-section">
            <div class="section-title">פרטי המוצר</div>

            <div class="form-grid compact-grid">

                <div class="field">
                    <label>קטגוריה</label>
                    <select name="category_id" id="category_id" required>
                        <option value="">בחר קטגוריה</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>" <?= $selected_category_id === (int) $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>תת קטגוריה</label>
                    <select name="subcategory_id" id="subcategory_id" required>
                        <option value="">בחר קודם קטגוריה</option>
                        <?php foreach ($subcategories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>" <?= (int) $form['subcategory_id'] === (int) $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>כותרת מודעה</label>
                    <input type="text" name="title" maxlength="200" required
                        placeholder="לדוגמה: ספה 3 מושבים במצב מעולה" value="<?= htmlspecialchars($form['title']) ?>">
                </div>

                <div class="field">
                    <label>מצב המוצר</label>
                    <select name="item_condition">
                        <option value="">בחר מצב</option>
                        <option value="new" <?= $form['item_condition'] === 'new' ? 'selected' : '' ?>>חדש</option>
                        <option value="like_new" <?= $form['item_condition'] === 'like_new' ? 'selected' : '' ?>>כמו חדש
                        </option>
                        <option value="used" <?= $form['item_condition'] === 'used' ? 'selected' : '' ?>>משומש</option>
                        <option value="broken" <?= $form['item_condition'] === 'broken' ? 'selected' : '' ?>>לתיקון / לא
                            תקין</option>
                    </select>
                </div>

                <div class="field">
                    <label>מחיר</label>
                    <input type="number" name="price" min="0" value="<?= htmlspecialchars($form['price']) ?>">
                </div>

            </div>
        </div>

        <div class="form-section">
            <div class="section-title">מיקום</div>

            <div class="form-grid price-grid">

                <div class="field location-field">
                    <label>אזור</label>
                    <select name="region_id" id="region_id" required>
                        <option value="">בחר אזור</option>
                        <?php foreach ($regions as $region): ?>
                            <option value="<?= (int) $region['id'] ?>" <?= (int) $form['region_id'] === (int) $region['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($region['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field location-field">
                    <label>עיר</label>
                    <select name="city_id" id="city_id" required>
                        <option value="">בחר עיר</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= (int) $city['id'] ?>" <?= (int) $form['city_id'] === (int) $city['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($city['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>
        </div>

        <div class="form-section">
            <div class="section-title">מחיר ויצירת קשר</div>

            <div class="form-grid price-grid">

                <div class="check-row small-options">
                    <label><input type="checkbox" name="is_price_flexible" <?= $form['is_price_flexible'] ? 'checked' : '' ?>> מחיר גמיש</label>
                    <label><input type="checkbox" name="allow_whatsapp" <?= $form['allow_whatsapp'] ? 'checked' : '' ?>>
                        WhatsApp</label>
                </div>

                <div class="field">
                    <label>טלפון ליצירת קשר</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($form['phone']) ?>">
                </div>

                <div class="check-row small-options">
                    <label><input type="checkbox" name="hide_phone" <?= $form['hide_phone'] ? 'checked' : '' ?>> הסתר
                        טלפון</label>
                </div>

            </div>
        </div>

        <div class="form-section desc-feature-section">
            <div class="desc-box">
                <div class="section-title">תיאור המודעה</div>
                <textarea name="description" rows="7"
                    placeholder="כתוב פרטים חשובים על המוצר: מצב, מידות, גיל, סיבת מכירה וכל מידע שיעזור לקונה."><?= htmlspecialchars($form['description']) ?></textarea>
            </div>

            <div class="features-box">
                <div class="section-title">טיפים למודעה טובה</div>

                <div class="features-grid">

                    <label>✔ ציין מצב אמיתי</label>
                    <label>✔ הוסף כמה תמונות</label>
                    <label>✔ ציין אם המחיר גמיש</label>
                    <label>✔ פרט מידות אם רלוונטי</label>
                    <label>✔ כתוב אזור ועיר מדויקים</label>
                </div>
            </div>
        </div>

        <div class="form-section upload-section">
            <div class="section-title">תמונות</div>

            <label class="upload-box" id="uploadBox">
                <input type="file" name="images[]" id="secondhandImagesInput" multiple
                    accept="image/jpeg,image/png,image/webp">
                <span class="upload-icon">＋</span>
                <strong>העלה תמונות של המוצר</strong>
                <small>אפשר לבחור כמה תמונות יחד. JPG, PNG או WEBP עד 5MB לתמונה.</small>
            </label>

            <div class="images-preview-head" id="imagesPreviewHead" style="display:none;">
                <strong>תמונות שנבחרו</strong>
                <span id="imagesCounter">0 תמונות</span>
            </div>

            <div class="images-preview-grid" id="imagesPreviewGrid"></div>
        </div>

        <div class="form-actions">
            <a href="/secondhand/view.php?id=<?= $ad_id ?>" class="btn-secondary">ביטול</a>
            <button type="submit" class="btn-main">שמור שינויים</button>
        </div>

    </form>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const categorySelect = document.getElementById('category_id');
        const subcategorySelect = document.getElementById('subcategory_id');

        function loadSubcategories(parentId) {
            if (!subcategorySelect) {
                return;
            }

            subcategorySelect.innerHTML = '<option value="">טוען...</option>';

            if (!parentId) {
                subcategorySelect.innerHTML = '<option value="">בחר קודם קטגוריה</option>';
                return;
            }

            fetch('/ajax/get_subcategories.php?category_id=' + encodeURIComponent(parentId))
                .then(response => response.json())
                .then(data => {
                    subcategorySelect.innerHTML = '<option value="">בחר תת קטגוריה</option>';

                    data.forEach(cat => {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        option.textContent = cat.name;
                        subcategorySelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.log(error);
                    subcategorySelect.innerHTML = '<option value="">שגיאה בטעינת תתי קטגוריות</option>';
                });
        }

        if (categorySelect && subcategorySelect) {
            categorySelect.addEventListener('change', function () {
                loadSubcategories(this.value);
            });
        }

        const regionSelect = document.getElementById('region_id');
        const citySelect = document.getElementById('city_id');

        if (regionSelect && citySelect) {
            regionSelect.addEventListener('change', function () {
                const regionId = this.value;

                citySelect.innerHTML = '<option value="">טוען...</option>';

                if (!regionId) {
                    citySelect.innerHTML = '<option value="">בחר קודם אזור</option>';
                    return;
                }

                fetch('/ajax/get_cities.php?region_id=' + encodeURIComponent(regionId))
                    .then(response => response.json())
                    .then(data => {
                        citySelect.innerHTML = '<option value="">בחר עיר</option>';

                        data.forEach(city => {
                            const option = document.createElement('option');
                            option.value = city.id;
                            option.textContent = city.name;
                            citySelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.log(error);
                        citySelect.innerHTML = '<option value="">שגיאה בטעינת ערים</option>';
                    });
            });
        }

        const imagesInput = document.getElementById('secondhandImagesInput');
        const previewGrid = document.getElementById('imagesPreviewGrid');
        const previewHead = document.getElementById('imagesPreviewHead');
        const imagesCounter = document.getElementById('imagesCounter');
        const uploadBox = document.getElementById('uploadBox');
        const maxImages = 12;
        let selectedFiles = [];

        function updateInputFiles() {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            imagesInput.files = dataTransfer.files;
        }

        function renderPreviews() {
            previewGrid.innerHTML = '';

            if (selectedFiles.length === 0) {
                previewHead.style.display = 'none';
                imagesCounter.textContent = '0 תמונות';
                return;
            }

            previewHead.style.display = 'flex';
            imagesCounter.textContent = selectedFiles.length === 1 ? 'תמונה אחת' : selectedFiles.length + ' תמונות';

            selectedFiles.forEach((file, index) => {
                const card = document.createElement('div');
                card.className = 'image-preview-card';

                const img = document.createElement('img');
                img.alt = file.name;
                img.src = URL.createObjectURL(file);
                img.onload = function () {
                    URL.revokeObjectURL(img.src);
                };

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'remove-image-btn';
                removeButton.textContent = '×';
                removeButton.setAttribute('aria-label', 'הסר תמונה');
                removeButton.addEventListener('click', function () {
                    selectedFiles.splice(index, 1);
                    updateInputFiles();
                    renderPreviews();
                });

                const badge = document.createElement('span');
                badge.className = 'main-image-badge';
                badge.textContent = index === 0 ? 'ראשית' : (index + 1);

                card.appendChild(img);
                card.appendChild(removeButton);
                card.appendChild(badge);
                previewGrid.appendChild(card);
            });
        }

        if (imagesInput && previewGrid && previewHead && imagesCounter) {
            imagesInput.addEventListener('change', function () {
                const newFiles = Array.from(this.files).filter(file => file.type.startsWith('image/'));
                selectedFiles = selectedFiles.concat(newFiles).slice(0, maxImages);
                updateInputFiles();
                renderPreviews();
            });
        }

        if (uploadBox) {
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadBox.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    uploadBox.classList.add('drag-over');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadBox.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    uploadBox.classList.remove('drag-over');
                });
            });

            uploadBox.addEventListener('drop', function (event) {
                const droppedFiles = Array.from(event.dataTransfer.files).filter(file => file.type.startsWith('image/'));
                selectedFiles = selectedFiles.concat(droppedFiles).slice(0, maxImages);
                updateInputFiles();
                renderPreviews();
            });
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>