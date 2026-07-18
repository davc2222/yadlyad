Library
/
forgot_password.php


<?php
require_once 'includes/db.php';
require_once 'includes/mail_templates.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$message = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = 'יש להזין כתובת אימייל';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'כתובת האימייל אינה תקינה';
    } else {

        $stmt = $pdo->prepare("
            SELECT id, email_verified
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $message = 'אם כתובת המייל קיימת במערכת, נשלח אליה קישור לאיפוס הסיסמה.';

        if ($user && (int) $user['email_verified'] === 1) {

            try {
                $resetToken = bin2hex(random_bytes(32));
                $resetExpiresAt = date('Y-m-d H:i:s', time() + 3600);

                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    UPDATE users
                    SET
                        reset_token = ?,
                        reset_expires_at = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $resetToken,
                    $resetExpiresAt,
                    (int) $user['id']
                ]);

                $mailResult = mailPasswordReset(
                    $pdo,
                    (int) $user['id'],
                    $resetToken
                );

                if (!$mailResult['success']) {
                    throw new RuntimeException($mailResult['error']);
                }

                $pdo->commit();

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log('Forgot password error: ' . $e->getMessage());

                $error = 'לא ניתן לשלוח כרגע את קישור האיפוס. נסה שוב מאוחר יותר.';
                $message = '';
            }
        }
    }
}

require_once 'includes/header.php';
?>

<link rel="stylesheet" href="/css/style.css">

<section class="auth-page">
    <div class="auth-box">

        <h1>שכחת סיסמה?</h1>

<?php if ($message): ?>

    <div class="reset-success-card">

        <div class="reset-success-icon">✉</div>

        <h2>המייל נשלח</h2>

        <p>
            אם כתובת המייל קיימת במערכת,
            נשלח אליה קישור לאיפוס הסיסמה.
        </p>

        <p class="reset-success-note">
            הקישור תקף למשך שעה אחת.
        </p>

        <a href="/login.php" class="reset-back-link">
            חזרה להתחברות
        </a>

    </div>

<?php else: ?>

    <p class="auth-subtitle">
        הזן את כתובת המייל שלך ונשלח אליך קישור לבחירת סיסמה חדשה.
    </p>

    <?php if ($error): ?>
        <div class="auth-error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

        <?php if (!$message): ?>
            <form method="post" class="auth-form" autocomplete="off">

                <div class="field">
                    <label>אימייל</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <button type="submit" class="auth-btn">
                    שלח קישור לאיפוס
                </button>

            </form>
        <?php endif; ?>

        <div class="auth-divider"></div>

     

    </div>
</section>

<?php require_once 'includes/footer.php'; ?>