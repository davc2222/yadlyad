<?php
require_once 'includes/db.php';

$success = false;
$message = '';
$token = trim($_GET['token'] ?? '');

if ($token === '' || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
    $message = 'קישור האימות אינו תקין.';
} else {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("\n            SELECT id, email_verified, verification_expires_at\n            FROM users\n            WHERE verification_token = ?\n            LIMIT 1\n            FOR UPDATE\n        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $message = 'קישור האימות אינו תקין או שכבר נעשה בו שימוש.';
        } elseif ((int) $user['email_verified'] === 1) {
            $message = 'כתובת המייל כבר אומתה.';
            $success = true;
        } elseif (empty($user['verification_expires_at']) || strtotime($user['verification_expires_at']) < time()) {
            $message = 'קישור האימות פג תוקף.';
        } else {
            $stmt = $pdo->prepare("\n                UPDATE users\n                SET email_verified = 1, verification_token = NULL, verification_expires_at = NULL\n                WHERE id = ?\n            ");
            $stmt->execute([(int) $user['id']]);
            $success = true;
            $message = 'כתובת המייל אומתה בהצלחה.';
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Email verification error: ' . $e->getMessage());
        $message = 'אירעה שגיאה באימות כתובת המייל.';
    }
}

if ($success) {
    header('Location: /login.php?verified=1');
    exit;
}

require_once 'includes/header.php';
?>
<link rel="stylesheet" href="/css/style.css">
<section class="auth-page">
    <div class="auth-box">
        <h1>אימות כתובת מייל</h1>
        <div class="auth-error">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="auth-footer"><a href="/register.php">חזרה להרשמה</a></div>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>