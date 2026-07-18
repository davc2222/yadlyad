<?php
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("
        SELECT id, name, email, password
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];

        $redirect = $_GET['redirect'] ?? '/my_ads.php';

        header("Location: " . $redirect);
        exit;
    }

    $error = 'אימייל או סיסמה אינם נכונים';
}

require_once 'includes/header.php';
?>

<link rel="stylesheet" href="/css/style.css">

<section class="auth-page">

    <div class="auth-box">

        <h1>התחברות</h1>

        <?php if (isset($_GET['verified']) && $_GET['verified'] === '1'): ?>
            <div class="auth-success">
                ✅ כתובת המייל אומתה בהצלחה. עכשיו אפשר להתחבר.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['reset']) && $_GET['reset'] === '1'): ?>
            <div class="auth-success">
                ✅ הסיסמה עודכנה בהצלחה. עכשיו אפשר להתחבר.
            </div>
        <?php endif; ?>

        <p class="auth-subtitle">
            התחבר לחשבון שלך כדי לנהל את המודעות שפרסמת.
        </p>

        <?php if ($error): ?>
            <div class="auth-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="auth-form" autocomplete="off">

            <div class="field">
                <label>אימייל</label>
                <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
            </div>

            <div class="field">
                <label>סיסמה</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" class="auth-btn">
                התחבר
            </button>

        </form>

        <div class="auth-divider"></div>

        <div class="auth-bottom-links">

            <a href="/forgot_password.php" class="auth-forgot-link">
                שכחת סיסמה?
            </a>

            <div class="auth-register-line">
                <span>אין לך חשבון?</span>

                <a href="/register.php">
                    להרשמה לחץ כאן
                </a>
            </div>

        </div>

    </div>

</section>

<?php require_once 'includes/footer.php'; ?>