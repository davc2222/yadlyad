<?php
// ===== FILE: config/mail_config.php =====

$host = $_SERVER['HTTP_HOST'] ?? '';
$serverName = $_SERVER['SERVER_NAME'] ?? '';
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

$isLocal =
    in_array($remoteAddr, ['127.0.0.1', '::1'], true) ||
    stripos($host, 'localhost') !== false ||
    stripos($serverName, 'localhost') !== false ||
    stripos($host, '127.0.0.1') !== false ||
    stripos($serverName, '127.0.0.1') !== false ||
    stripos($host, 'yadlyad.local') !== false ||
    stripos($serverName, 'yadlyad.local') !== false ||
    stripos($host, 'wsl.localhost') !== false ||
    stripos($serverName, 'wsl.localhost') !== false ||
    preg_match(
        '/^(192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/',
        $remoteAddr
    );

// לוקאל — Gmail
if ($isLocal) {
    return [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'davc22@gmail.com',
        'password' => 'gutg mpls btsq putx',
        'secure' => 'tls',
        'from_email' => 'davc22@gmail.com',
        'from_name' => 'יד ליד',
    ];
}

// GoDaddy — Production
return [
    'host' => 'localhost',
    'port' => 25,
    'username' => 'mail@הדומיין-שלך.co.il',
    'password' => 'סיסמת-המייל-בשרת',
    'secure' => false,
    'from_email' => 'mail@הדומיין-שלך.co.il',
    'from_name' => 'יד ליד',
];