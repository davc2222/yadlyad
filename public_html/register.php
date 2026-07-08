<?php
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        $error = 'יש למלא שם, אימייל וסיסמה';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'אימייל לא תקין';
    } elseif (strlen($password) < 4) {
        $error = 'הסיסמה חייבת להכיל לפחות 4 תווים';
    } else {

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'האימייל כבר קיים במערכת';
        } else {

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, phone)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $name,
                $email,
                $password_hash,
                $phone !== '' ? $phone : null
            ]);

            $_SESSION['user_id'] = (int) $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;

            header("Location: /my_ads.php");
            exit;
        }
    }
}

require_once 'includes/header.php';
?>

<h1>הרשמה</h1>

<?php if ($error): ?>
    <p style="color:red;">
        <?= htmlspecialchars($error) ?>
    </p>
<?php endif; ?>

<form method="post" style="max-width:400px;margin:30px auto;display:flex;flex-direction:column;gap:12px;">

    <label>שם</label>
    <input type="text" name="name" required>

    <label>אימייל</label>
    <input type="email" name="email" required>

    <label>סיסמה</label>
    <input type="password" name="password" required>

    <label>טלפון</label>
    <input type="text" name="phone">

    <button type="submit">הרשמה</button>

</form>

<?php require_once 'includes/footer.php'; ?>