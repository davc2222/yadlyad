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

    $stmt = $pdo->prepare("\n        SELECT id, name, email, email_verified, password\n        FROM users\n        WHERE email = ?\n        LIMIT 1\n    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        if ((int) $user['email_verified'] !== 1) {
            $error = 'יש לאמת את כתובת המייל לפני ההתחברות';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];

            $redirect = $_GET['redirect'] ?? '/my_ads.php';
            if (!is_string($redirect) || $redirect === '' || $redirect[0] !== '/' || str_starts_with($redirect, '//')) {
                $redirect = '/my_ads.php';
            }

            header('Location: ' . $redirect);
            exit;
        }
    } else {
        $error = 'אימייל או סיסמה אינם נכונים';
    }
}

require_once 'includes/header.php';
?>
<link rel="stylesheet" href="/css/style.css">
<section class="auth-page">
    <div class="auth-box">
        <h1>התחברות</h1>
        <p class="auth-subtitle">התחבר לחשבון שלך כדי לנהל את המודעות שפרסמת.</p>

        <?php if ($error): ?>
            <div class="auth-error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['verified']) && $_GET['verified'] === '1'): ?>
            <div class="auth-success">כתובת המייל אומתה בהצלחה. כעת ניתן להתחבר.</div>
        <?php endif; ?>

        <form method="post" class="auth-form" autocomplete="off">
            <div class="field">
                <label>אימייל</label>
                <input type="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="field">
                <label>סיסמה</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="auth-btn">התחבר</button>
        </form>

        <div class="auth-divider"></div>
        <div class="auth-footer">
            <span>אין לך חשבון?</span>
            <a href="/register.php">להרשמה לחץ כאן</a>
        </div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>