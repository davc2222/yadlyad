<?php
require_once 'includes/header.php';
?>

<section class="post-ad-page">
    <div class="post-ad-box">
        <h1>מה תרצה לפרסם?</h1>
        <p>בחר קטגוריה ראשית והמשך לפרסום המודעה.</p>

        <div class="post-category-grid">
            <a href="/vehicle/add.php" class="post-category-card active">
                <span>🚗</span>
                <strong>רכב</strong>
                <small>מכוניות, אופנועים, מסחריות ומשאיות</small>
            </a>

            <a href="/post_ad.php?category=realestate" class="post-category-card disabled">
                <span>🏠</span>
                <strong>נדל״ן</strong>
                <small>דירות, בתים, משרדים ומחסנים</small>
            </a>

            <a href="/post_ad.php?category=secondhand" class="post-category-card disabled">
                <span>🛋️</span>
                <strong>יד שנייה</strong>
                <small>ריהוט, מוצרי חשמל וציוד לבית</small>
            </a>

            <a href="/post_ad.php?category=jobs" class="post-category-card disabled">
                <span>💼</span>
                <strong>דרושים</strong>
                <small>משרות, פרילנס ועבודות זמניות</small>
            </a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>