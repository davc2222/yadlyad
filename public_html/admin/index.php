<?php
session_start();

$admin_password = '123456';

if (isset($_POST['password'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = 'סיסמה שגויה';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['admin_logged_in'])):
    ?>
    <!DOCTYPE html>
    <html lang="he" dir="rtl">

    <head>
        <meta charset="UTF-8">
        <title>כניסת מנהל</title>
    </head>

    <body>
        <h1>כניסת מנהל</h1>

        <?php if (!empty($error)): ?>
            <p style="color:red;">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <form method="post">
            <input type="password" name="password" placeholder="סיסמת מנהל" required>
            <button type="submit">כניסה</button>
        </form>
    </body>

    </html>
    <?php
    exit;
endif;
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>פאנל ניהול</title>
</head>

<body>
    <h1>פאנל ניהול ותחזוקה</h1>

    <ul>
    <li><a href="categories.php">ניהול קטגוריות</a></li>
    <li><a href="ads.php">ניהול מודעות</a></li>
    <li><a href="users.php">ניהול משתמשים</a></li>
</ul>

    <p><a href="?logout=1">יציאה</a></p>
</body>

</html>