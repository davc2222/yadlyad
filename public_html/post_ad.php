<?php
require_once 'includes/header.php';

$isLoggedIn = !empty($_SESSION['user_id']);
?>

<section class="post-ad-page">

    <div class="post-ad-box">

        <h1>באיזו קטגוריה תרצה לפרסם?</h1>
        <p>בחר קטגוריה והמשך לפרסום המודעה</p>

        <?php if (!$isLoggedIn): ?>
            <div class="post-login-note">
                כדי לפרסם מודעה יש להתחבר קודם.
            </div>
        <?php endif; ?>

        <div class="post-category-grid">

           <a href="/vehicle/add.php" class="post-category-card">
                <class="post-category-card">
                <div class="post-category-icon">🚗</div>
                <h2>רכב</h2>
                <span>מכוניות, אופנועים, מסחריות ומשאיות</span>
            </a>

            <a href="<?= $isLoggedIn ? '/realestate/add.php' : '/login.php?redirect=/realestate/add.php' ?>"
                class="post-category-card disabled">
                <div class="post-category-icon">🏠</div>
                <h2>נדל״ן</h2>
                <span>בקרוב</span>
            </a>

            <a href="<?= $isLoggedIn ? '/secondhand/add.php' : '/login.php?redirect=/secondhand/add.php' ?>"
                class="post-category-card disabled">
                <div class="post-category-icon">🏷️</div>
                <h2>יד שנייה</h2>
                <span>בקרוב</span>
            </a>

            <a href="<?= $isLoggedIn ? '/jobs/add.php' : '/login.php?redirect=/jobs/add.php' ?>"
                class="post-category-card disabled">
                <div class="post-category-icon">💼</div>
                <h2>דרושים</h2>
                <span>בקרוב</span>
            </a>

        </div>

    </div>

</section>

<?php require_once 'includes/footer.php'; ?>