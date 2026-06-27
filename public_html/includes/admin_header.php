<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_logged_in'])) {
    header("Location: /admin/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>פאנל ניהול - יד ליד</title>

    <link rel="stylesheet" href="/css/admin.css">
</head>

<body>

<div class="admin-header">
    פאנל ניהול - יד ליד
</div>

<div class="admin-menu">
    <a href="/admin/index.php">ראשי</a>
    <a href="/admin/categories.php">קטגוריות</a>
    <a href="/admin/ads.php">מודעות</a>
    <a href="/admin/users.php">משתמשים</a>
    <a href="/admin/settings.php">הגדרות</a>
    <a href="/admin/index.php?logout=1">יציאה</a>
</div>

<div class="container">
    <div class="card"></div>