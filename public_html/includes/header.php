<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = !empty($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>יד ליד - לוח מודעות יד שנייה</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/responsive.css">    <link rel="stylesheet" href="/css/footer.css">
    <link rel="stylesheet" href="/css/post_ad.css">
    <link rel="stylesheet" href="/css/my_ads.css">
    <link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

    <header class="yl-header">

        <div class="yl-header-top">

            <a href="/" class="yl-logo">
                <span class="yl-logo-main">יד<span>ליד</span></span>
                <small>לוח מודעות יד שנייה</small>
            </a>

            <form class="yl-search" action="/search.php" method="get">
                <input type="text" name="q" placeholder="מה אתם מחפשים?">
                <button type="submit">חפש</button>
            </form>

          <div class="yl-actions">

    <?php if ($isLoggedIn): ?>
        
                <a href="/my_ads.php" class="yl-login-btn">
                    המודעות שלי
                </a>
        
                <a href="/logout.php" class="yl-login-btn">
                    התנתק
                </a>
        
            <?php else: ?>
        
                <a href="/login.php" class="yl-login-btn">
                    התחברות
                </a>
        
            <?php endif; ?>
        
            <a href="/post_ad.php" class="yl-post-btn">
                + פרסם מודעה
            </a>
        
        </div>
        </div>

        <nav class="yl-category-nav">

    <a href="/vehicle/index.php" class="yl-category-item">
        <span class="yl-category-icon car">
    <i class="fa-solid fa-car"></i>
</span>
        <span>רכב</span>
    </a>

    <a href="/search.php?category=realestate" class="yl-category-item">
       <span class="yl-category-icon home">
    <i class="fa-solid fa-house"></i>
</span>
        <span>נדל״ן</span>
    </a>

    <a href="/search.php?category=secondhand" class="yl-category-item">
    <span class="yl-category-icon second">
    <i class="fa-solid fa-tags"></i>
</span>
        <span>יד שנייה</span>
    </a>

    <a href="/search.php?category=jobs" class="yl-category-item">
     <span class="yl-category-icon jobs">
    <i class="fa-solid fa-briefcase"></i>
</span>
        <span>דרושים</span>
    </a>

</nav>
    

    </header>

    <main class="site-main">