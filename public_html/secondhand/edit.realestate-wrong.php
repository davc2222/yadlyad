<?php
// VERSION: REAL_ESTATE_EDIT_V1_2026_07_18
session_start();

require_once '../includes/db.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (empty($_SESSION['user_id'])) {
    $redirect = urlencode('/realestate/edit.php?id=' . (int)($_GET['id'] ?? 0));
    header("Location: /login.php?redirect={$redirect}");
    exit;
}

$userId = (int) $_SESSION['user_id'];
$adId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$message = '';
$error = '';

if ($adId <= 0) {
    http_response_code(404);
    exit('לא התקבל מספר מודעה תקין.');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM realestate_ads
    WHERE id = ?
      AND user_id = ?
      AND is_deleted = 0
    LIMIT 1
");
$stmt->execute([$adId, $userId]);
$ad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ad) {
    http_response_code(404);
    exit('המודעה לא נמצאה או שאינה שייכת למשתמש המחובר.');
}

$stmt = $pdo->prepare("
    SELECT id, name
    FROM categories
    WHERE parent_id = 3
      AND is_active = 1
    ORDER BY sort_order, name
");
$stmt->execute();
$subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT id, name
    FROM regions
    ORDER BY name
");
$regions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$form = [
    'subcategory_id'       => (string)($ad['subcategory_id'] ?? ''),
    'title'                => (string)($ad['title'] ?? ''),
    'description'          => (string)($ad['description'] ?? ''),
    'property_type'        => (string)($ad['property_type'] ?? ''),
    'deal_type'            => (string)($ad['deal_type'] ?? 'sale'),
    'price'                => (string)($ad['price'] ?? ''),
    'is_price_flexible'    => (int)($ad['is_price_flexible'] ?? 0),
    'region_id'            => (string)($ad['region_id'] ?? ''),
    'city_id'              => (string)($ad['city_id'] ?? ''),
    'street'               => (string)($ad['street'] ?? ''),
    'house_number'         => (string)($ad['house_number'] ?? ''),
    'neighborhood'         => (string)($ad['neighborhood'] ?? ''),
    'rooms'                => (string)($ad['rooms'] ?? ''),
    'floor'                => (string)($ad['floor'] ?? ''),
    'total_floors'         => (string)($ad['total_floors'] ?? ''),
    'square_meters'        => (string)($ad['square_meters'] ?? ''),
    'entrance_date'        => (string)($ad['entrance_date'] ?? ''),
    'immediate_entrance'   => (int)($ad['immediate_entrance'] ?? 0),
    'parking_spaces'       => (int)($ad['parking_spaces'] ?? 0),
    'balconies'            => (int)($ad['balconies'] ?? 0),
    'bathrooms'            => (int)($ad['bathrooms'] ?? 1),
    'has_elevator'         => (int)($ad['has_elevator'] ?? 0),
    'has_air_conditioning' => (int)($ad['has_air_conditioning'] ?? 0),
    'has_storage'          => (int)($ad['has_storage'] ?? 0),
    'has_safe_room'        => (int)($ad['has_safe_room'] ?? 0),
    'has_bars'             => (int)($ad['has_bars'] ?? 0),
    'has_accessibility'    => (int)($ad['has_accessibility'] ?? 0),
    'has_furniture'        => (int)($ad['has_furniture'] ?? 0),
    'has_renovation'       => (int)($ad['has_renovation'] ?? 0),
    'has_pets'             => (int)($ad['has_pets'] ?? 0),
    'phone'                => (string)($ad['phone'] ?? ''),
    'hide_phone'           => (int)($ad['hide_phone'] ?? 0),
    'allow_whatsapp'       => (int)($ad['allow_whatsapp'] ?? 1)
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $defaultValue) {
        if (is_int($defaultValue)) {
            $form[$key] = isset($_POST[$key]) ? (int) $_POST[$key] : 0;
        } else {
            $form[$key] = trim((string)($_POST[$key] ?? ''));
        }
    }

    $checkboxes = [
        'is_price_flexible',
        'immediate_entrance',
        'has_elevator',
        'has_air_conditioning',
        'has_storage',
        'has_safe_room',
        'has_bars',
        'has_accessibility',
        'has_furniture',
        'has_renovation',
        'has_pets',
        'hide_phone',
        'allow_whatsapp'
    ];

    foreach ($checkboxes as $checkbox) {
        $form[$checkbox] = isset($_POST[$checkbox]) ? 1 : 0;
    }

    $subcategoryId = (int)$form['subcategory_id'];
    $regionId = (int)$form['region_id'];
    $cityId = (int)$form['city_id'];
    $title = $form['title'];
    $propertyType = $form['property_type'];
    $dealType = $form['deal_type'];

    if (!in_array($dealType, ['sale', 'rent', 'roommates', 'commercial'], true)) {
        $dealType = 'sale';
        $form['deal_type'] = 'sale';
    }

    $price = $form['price'] !== ''
        ? max(0, (int)str_replace(',', '', $form['price']))
        : null;

    $rooms = $form['rooms'] !== '' ? (float)$form['rooms'] : null;
    $floor = $form['floor'] !== '' ? (int)$form['floor'] : null;
    $totalFloors = $form['total_floors'] !== '' ? (int)$form['total_floors'] : null;
    $squareMeters = $form['square_meters'] !== '' ? max(0, (int)$form['square_meters']) : null;
    $entranceDate = $form['entrance_date'] !== '' ? $form['entrance_date'] : null;

    if ($form['immediate_entrance']) {
        $entranceDate = null;
    }

    if ($subcategoryId <= 0) {
        $error = 'יש לבחור קטגוריית נכס.';
    } elseif ($title === '') {
        $error = 'יש להזין כותרת למודעה.';
    } elseif (mb_strlen($title) > 150) {
        $error = 'כותרת המודעה ארוכה מדי.';
    } elseif ($propertyType === '') {
        $error = 'יש לבחור סוג נכס.';
    } elseif ($regionId <= 0) {
        $error = 'יש לבחור אזור.';
    } elseif ($cityId <= 0) {
        $error = 'יש לבחור עיר.';
    } elseif ($form['phone'] === '') {
        $error = 'יש להזין מספר טלפון.';
    }

    if ($error === '') {
        $stmt = $pdo->prepare("
            SELECT id
            FROM categories
            WHERE id = ?
              AND parent_id = 3
              AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$subcategoryId]);

        if (!$stmt->fetchColumn()) {
            $error = 'קטגוריית הנכס שנבחרה אינה תקינה.';
        }
    }

    if ($error === '') {
        $stmt = $pdo->prepare("
            SELECT id
            FROM cities
            WHERE id = ?
              AND region_id = ?
            LIMIT 1
        ");
        $stmt->execute([$cityId, $regionId]);

        if (!$stmt->fetchColumn()) {
            $error = 'העיר שנבחרה אינה שייכת לאזור.';
        }
    }

    if ($error === '') {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE realestate_ads SET
                    subcategory_id = ?,
                    title = ?,
                    description = ?,
                    property_type = ?,
                    deal_type = ?,
                    price = ?,
                    is_price_flexible = ?,
                    region_id = ?,
                    city_id = ?,
                    street = ?,
                    house_number = ?,
                    neighborhood = ?,
                    rooms = ?,
                    floor = ?,
                    total_floors = ?,
                    square_meters = ?,
                    entrance_date = ?,
                    immediate_entrance = ?,
                    parking_spaces = ?,
                    balconies = ?,
                    bathrooms = ?,
                    has_elevator = ?,
                    has_air_conditioning = ?,
                    has_storage = ?,
                    has_safe_room = ?,
                    has_bars = ?,
                    has_accessibility = ?,
                    has_furniture = ?,
                    has_renovation = ?,
                    has_pets = ?,
                    phone = ?,
                    hide_phone = ?,
                    allow_whatsapp = ?,
                    status = 'pending',
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
                  AND user_id = ?
                  AND is_deleted = 0
            ");

            $stmt->execute([
                $subcategoryId,
                $title,
                $form['description'] !== '' ? $form['description'] : null,
                $propertyType,
                $dealType,
                $price,
                $form['is_price_flexible'],
                $regionId,
                $cityId,
                $form['street'] !== '' ? $form['street'] : null,
                $form['house_number'] !== '' ? $form['house_number'] : null,
                $form['neighborhood'] !== '' ? $form['neighborhood'] : null,
                $rooms,
                $floor,
                $totalFloors,
                $squareMeters,
                $entranceDate,
                $form['immediate_entrance'],
                max(0, (int)$form['parking_spaces']),
                max(0, (int)$form['balconies']),
                max(0, (int)$form['bathrooms']),
                $form['has_elevator'],
                $form['has_air_conditioning'],
                $form['has_storage'],
                $form['has_safe_room'],
                $form['has_bars'],
                $form['has_accessibility'],
                $form['has_furniture'],
                $form['has_renovation'],
                $form['has_pets'],
                $form['phone'],
                $form['hide_phone'],
                $form['allow_whatsapp'],
                $adId,
                $userId
            ]);

            if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
                $uploadDir = __DIR__ . '/uploads/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $allowedMimeTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                $fileInfo = new finfo(FILEINFO_MIME_TYPE);

                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM realestate_images WHERE ad_id = ?");
                $countStmt->execute([$adId]);
                $existingCount = (int)$countStmt->fetchColumn();
                $uploadedCount = 0;

                foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
                    if ($existingCount + $uploadedCount >= 10) {
                        break;
                    }

                    $uploadError = $_FILES['images']['error'][$index] ?? UPLOAD_ERR_NO_FILE;

                    if ($uploadError !== UPLOAD_ERR_OK || !is_uploaded_file($tmpName)) {
                        continue;
                    }

                    $fileSize = (int)($_FILES['images']['size'][$index] ?? 0);

                    if ($fileSize <= 0 || $fileSize > 8 * 1024 * 1024) {
                        continue;
                    }

                    $mimeType = $fileInfo->file($tmpName);

                    if (!isset($allowedMimeTypes[$mimeType])) {
                        continue;
                    }

                    $newFileName = 'realestate_' . $adId . '_' . bin2hex(random_bytes(8)) . '.' . $allowedMimeTypes[$mimeType];
                    $destination = $uploadDir . $newFileName;

                    if (!move_uploaded_file($tmpName, $destination)) {
                        continue;
                    }

                    $imagePath = '/realestate/uploads/' . $newFileName;
                    $isMain = $existingCount === 0 && $uploadedCount === 0 ? 1 : 0;

                    $imageStmt = $pdo->prepare("
                        INSERT INTO realestate_images
                            (ad_id, image_name, image_path, sort_order, is_main)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $imageStmt->execute([
                        $adId,
                        $newFileName,
                        $imagePath,
                        $existingCount + $uploadedCount,
                        $isMain
                    ]);

                    $uploadedCount++;
                }
            }

            $pdo->commit();

            header('Location: /realestate/view.php?id=' . $adId . '&updated=1');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = 'אירעה שגיאה בעדכון המודעה: ' . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<link rel="stylesheet" href="/realestate/css/realestate.css">

<section class="realestate-form-page">

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


    <div class="realestate-form-header">
        <h1>עריכת מודעת נדל״ן</h1>
        <p>עדכן את פרטי הנכס ושמור את השינויים.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="realestate-alert error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($message !== ''): ?>
        <div class="realestate-alert success">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form
        method="post"
        action="/realestate/edit.php?id=<?= $adId ?>"
        enctype="multipart/form-data"
        class="realestate-form"
        autocomplete="off"
    >
        <input type="hidden" name="id" value="<?= $adId ?>">

        <div class="realestate-form-section">
            <h2>סוג המודעה</h2>

            <div class="realestate-form-grid">

                <div class="realestate-field">
                    <label for="subcategory_id">קטגוריה</label>

                    <select
                        name="subcategory_id"
                        id="subcategory_id"
                        required
                    >
                        <option value="">בחר קטגוריה</option>

                        <?php foreach ($subcategories as $subcategory): ?>
                            <option
                                value="<?= (int) $subcategory['id'] ?>"
                                <?= (int) $form['subcategory_id'] === (int) $subcategory['id'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($subcategory['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="realestate-field">
                    <label for="deal_type">סוג עסקה</label>

                    <select name="deal_type" id="deal_type" required>
                        <option value="sale" <?= $form['deal_type'] === 'sale' ? 'selected' : '' ?>>
                            מכירה
                        </option>

                        <option value="rent" <?= $form['deal_type'] === 'rent' ? 'selected' : '' ?>>
                            השכרה
                        </option>

                        <option value="roommates" <?= $form['deal_type'] === 'roommates' ? 'selected' : '' ?>>
                            שותפים
                        </option>

                        <option value="commercial" <?= $form['deal_type'] === 'commercial' ? 'selected' : '' ?>>
                            מסחרי
                        </option>
                    </select>
                </div>

                <div class="realestate-field">
                    <label for="property_type">סוג נכס</label>

                    <select
                        name="property_type"
                        id="property_type"
                        required
                    >
                        <option value="">בחר סוג נכס</option>

                        <?php
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

                        <?php foreach ($propertyTypes as $type): ?>
                            <option
                                value="<?= htmlspecialchars($type) ?>"
                                <?= $form['property_type'] === $type ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>
        </div>

        <div class="realestate-form-section">
            <h2>פרטי המודעה</h2>

            <div class="realestate-form-grid">

                <div class="realestate-field realestate-field-wide">
                    <label for="title">כותרת המודעה</label>

                    <input
                        type="text"
                        name="title"
                        id="title"
                        maxlength="150"
                        value="<?= htmlspecialchars($form['title']) ?>"
                        placeholder="לדוגמה: דירת 4 חדרים משופצת במרכז העיר"
                        required
                    >
                </div>

                <div class="realestate-field">
                    <label for="price">מחיר</label>

                    <input
                        type="number"
                        name="price"
                        id="price"
                        min="0"
                        step="1"
                        value="<?= htmlspecialchars($form['price']) ?>"
                        placeholder="₪"
                    >
                </div>

                <label class="realestate-checkbox">
                    <input
                        type="checkbox"
                        name="is_price_flexible"
                        value="1"
                        <?= $form['is_price_flexible'] ? 'checked' : '' ?>
                    >
                    <span>מחיר גמיש</span>
                </label>

                <div class="realestate-field realestate-field-full">
                    <label for="description">תיאור המודעה</label>

                    <textarea
                        name="description"
                        id="description"
                        rows="6"
                        placeholder="ספר על הנכס, סביבת המגורים, מצב הנכס ופרטים חשובים נוספים"
                    ><?= htmlspecialchars($form['description']) ?></textarea>
                </div>

            </div>
        </div>

        <div class="realestate-form-section">
            <h2>מיקום הנכס</h2>

            <div class="realestate-form-grid">

                <div class="realestate-field">
                    <label for="region_id">אזור</label>

                    <select name="region_id" id="region_id" required>
                        <option value="">בחר אזור</option>

                        <?php foreach ($regions as $region): ?>
                            <option
                                value="<?= (int) $region['id'] ?>"
                                <?= (int) $form['region_id'] === (int) $region['id'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($region['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="realestate-field">
                    <label for="city_id">עיר</label>

                    <select
                        name="city_id"
                        id="city_id"
                        data-selected="<?= (int) $form['city_id'] ?>"
                        <?= (int) $form['region_id'] <= 0 ? 'disabled' : '' ?>
                        required
                    >
                        <option value="">בחר קודם אזור</option>
                    </select>
                </div>

                <div class="realestate-field">
                    <label for="neighborhood">שכונה</label>

                    <input
                        type="text"
                        name="neighborhood"
                        id="neighborhood"
                        value="<?= htmlspecialchars($form['neighborhood']) ?>"
                    >
                </div>

                <div class="realestate-field">
                    <label for="street">רחוב</label>

                    <input
                        type="text"
                        name="street"
                        id="street"
                        value="<?= htmlspecialchars($form['street']) ?>"
                    >
                </div>

                <div class="realestate-field">
                    <label for="house_number">מספר בית</label>

                    <input
                        type="text"
                        name="house_number"
                        id="house_number"
                        maxlength="20"
                        value="<?= htmlspecialchars($form['house_number']) ?>"
                    >
                </div>

            </div>
        </div>

        <div class="realestate-form-section">
            <h2>פרטי הנכס</h2>

            <div class="realestate-form-grid">

                <div class="realestate-field">
                    <label for="rooms">מספר חדרים</label>

                    <input
                        type="number"
                        name="rooms"
                        id="rooms"
                        min="0"
                        max="30"
                        step="0.5"
                        value="<?= htmlspecialchars($form['rooms']) ?>"
                    >
                </div>

                <div class="realestate-field">
                    <label for="square_meters">שטח במ״ר</label>

                    <input
                        type="number"
                        name="square_meters"
                        id="square_meters"
                        min="0"
                        value="<?= htmlspecialchars($form['square_meters']) ?>"
                    >
                </div>

                <div class="realestate-field">
                    <label for="floor">קומה</label>

                    <input
                        type="number"
                        name="floor"
                        id="floor"
                        min="-5"
                        max="100"
                        value="<?= htmlspecialchars($form['floor']) ?>"
                    >
                </div>

                <div class="realestate-field">
                    <label for="total_floors">קומות בבניין</label>

                    <input
                        type="number"
                        name="total_floors"
                        id="total_floors"
                        min="0"
                        max="100"
                        value="<?= htmlspecialchars($form['total_floors']) ?>"
                    >
                </div>

                <div class="realestate-field">
                    <label class="realestate-checkbox">
    <input
        type="checkbox"
        name="parking_spaces"
        value="1"
   <?= (int) $form['parking_spaces'] > 0 ? 'checked' : '' ?>>
                        <span>חניה פרטית</span>
                    </label>
                </div>

                <div class="realestate-field">
                    <label for="balconies">מרפסות</label>

                    <input
                        type="number"
                        name="balconies"
                        id="balconies"
                        min="0"
                        max="20"
                        value="<?= (int) $form['balconies'] ?>"
                    >
                </div>

                <div class="realestate-field">
                    <label for="bathrooms">חדרי רחצה</label>

                    <input
                        type="number"
                        name="bathrooms"
                        id="bathrooms"
                        min="0"
                        max="20"
                        value="<?= (int) $form['bathrooms'] ?>"
                    >
                </div>

                <div class="realestate-field">
                    <label for="entrance_date">תאריך כניסה</label>

                    <input
                        type="date"
                        name="entrance_date"
                        id="entrance_date"
                        value="<?= htmlspecialchars($form['entrance_date']) ?>"
                    >
                </div>

                <label class="realestate-checkbox">
                    <input
                        type="checkbox"
                        name="immediate_entrance"
                        id="immediate_entrance"
                        value="1"
                        <?= $form['immediate_entrance'] ? 'checked' : '' ?>
                    >
                    <span>כניסה מיידית</span>
                </label>

            </div>
        </div>

        <div class="realestate-form-section">
            <h2>מאפייני הנכס</h2>

            <div class="realestate-features-grid">

                <?php
                $features = [
                    'has_elevator'         => 'מעלית',
                    'has_air_conditioning' => 'מיזוג אוויר',
                    'has_storage'          => 'מחסן',
                    'has_safe_room'        => 'ממ״ד',
                    'has_bars'             => 'סורגים',
                    'has_accessibility'    => 'גישה לנכים',
                    'has_furniture'        => 'ריהוט',
                    'has_renovation'       => 'משופץ',
                    'has_pets'             => 'מתאים לבעלי חיים'
                ];
                ?>

                <?php foreach ($features as $fieldName => $label): ?>
                    <label class="realestate-feature">
                        <input
                            type="checkbox"
                            name="<?= htmlspecialchars($fieldName) ?>"
                            value="1"
                            <?= $form[$fieldName] ? 'checked' : '' ?>
                        >
                        <span><?= htmlspecialchars($label) ?></span>
                    </label>
                <?php endforeach; ?>

            </div>
        </div>

        <div class="realestate-form-section">
            <h2>תמונות</h2>
<div class="realestate-field realestate-field-full">
    <label for="images">העלאת תמונות</label>

    <input
        type="file"
        name="images[]"
        id="images"
        accept="image/jpeg,image/png,image/webp"
        multiple
    >

    <small>
        ניתן להעלות עד 10 תמונות. התמונה הראשונה תהיה התמונה הראשית.
    </small>

    <div id="imagePreviewGrid" class="realestate-image-preview-grid"></div>
</div>


        </div>

        <div class="realestate-form-section">
            <h2>פרטי קשר</h2>

            <div class="realestate-form-grid">

                <div class="realestate-field">
                    <label for="phone">טלפון</label>

                    <input
                        type="tel"
                        name="phone"
                        id="phone"
                        maxlength="30"
                        value="<?= htmlspecialchars($form['phone']) ?>"
                        required
                    >
                </div>

                <label class="realestate-checkbox">
                    <input
                        type="checkbox"
                        name="hide_phone"
                        value="1"
                        <?= $form['hide_phone'] ? 'checked' : '' ?>
                    >
                    <span>הסתר מספר טלפון</span>
                </label>

                <label class="realestate-checkbox">
                    <input
                        type="checkbox"
                        name="allow_whatsapp"
                        value="1"
                        <?= $form['allow_whatsapp'] ? 'checked' : '' ?>
                    >
                    <span>אפשר פנייה בוואטסאפ</span>
                </label>

            </div>
        </div>

        <div class="realestate-form-actions">
            <button type="submit" class="realestate-submit-button">
                שמור שינויים
            </button>

            <a href="/realestate/view.php?id=<?= $adId ?>" class="realestate-cancel-button">
                ביטול
            </a>
        </div>

    </form>

</section>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const regionSelect = document.getElementById('region_id');
    const citySelect = document.getElementById('city_id');
    const immediateCheckbox = document.getElementById('immediate_entrance');
    const entranceDate = document.getElementById('entrance_date');


    const imagesInput = document.getElementById('images');
const imagePreviewGrid = document.getElementById('imagePreviewGrid');

let selectedImages = [];

function updateImagesInput() {
    const transfer = new DataTransfer();

    selectedImages.forEach(function (file) {
        transfer.items.add(file);
    });

    imagesInput.files = transfer.files;
}

function renderImagePreviews() {
    imagePreviewGrid.innerHTML = '';

    selectedImages.forEach(function (file, index) {
        const item = document.createElement('div');
        item.className = 'realestate-image-preview-item';

        const image = document.createElement('img');
        image.src = URL.createObjectURL(file);
        image.alt = '';

        image.addEventListener('load', function () {
            URL.revokeObjectURL(image.src);
        });

        item.appendChild(image);

        if (index === 0) {
            const mainBadge = document.createElement('span');
            mainBadge.className = 'realestate-image-preview-main';
            mainBadge.textContent = 'ראשית';
            item.appendChild(mainBadge);
        }

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'realestate-image-preview-remove';
        removeButton.innerHTML = '&times;';
        removeButton.setAttribute('aria-label', 'הסר תמונה');

        removeButton.addEventListener('click', function () {
            selectedImages.splice(index, 1);
            updateImagesInput();
            renderImagePreviews();
        });

        item.appendChild(removeButton);

        const fileName = document.createElement('div');
        fileName.className = 'realestate-image-preview-name';
        fileName.textContent = file.name;
        fileName.title = file.name;

        item.appendChild(fileName);
        imagePreviewGrid.appendChild(item);
    });
}

imagesInput.addEventListener('change', function () {
    const newFiles = Array.from(this.files);

    newFiles.forEach(function (file) {
        if (!file.type.startsWith('image/')) {
            return;
        }

        const alreadyExists = selectedImages.some(function (existingFile) {
            return (
                existingFile.name === file.name &&
                existingFile.size === file.size &&
                existingFile.lastModified === file.lastModified
            );
        });

        if (!alreadyExists && selectedImages.length < 10) {
            selectedImages.push(file);
        }
    });

    updateImagesInput();
    renderImagePreviews();

    if (newFiles.length + selectedImages.length > 10) {
        alert('ניתן לבחור עד 10 תמונות.');
    }
});


    async function loadCities(regionId, selectedCityId = '') {

        citySelect.innerHTML = '<option value="">טוען ערים...</option>';
        citySelect.disabled = true;

        if (!regionId) {
            citySelect.innerHTML = '<option value="">בחר קודם אזור</option>';
            return;
        }

        try {
            const response = await fetch(
                '/ajax/get_cities.php?region_id=' +
                encodeURIComponent(regionId)
            );

            if (!response.ok) {
                throw new Error('שגיאה בטעינת הערים');
            }

            const cities = await response.json();

            citySelect.innerHTML = '<option value="">בחר עיר</option>';

            cities.forEach(function (city) {
                const option = document.createElement('option');

                option.value = city.id;
                option.textContent = city.name;

                if (
                    selectedCityId &&
                    String(city.id) === String(selectedCityId)
                ) {
                    option.selected = true;
                }

                citySelect.appendChild(option);
            });

            citySelect.disabled = false;

        } catch (error) {
            citySelect.innerHTML =
                '<option value="">לא ניתן לטעון ערים</option>';
        }
    }

    regionSelect.addEventListener('change', function () {
        loadCities(this.value);
    });

    if (regionSelect.value) {
        loadCities(
            regionSelect.value,
            citySelect.dataset.selected || ''
        );
    }

    function updateEntranceDate() {
        if (immediateCheckbox.checked) {
            entranceDate.value = '';
            entranceDate.disabled = true;
        } else {
            entranceDate.disabled = false;
        }
    }

    immediateCheckbox.addEventListener('change', updateEntranceDate);
    updateEntranceDate();
});
</script>

<?php require_once '../includes/footer.php'; ?>