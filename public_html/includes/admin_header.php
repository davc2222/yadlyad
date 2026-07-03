<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: /admin/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>מערכת ניהול - יד ליד</title>
    <link rel="stylesheet" href="/css/admin.css">
    <link rel="stylesheet" href="/css/modal.css">
</head>

<body>

    <div class="admin-layout">