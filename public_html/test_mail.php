<?php

require_once __DIR__ . '/includes/send_mail.php';

$result = sendSiteMail(
    'davc22@gmail.com',
    'בדיקת מייל מאתר יד ליד',
    '
    <div dir="rtl" style="font-family:Arial,sans-serif">
        <h2>בדיקת שליחת מייל</h2>
        <p>אם קיבלת את ההודעה הזו, שליחת המיילים עובדת.</p>
    </div>
    ',
    'דוד'
);

echo '<pre>';
print_r($result);
echo '</pre>';