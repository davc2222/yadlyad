<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>יד ליד - לוח מודעות יד שנייה</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/responsive.css">
    <link rel="stylesheet" href="/css/site_layout.css">
</head>

<body>

<header class="site-header">
    <div class="header-inner">

        <a href="/" class="logo">
            יד<span>ליד</span>
        </a>

        <nav class="main-nav">
            <a href="/">ראשי</a>
            <a href="/vehicle/index.php">רכבים</a>
            <a href="/vehicle/add.php">פרסום רכב</a>
            <a href="/search.php">חיפוש כללי</a>
            <a href="/contact.php">צור קשר</a>
        </nav>

        <div class="header-actions">
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a href="/profile.php" class="btn-login">איזור אישי</a>
                <a href="/logout.php" class="btn-post">התנתק</a>
            <?php else: ?>
                <a href="/login.php" class="btn-login">התחברות</a>
                <a href="/vehicle/add.php" class="btn-post">פרסם מודעה</a>
            <?php endif; ?>
        </div>

    </div>
</header>

<nav class="category-strip">
    <a href="/vehicle/index.php">🚗 רכב</a>
    <a href="/search.php?category=realestate">🏠 נדל״ן</a>
    <a href="/search.php?category=secondhand">🛋️ יד שנייה</a>
    <a href="/search.php?category=jobs">💼 דרושים</a>
</nav>

<main class="site-main">
