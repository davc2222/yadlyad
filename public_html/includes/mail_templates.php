<?php
// ===== FILE: includes/mail_templates.php =====

require_once __DIR__ . '/send_mail.php';

function siteBaseUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'yadlyad.local';
    return $scheme . '://' . $host;
}

function siteMailLayout(string $title, string $content, string $buttonText = '', string $buttonUrl = ''): string
{
    $button = '';

    if ($buttonText !== '' && $buttonUrl !== '') {
        $button = '<p style="margin:28px 0;text-align:center;"><a href="' . htmlspecialchars($buttonUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:13px 24px;color:#fff;background:#1769d2;border-radius:9px;text-decoration:none;font-weight:bold;">' . htmlspecialchars($buttonText, ENT_QUOTES, 'UTF-8') . '</a></p>';
    }

    return '<div dir="rtl" style="max-width:620px;margin:0 auto;padding:28px;font-family:Arial,sans-serif;color:#26354a;background:#f7f9fc;border:1px solid #dfe6ef;border-radius:14px;">
        <div style="margin-bottom:22px;padding-bottom:18px;border-bottom:1px solid #dfe6ef;text-align:center;">
            <div style="color:#1769d2;font-size:28px;font-weight:800;">יד ליד</div>
            <div style="margin-top:5px;color:#718096;font-size:13px;">לוח המודעות שלך</div>
        </div>
        <h2 style="margin:0 0 18px;color:#185fca;font-size:22px;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>
        <div style="color:#334155;font-size:15px;line-height:1.75;">' . $content . '</div>
        ' . $button . '
        <div style="margin-top:26px;padding-top:18px;border-top:1px solid #dfe6ef;color:#7b8798;font-size:12px;text-align:center;">הודעה זו נשלחה אוטומטית מאתר יד ליד.</div>
    </div>';
}

function getMailUser(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

function mailAdReceived(PDO $pdo, int $userId, int $adId, string $adType, string $adTitle = ''): array
{
    $user = getMailUser($pdo, $userId);

    if (!$user || empty($user['email'])) {
        return ['success' => false, 'error' => 'לא נמצאה כתובת מייל למשתמש'];
    }

    $name = htmlspecialchars($user['name'] ?: 'משתמש', ENT_QUOTES, 'UTF-8');
    $safeTitle = htmlspecialchars($adTitle !== '' ? $adTitle : 'המודעה שלך', ENT_QUOTES, 'UTF-8');

    $content = '<p>שלום <strong>' . $name . '</strong>,</p>
        <p>המודעה <strong>' . $safeTitle . '</strong> התקבלה בהצלחה ונמצאת כעת בבדיקת מנהל האתר.</p>
        <p>לאחר שהמודעה תאושר, יישלח אליך מייל נוסף.</p>
        <p style="color:#64748b;">מספר מודעה: ' . $adId . '</p>';

    return sendSiteMail(
        $user['email'],
        'מודעתך התקבלה באתר יד ליד',
        siteMailLayout('המודעה התקבלה וממתינה לאישור', $content),
        $user['name'] ?? ''
    );
}

function mailAdApproved(PDO $pdo, int $userId, int $adId, string $adType, string $adTitle = ''): array
{
    $user = getMailUser($pdo, $userId);

    if (!$user || empty($user['email'])) {
        return ['success' => false, 'error' => 'לא נמצאה כתובת מייל למשתמש'];
    }

    $name = htmlspecialchars($user['name'] ?: 'משתמש', ENT_QUOTES, 'UTF-8');
    $safeTitle = htmlspecialchars($adTitle !== '' ? $adTitle : 'המודעה שלך', ENT_QUOTES, 'UTF-8');
    $path = $adType === 'vehicle' ? '/vehicle/view.php?id=' . $adId : '/secondhand/view.php?id=' . $adId;
    $viewUrl = siteBaseUrl() . $path;

    $content = '<p>שלום <strong>' . $name . '</strong>,</p>
        <p>המודעה <strong>' . $safeTitle . '</strong> אושרה וכעת מוצגת באתר.</p>
        <p style="color:#64748b;">מספר מודעה: ' . $adId . '</p>';

    return sendSiteMail(
        $user['email'],
        'מודעתך אושרה באתר יד ליד',
        siteMailLayout('המודעה אושרה', $content, 'צפייה במודעה', $viewUrl),
        $user['name'] ?? ''
    );
}

function mailAdRejected(PDO $pdo, int $userId, int $adId, string $adTitle = '', string $reason = ''): array
{
    $user = getMailUser($pdo, $userId);

    if (!$user || empty($user['email'])) {
        return ['success' => false, 'error' => 'לא נמצאה כתובת מייל למשתמש'];
    }

    $name = htmlspecialchars($user['name'] ?: 'משתמש', ENT_QUOTES, 'UTF-8');
    $safeTitle = htmlspecialchars($adTitle !== '' ? $adTitle : 'המודעה שלך', ENT_QUOTES, 'UTF-8');
    $reasonBlock = '';

    if ($reason !== '') {
        $reasonBlock = '<div style="margin:18px 0;padding:14px;background:#fff4f4;border:1px solid #f0caca;border-radius:8px;color:#8a2f2f;"><strong>סיבת הדחייה:</strong><br>' . nl2br(htmlspecialchars($reason, ENT_QUOTES, 'UTF-8')) . '</div>';
    }

    $content = '<p>שלום <strong>' . $name . '</strong>,</p>
        <p>המודעה <strong>' . $safeTitle . '</strong> לא אושרה לפרסום.</p>
        ' . $reasonBlock . '
        <p style="color:#64748b;">מספר מודעה: ' . $adId . '</p>';

    return sendSiteMail(
        $user['email'],
        'עדכון לגבי המודעה באתר יד ליד',
        siteMailLayout('המודעה לא אושרה', $content),
        $user['name'] ?? ''
    );
}

function mailPasswordReset(PDO $pdo, int $userId, string $token): array
{
    $user = getMailUser($pdo, $userId);

    if (!$user || empty($user['email'])) {
        return ['success' => false, 'error' => 'לא נמצאה כתובת מייל'];
    }

    $url = siteBaseUrl() . '/reset_password.php?token=' . urlencode($token);

    $content = '
        <p>שלום <strong>' . htmlspecialchars($user['name']) . '</strong>,</p>

        <p>התקבלה בקשה לאיפוס הסיסמה שלך.</p>

        <p>אם זו הייתה בקשתך לחץ על הכפתור:</p>
    ';

    return sendSiteMail(
        $user['email'],
        'איפוס סיסמה',
        siteMailLayout(
            'איפוס סיסמה',
            $content,
            'בחר סיסמה חדשה',
            $url
        ),
        $user['name']
    );
}