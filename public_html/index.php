<?php
require_once 'includes/header.php';
?>

<section class="home-hero">
    <h1>מה מחפשים היום?</h1>
    <p>בחר קטגוריה והמשך לחיפוש מותאם.</p>

    <div class="home-actions">
       <a href="/post_ad.php" class="home-main-btn">פרסם מודעה</a>
    </div>

    <div class="home-category-grid">

        <a href="/vehicle/index.php" class="home-category-card">
            <div class="cat-icon">🚗</div>
            <h2>רכב</h2>
            <span>מכוניות, אופנועים, מסחריות ומשאיות</span>
        </a>

        <a href="/search.php?category=realestate" class="home-category-card">
            <div class="cat-icon">🏠</div>
            <h2>נדל״ן</h2>
            <span>דירות, בתים, משרדים ונכסים</span>
        </a>

        <a href="/search.php?category=secondhand" class="home-category-card">
            <div class="cat-icon">🛋️</div>
            <h2>יד שנייה</h2>
            <span>ריהוט, חשמל, מחשבים וסלולר</span>
        </a>

        <a href="/search.php?category=jobs" class="home-category-card">
            <div class="cat-icon">💼</div>
            <h2>דרושים</h2>
            <span>משרות, הייטק, שירות ומכירות</span>
        </a>

    </div>
</section>

<?php require_once 'includes/footer.php'; ?>