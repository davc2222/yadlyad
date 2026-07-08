<?php
session_start();

$admin_password = '123456';

if (isset($_POST['password'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = 'סיסמה שגויה';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

if (empty($_SESSION['admin_logged_in'])):
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>כניסת מנהל</title>

    <style>
        body {
            direction: rtl;
            font-family: Arial;
            background: #f4f4f4;
        }

        .login-box {
            width: 350px;
            margin: 120px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px #ccc;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 15px 0;
            box-sizing: border-box;
        }

        button {
            padding: 10px 30px;
            cursor: pointer;
        }
    </style>
</head>

<body>

<div class="login-box">

    <h2>כניסת מנהל</h2>

    <?php if (!empty($error)): ?>
        <p style="color:red">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <form method="post">
        <input type="password" name="password" placeholder="סיסמת מנהל" required>
        <button type="submit">כניסה</button>
    </form>

</div>

</body>
</html>

<?php
exit;
endif;

require_once '../includes/db.php';
require_once '../includes/admin_header.php';
require_once '../includes/admin_sidebar.php';
?>

<h1>מערכת ניהול</h1>

<hr>

<h2>ברוך הבא למערכת הניהול של יד ליד</h2>

<p>
    בחר את הפעולה הרצויה מהתפריט הימני.
</p>

<br>

<table style="width:500px">

    <tr>
        <td>סה״כ משתמשים</td>
        <td><strong>0</strong></td>
    </tr>

    <tr>
        <td>מודעות פעילות</td>
        <td><strong>0</strong></td>
    </tr>

    <tr>
        <td>מודעות ממתינות לאישור</td>
        <td><strong>0</strong></td>
    </tr>

    <tr>
        <td>פניות חדשות</td>
        <td><strong>0</strong></td>
    </tr>

</table>

<?php
require_once '../includes/admin_footer.php';
?>