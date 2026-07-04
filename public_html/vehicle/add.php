<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id = 1;
    $category_id = 1;
    $subcategory_id = 1;

    $vehicle_category_id = (int) ($_POST['vehicle_category_id'] ?? 0);

    // אין יותר שדה כותרת בטופס. המסד דורש title, לכן נשמרת כותרת אוטומטית.
    $title = 'מודעת רכב';
    $description = trim($_POST['description'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $manufacturer_id = (int) ($_POST['manufacturer_id'] ?? 0);
    $model_id = (int) ($_POST['model_id'] ?? 0);
    $year = (int) ($_POST['year'] ?? 0);

    $road_month = ($_POST['road_month'] ?? '') !== '' ? (int) $_POST['road_month'] : null;
    $hand = ($_POST['hand'] ?? '') !== '' ? (int) $_POST['hand'] : null;
    $km = ($_POST['km'] ?? '') !== '' ? (int) $_POST['km'] : null;
    $price = ($_POST['price'] ?? '') !== '' ? (int) $_POST['price'] : null;

    if (($_POST['is_price_flexible'] ?? '') === '') {
        $is_price_flexible = null;
    } else {
        $is_price_flexible = (int) $_POST['is_price_flexible'];
    }

    $gearbox_id = ($_POST['gearbox_id'] ?? '') !== '' ? (int) $_POST['gearbox_id'] : null;
    $fuel_type_id = ($_POST['fuel_type_id'] ?? '') !== '' ? (int) $_POST['fuel_type_id'] : null;
    $color_id = ($_POST['color_id'] ?? '') !== '' ? (int) $_POST['color_id'] : null;
    $body_type_id = ($_POST['body_type_id'] ?? '') !== '' ? (int) $_POST['body_type_id'] : null;
    $drive_type_id = ($_POST['drive_type_id'] ?? '') !== '' ? (int) $_POST['drive_type_id'] : null;
    $engine_volume = ($_POST['engine_volume'] ?? '') !== '' ? (int) $_POST['engine_volume'] : null;
    $doors = ($_POST['doors'] ?? '') !== '' ? (int) $_POST['doors'] : null;
    $seats = ($_POST['seats'] ?? '') !== '' ? (int) $_POST['seats'] : null;
    $ownership_type_id = ($_POST['ownership_type_id'] ?? '') !== '' ? (int) $_POST['ownership_type_id'] : null;
    $condition_id = ($_POST['condition_id'] ?? '') !== '' ? (int) $_POST['condition_id'] : null;

    $test_until_raw = trim($_POST['test_until'] ?? '');
    $test_until = $test_until_raw !== '' ? $test_until_raw . '-01' : null;

    $has_abs = isset($_POST['has_abs']) ? 1 : 0;
    $has_esp = isset($_POST['has_esp']) ? 1 : 0;
    $has_airbags = isset($_POST['has_airbags']) ? 1 : 0;
    $has_reverse_camera = isset($_POST['has_reverse_camera']) ? 1 : 0;
    $has_parking_sensors = isset($_POST['has_parking_sensors']) ? 1 : 0;
    $has_sunroof = isset($_POST['has_sunroof']) ? 1 : 0;
    $has_multimedia = isset($_POST['has_multimedia']) ? 1 : 0;
    $has_navigation = isset($_POST['has_navigation']) ? 1 : 0;
    $has_cruise_control = isset($_POST['has_cruise_control']) ? 1 : 0;
    $has_alloy_wheels = isset($_POST['has_alloy_wheels']) ? 1 : 0;
    $has_leather_seats = isset($_POST['has_leather_seats']) ? 1 : 0;
    $has_android_auto = isset($_POST['has_android_auto']) ? 1 : 0;
    $has_apple_carplay = isset($_POST['has_apple_carplay']) ? 1 : 0;

    $hide_phone = isset($_POST['hide_phone']) ? 1 : 0;
    $allow_whatsapp = isset($_POST['allow_whatsapp']) ? 1 : 0;

    if ($vehicle_category_id <= 0) {
        $error = 'יש לבחור קטגוריית רכב';
    } elseif ($manufacturer_id <= 0) {
        $error = 'יש לבחור יצרן';
    } elseif ($model_id <= 0) {
        $error = 'יש לבחור דגם';
    } elseif ($year <= 0) {
        $error = 'יש לבחור שנת ייצור';
    } else {

        $stmt = $pdo->prepare("
            INSERT INTO vehicle_ads
            (
                user_id, category_id, subcategory_id, vehicle_category_id,
                title, description, price, is_price_flexible,
                manufacturer_id, model_id, year, road_month,
                hand, km,
                body_type_id, gearbox_id, fuel_type_id,
                engine_volume, drive_type_id, doors, seats,
                color_id, ownership_type_id, condition_id, test_until,
                has_abs, has_esp, has_airbags, has_reverse_camera,
                has_parking_sensors, has_sunroof, has_multimedia,
                has_navigation, has_cruise_control, has_alloy_wheels,
                has_leather_seats, has_android_auto, has_apple_carplay,
                phone, hide_phone, allow_whatsapp,
                status, created_at
            )
            VALUES
            (
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                'pending', NOW()
            )
        ");

        try {
            $stmt->execute([
                $user_id,
                $category_id,
                $subcategory_id,
                $vehicle_category_id,
                $title,
                $description,
                $price,
                $is_price_flexible,
                $manufacturer_id,
                $model_id,
                $year,
                $road_month,
                $hand,
                $km,
                $body_type_id,
                $gearbox_id,
                $fuel_type_id,
                $engine_volume,
                $drive_type_id,
                $doors,
                $seats,
                $color_id,
                $ownership_type_id,
                $condition_id,
                $test_until,
                $has_abs,
                $has_esp,
                $has_airbags,
                $has_reverse_camera,
                $has_parking_sensors,
                $has_sunroof,
                $has_multimedia,
                $has_navigation,
                $has_cruise_control,
                $has_alloy_wheels,
                $has_leather_seats,
                $has_android_auto,
                $has_apple_carplay,
                $phone,
                $hide_phone,
                $allow_whatsapp
            ]);
        } catch (PDOException $e) {
            die('<pre>שגיאת שמירה: ' . $e->getMessage() . '</pre>');
        }

        $ad_id = (int) $pdo->lastInsertId();

        if (!empty($_FILES['images']['name'][0])) {

            $uploadBaseDir = realpath(__DIR__ . '/../uploads');

            if ($uploadBaseDir === false) {
                mkdir(__DIR__ . '/../uploads', 0775, true);
                $uploadBaseDir = realpath(__DIR__ . '/../uploads');
            }

            $vehicleUploadDir = $uploadBaseDir . '/vehicles/' . $ad_id;

            if (!is_dir($vehicleUploadDir)) {
                mkdir($vehicleUploadDir, 0775, true);
            }

            $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
            $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];

            $imageStmt = $pdo->prepare("
                INSERT INTO vehicle_images
                (ad_id, image_name, image_path, sort_order, is_main, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            $sortOrder = 0;
            $hasMain = false;

            foreach ($_FILES['images']['name'] as $index => $originalName) {

                if ($_FILES['images']['error'][$index] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $tmpName = $_FILES['images']['tmp_name'][$index];
                $size = (int) $_FILES['images']['size'][$index];

                if ($size <= 0 || $size > 5 * 1024 * 1024) {
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

                $newName = uniqid('vehicle_', true) . '.' . $ext;
                $targetPath = $vehicleUploadDir . '/' . $newName;

                if (move_uploaded_file($tmpName, $targetPath)) {

                    $relativePath = '/uploads/vehicles/' . $ad_id . '/' . $newName;
                    $isMain = $hasMain ? 0 : 1;

                    $imageStmt->execute([
                        $ad_id,
                        $newName,
                        $relativePath,
                        $sortOrder,
                        $isMain
                    ]);

                    $hasMain = true;
                    $sortOrder++;
                }
            }
        }

        $message = 'המודעה נשמרה בהצלחה וממתינה לאישור';
    }
}

$vehicle_categories = $pdo->query("
    SELECT id, name
    FROM vehicle_categories
    WHERE is_active = 1
    ORDER BY sort_order, name
")->fetchAll(PDO::FETCH_ASSOC);

$makers = $pdo->query("SELECT id, name FROM car_makers WHERE is_active = 1 ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC);
$gearboxes = $pdo->query("SELECT id, name FROM gearboxes WHERE is_active = 1 ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC);
$fuel_types = $pdo->query("SELECT id, name FROM fuel_types WHERE is_active = 1 ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC);
$colors = $pdo->query("SELECT id, name FROM vehicle_colors WHERE is_active = 1 ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC);
$body_types = $pdo->query("SELECT id, name FROM vehicle_body_types WHERE is_active = 1 ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC);
$drive_types = $pdo->query("SELECT id, name FROM vehicle_drive_types WHERE is_active = 1 ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC);
$conditions = $pdo->query("SELECT id, name FROM vehicle_conditions WHERE is_active = 1 ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC);
$ownership_types = $pdo->query("SELECT id, name FROM ownership_types WHERE is_active = 1 ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="/vehicle/css/vehicle_form.css">

<section class="vehicle-form-page">

    <?php if ($message): ?>
        <div class="form-alert success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="form-alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="" class="vehicle-form" enctype="multipart/form-data">

        <div class="form-topline">
            <div>
                <span class="form-eyebrow">פרסום רכב</span>
                <strong>מלא את הפרטים והעלה מודעה</strong>
            </div>
            <a href="/vehicle/index.php" class="top-back-link">חזרה לרכבים</a>
        </div>

        <div class="form-section">
            <div class="section-title">פרטי הרכב</div>

            <div class="form-grid compact-grid">
                <div class="field">
                    <label>קטגוריה</label>
                    <select name="vehicle_category_id" required>
                        <option value="">בחר קטגוריה</option>
                        <?php foreach ($vehicle_categories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>יצרן</label>
                    <select name="manufacturer_id" id="manufacturer_id" required>
                        <option value="">בחר יצרן</option>
                        <?php foreach ($makers as $maker): ?>
                            <option value="<?= (int) $maker['id'] ?>"><?= htmlspecialchars($maker['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>דגם</label>
                    <select name="model_id" id="model_id" required>
                        <option value="">בחר דגם</option>
                    </select>
                </div>

                <div class="field">
                    <label>שנת ייצור</label>
                    <input type="number" name="year" min="1950" max="<?= date('Y') + 1 ?>" required>
                </div>

                <div class="field">
                    <label>חודש עליה לכביש</label>
                    <select name="road_month">
                        <option value="">בחר חודש</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="field">
                    <label>יד</label>
                    <select name="hand">
                        <option value="">בחר יד</option>
                        <?php for ($i = 0; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="field">
                    <label>קילומטר</label>
                    <input type="number" name="km" min="0">
                </div>

                <div class="field">
                    <label>תיבת הילוכים</label>
                    <select name="gearbox_id">
                        <option value="">בחר</option>
                        <?php foreach ($gearboxes as $row): ?>
                            <option value="<?= (int) $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>סוג דלק</label>
                    <select name="fuel_type_id">
                        <option value="">בחר</option>
                        <?php foreach ($fuel_types as $row): ?>
                            <option value="<?= (int) $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>נפח מנוע</label>
                    <input type="number" name="engine_volume" min="0">
                </div>

                <div class="field">
                    <label>סוג מרכב</label>
                    <select name="body_type_id">
                        <option value="">בחר</option>
                        <?php foreach ($body_types as $row): ?>
                            <option value="<?= (int) $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>הנעה</label>
                    <select name="drive_type_id">
                        <option value="">בחר</option>
                        <?php foreach ($drive_types as $row): ?>
                            <option value="<?= (int) $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>דלתות</label>
                    <input type="number" name="doors" min="0">
                </div>

                <div class="field">
                    <label>מקומות ישיבה</label>
                    <input type="number" name="seats" min="0">
                </div>

                <div class="field">
                    <label>בעלות</label>
                    <select name="ownership_type_id">
                        <option value="">בחר</option>
                        <?php foreach ($ownership_types as $row): ?>
                            <option value="<?= (int) $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>מצב הרכב</label>
                    <select name="condition_id">
                        <option value="">בחר</option>
                        <?php foreach ($conditions as $row): ?>
                            <option value="<?= (int) $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>צבע</label>
                    <select name="color_id">
                        <option value="">בחר</option>
                        <?php foreach ($colors as $row): ?>
                            <option value="<?= (int) $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>טסט עד</label>
                    <input type="month" name="test_until">
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="section-title">מחיר ויצירת קשר</div>

            <div class="form-grid price-grid">
                <div class="field">
                    <label>מחיר</label>
                    <input type="number" name="price" min="0">
                </div>

                <div class="field">
                    <label>מחיר גמיש</label>
                    <select name="is_price_flexible">
                        <option value="">לא משנה</option>
                        <option value="1">כן</option>
                        <option value="0">לא</option>
                    </select>
                </div>

                <div class="field">
                    <label>טלפון ליצירת קשר</label>
                    <input type="text" name="phone">
                </div>

                <div class="check-row small-options">
                    <label><input type="checkbox" name="hide_phone"> הסתר טלפון</label>
                    <label><input type="checkbox" name="allow_whatsapp" checked> WhatsApp</label>
                </div>
            </div>
        </div>

        <div class="form-section desc-feature-section">
            <div class="desc-box">
                <div class="section-title">תיאור המודעה</div>
                <textarea name="description" rows="7"
                    placeholder="כתוב פרטים חשובים על מצב הרכב, טיפולים, בעלות, תוספות וכל מידע שיעזור לקונה."></textarea>
            </div>

            <div class="features-box">
                <div class="section-title">אבזור</div>
                <div class="features-grid">
                    <label><input type="checkbox" name="has_abs"> ABS</label>
                    <label><input type="checkbox" name="has_esp"> ESP</label>
                    <label><input type="checkbox" name="has_airbags"> כריות אוויר</label>
                    <label><input type="checkbox" name="has_reverse_camera"> מצלמת רוורס</label>
                    <label><input type="checkbox" name="has_parking_sensors"> חיישני רוורס</label>
                    <label><input type="checkbox" name="has_sunroof"> גג שמש</label>
                    <label><input type="checkbox" name="has_multimedia"> מולטימדיה</label>
                    <label><input type="checkbox" name="has_navigation"> ניווט</label>
                    <label><input type="checkbox" name="has_cruise_control"> בקרת שיוט</label>
                    <label><input type="checkbox" name="has_alloy_wheels"> חישוקי סגסוגת</label>
                    <label><input type="checkbox" name="has_leather_seats"> ריפודי עור</label>
                    <label><input type="checkbox" name="has_android_auto"> Android Auto</label>
                    <label><input type="checkbox" name="has_apple_carplay"> Apple CarPlay</label>
                </div>
            </div>
        </div>

        <div class="form-section upload-section">
            <div class="section-title">תמונות</div>

            <label class="upload-box" id="uploadBox">
                <input type="file" name="images[]" id="vehicleImagesInput" multiple
                    accept="image/jpeg,image/png,image/webp">
                <span class="upload-icon">＋</span>
                <strong>העלה תמונות של הרכב</strong>
                <small>אפשר לבחור כמה תמונות יחד. JPG, PNG או WEBP עד 5MB לתמונה.</small>
            </label>

            <div class="images-preview-head" id="imagesPreviewHead" style="display:none;">
                <strong>תמונות שנבחרו</strong>
                <span id="imagesCounter">0 תמונות</span>
            </div>

            <div class="images-preview-grid" id="imagesPreviewGrid"></div>
        </div>

        <div class="form-actions">
            <a href="/vehicle/index.php" class="btn-secondary">ביטול</a>
            <button type="submit" class="btn-main">פרסם מודעה</button>
        </div>

    </form>

</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const manufacturerSelect = document.getElementById('manufacturer_id');
        const modelSelect = document.getElementById('model_id');

        if (!manufacturerSelect || !modelSelect) {
            return;
        }

        manufacturerSelect.addEventListener('change', function () {
            const manufacturerId = this.value;

            modelSelect.innerHTML = '<option value="">טוען...</option>';

            if (!manufacturerId) {
                modelSelect.innerHTML = '<option value="">בחר דגם</option>';
                return;
            }

         //   fetch('/vehicle/ajax/get_models.php?manufacturer_id=' + encodeURIComponent(manufacturerId))

            fetch('/vehicle/ajax/get_models.php?maker_id=' + encodeURIComponent(manufacturerId))
                .then(response => response.json())
                .then(data => {
                    modelSelect.innerHTML = '<option value="">בחר דגם</option>';

                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.name;
                        modelSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.log(error);
                    modelSelect.innerHTML = '<option value="">שגיאה בטעינת דגמים</option>';
                });
        });

        const imagesInput = document.getElementById('vehicleImagesInput');
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