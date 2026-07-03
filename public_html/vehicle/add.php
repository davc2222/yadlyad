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

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $manufacturer_id = (int) ($_POST['manufacturer_id'] ?? 0);
    $model_id = (int) ($_POST['model_id'] ?? 0);
    $year = (int) ($_POST['year'] ?? 0);

    $road_month = ($_POST['road_month'] ?? '') !== '' ? (int) $_POST['road_month'] : null;
    $hand = ($_POST['hand'] ?? '') !== '' ? (int) $_POST['hand'] : null;
    $km = ($_POST['km'] ?? '') !== '' ? (int) $_POST['km'] : null;
    $price = ($_POST['price'] ?? '') !== '' ? (int) $_POST['price'] : null;
    $is_price_flexible = isset($_POST['is_price_flexible']) ? 1 : 0;

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

    if ($test_until_raw !== '') {
        // input type="month" מחזיר YYYY-MM, אבל במסד זה DATE
        $test_until = $test_until_raw . '-01';
    } else {
        $test_until = null;
    }

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
    if ($_POST['is_price_flexible'] === '') {
        $is_price_flexible = null;
    } else {
        $is_price_flexible = (int) $_POST['is_price_flexible'];
    }
    $hide_phone = isset($_POST['hide_phone']) ? 1 : 0;
    $allow_whatsapp = isset($_POST['allow_whatsapp']) ? 1 : 0;

    if ($title === '') {
        $error = 'יש להזין כותרת';
    } elseif ($vehicle_category_id <= 0) {
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

    <h1>פרסום מודעת רכב</h1>

    <?php if ($message): ?>
        <div style="background:#d4edda;padding:12px;margin:15px 0;border:1px solid #c3e6cb;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background:#f8d7da;padding:12px;margin:15px 0;border:1px solid #f5c6cb;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="" class="vehicle-form" enctype="multipart/form-data">

        <?php require 'includes/step1_vehicle.php'; ?>
        <?php require 'includes/step2_features.php'; ?>
        <?php require 'includes/step3_images.php'; ?>
        <?php require 'includes/step4_publish.php'; ?>

        <button type="submit" class="btn-main">שמור מודעה</button>

    </form>

</section>

<script src="/vehicle/includes/wizard.js"></script>

<?php require_once '../includes/footer.php'; ?>