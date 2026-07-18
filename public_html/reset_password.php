<?php
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

if ($token === '' || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
    $error = 'קישור האיפוס אינו תקין';
}

$user = null;

if ($error === '') {
    $stmt = $pdo->prepare("
        SELECT id, reset_expires_at
        FROM users
        WHERE reset_token = ?
        LIMIT 1
    ");

    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = 'קישור האיפוס אינו תקין או שכבר נעשה בו שימוש';
    } elseif (
        empty($user['reset_expires_at']) ||
        strtotime($user['reset_expires_at']) < time()
    ) {
        $error = 'קישור האיפוס פג תוקף';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {

    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (mb_strlen($password) < 4) {
        $error = 'הסיסמה חייבת להכיל לפחות 4 תווים';
    } elseif ($password !== $passwordConfirm) {
        $error = 'הסיסמאות אינן זהות';
    } else {

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE users
            SET
                password = ?,
                reset_token = NULL,
                reset_expires_at = NULL
            WHERE id = ?
        ");

        $stmt->execute([
            $passwordHash,
            (int) $user['id']
        ]);

        header('Location: /login.php?reset=1');
        exit;
    }
}

require_once 'includes/header.php';
?>

<link rel="stylesheet" href="/css/style.css">

<section class="auth-page">

    <div class="auth-box">

        <h1>בחירת סיסמה חדשה</h1>

        <?php if ($error): ?>
            <div class="auth-error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>

            <div class="auth-footer">
                <a href="/forgot_password.php">
                    בקש קישור חדש
                </a>
            </div>

        <?php else: ?>

            <p class="auth-subtitle">
                הזן סיסמה חדשה לחשבון שלך.
            </p>

            <form method="post" class="auth-form" autocomplete="off">

                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                <div class="field">
                    <label>סיסמה חדשה</label>

                    <input type="password" name="password" minlength="4" required>
                </div>

                <div class="field">
                    <label>אימות סיסמה</label>

                    <input type="password" name="password_confirm" minlength="4" required>
                </div>

                <button type="submit" class="auth-btn">
                    עדכן סיסמה
                </button>

            </form>

        <?php endif; ?>

    </div>

</section>

<?php require_once 'includes/footer.php'; ?>