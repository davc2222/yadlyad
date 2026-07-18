<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

/**
 * שליחת מייל מכל מקום באתר.
 *
 * @param string $toEmail כתובת הנמען
 * @param string $subject  נושא
 * @param string $htmlBody תוכן HTML
 * @param string $toName   שם הנמען, לא חובה
 *
 * @return array{success: bool, error: string}
 */
function sendSiteMail(
    string $toEmail,
    string $subject,
    string $htmlBody,
    string $toName = ''
): array {
    $configFile = dirname(__DIR__) . '/config/mail_config.php';

    if (!is_file($configFile)) {
        return [
            'success' => false,
            'error' => 'קובץ הגדרות המייל לא נמצא',
        ];
    }

    $config = require $configFile;

    $requiredFields = [
        'host',
        'port',
        'username',
        'password',
        'from_email',
        'from_name',
    ];

    foreach ($requiredFields as $field) {
        if (!array_key_exists($field, $config)) {
            return [
                'success' => false,
                'error' => 'חסרה הגדרת מייל: ' . $field,
            ];
        }
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();

        $mail->Host = $config['host'];
        $mail->Port = (int) $config['port'];
        $mail->SMTPAuth = $config['username'] !== '';

        if ($mail->SMTPAuth) {
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
        }

        if (!empty($config['secure'])) {
            $mail->SMTPSecure = $config['secure'];
        }

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->setFrom(
            $config['from_email'],
            $config['from_name']
        );

        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = trim(
            html_entity_decode(
                strip_tags(
                    preg_replace('/<br\s*\/?>/i', "\n", $htmlBody)
                ),
                ENT_QUOTES,
                'UTF-8'
            )
        );

        $mail->send();

        return [
            'success' => true,
            'error' => '',
        ];

    } catch (Exception $e) {
        error_log('Mail error: ' . $mail->ErrorInfo);

        return [
            'success' => false,
            'error' => $mail->ErrorInfo,
        ];
    }
}