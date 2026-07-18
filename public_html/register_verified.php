<?php
require_once 'includes/db.php';
require_once 'includes/send_mail.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$message = '';
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        $error = 'יש למלא שם, אימייל וסיסמה';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'אימייל לא תקין';
    } elseif (mb_strlen($password) < 4) {
        $error = 'הסיסמה חייבת להכיל לפחות 4 תווים';
    } else {
        $stmt = $pdo->prepare("SELECT id, email_verified FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            $error = (int) $existingUser['email_verified'] === 1
                ? 'האימייל כבר קיים במערכת'
                : 'האימייל כבר נרשם אך עדיין לא אומת';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $verificationToken = bin2hex(random_bytes(32));
            $verificationExpiresAt = date('Y-m-d H:i:s', time() + 86400);

            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("\n                    INSERT INTO users\n                    (name, email, email_verified, verification_token, verification_expires_at, password, phone)\n                    VALUES (?, ?, 0, ?, ?, ?, ?)\n                ");

                $stmt->execute([
                    $name,
                    $email,
                    $verificationToken,
                    $verificationExpiresAt,
                    $passwordHash,
                    $phone !== '' ? $phone : null
                ]);

                $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
                    ? 'https'
                    : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'yadlyad.local';
                $verificationUrl = $scheme . '://' . $host . '/verify_email.php?token=' . urlencode($verificationToken);

                $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                $safeUrl = htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8');

                $htmlBody = '
                    <div dir="rtl" style="max-width:600px;margin:0 auto;padding:28px;font-family:Arial,sans-serif;color:#26354a;background:#f7f9fc;border:1px solid #dfe6ef;border-radius:14px;">
                        <h2 style="margin-top:0;color:#185fca;">ברוכים הבאים ליד ליד</h2>
                        <p>שלום ' . $safeName . ',</p>
                        <p>כדי להשלים את ההרשמה, יש לאמת את כתובת המייל שלך.</p>
                        <p style="margin:28px 0;text-align:center;">
                            <a href="' . $safeUrl . '" style="display:inline-block;padding:13px 24px;color:#fff;background:#1769d2;border-radius:9px;text-decoration:none;font-weight:bold;">אימות כתובת המייל</a>
                        </p>
                        <p style="font-size:13px;color:#68758a;">הקישור תקף למשך 24 שעות.</p>
                        <p style="font-size:12px;color:#7c8798;word-break:break-all;">אם הכפתור אינו עובד, אפשר להעתיק את הקישור הבא:<br>' . $safeUrl . '</p>
                    </div>';

                $mailResult = sendSiteMail($email, 'אימות הרשמה לאתר יד ליד', $htmlBody, $name);

                if (!$mailResult['success']) {
                    throw new RuntimeException('שליחת מייל האימות נכשלה: ' . $mailResult['error']);
                }

                $pdo->commit();
                $message = 'ההרשמה נקלטה. נשלח אליך מייל עם קישור לאימות החשבון.';
                $name = $email = $phone = '';

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Registration error: ' . $e->getMessage());
                $error = 'לא ניתן להשלים את ההרשמה כרגע. נסה שוב מאוחר יותר.';
            }
        }
    }
}

require_once 'includes/header.php';
?>
<link rel="stylesheet" href="/css/style.css">
<section class="auth-page">
    <div class="auth-box">
        <h1>הרשמה</h1>
        <p class="auth-subtitle">פתח חשבון כדי לפרסם ולנהל מודעות.</p>

        <?php if ($error): ?>
            <div class="auth-error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="auth-success">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!$message): ?>
            <form method="post" class="auth-form" autocomplete="off">
                <div class="field">
                    <label>שם</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="field">
                    <label>אימייל</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="field">
                    <label>סיסמה</label>
                    <input type="password" name="password" minlength="4" required>
                </div>
                <div class="field">
                    <label>טלפון</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <button type="submit" class="auth-btn">הרשמה</button>
            </form>
        <?php else: ?>
            <div class="auth-footer"><a href="/login.php">מעבר להתחברות</a></div>
        <?php endif; ?>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>