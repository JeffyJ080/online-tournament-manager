<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../mate_vendor/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../mate_vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../mate_vendor/PHPMailer/src/SMTP.php';

class Mailer
{
    public static function send(string $to, string $subject, string $body, ?string $toName = null): bool
    {
        $config = require __DIR__ . '/../../mate_config/mail.php';

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->Port = $config['port'];

            if (($config['encryption'] ?? '') === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            if (($config['encryption'] ?? '') === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }

            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($to, $toName ?? '');

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = self::wrapHtml($subject, $body);
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));

            return $mail->send();
        } catch (Exception $e) {
            return false;
        }
    }

    public static function sendToAdmin(string $subject, string $body): bool
    {
        $config = require __DIR__ . '/../../mate_config/mail.php';

        return self::send(
            $config['admin_email'],
            $subject,
            $body,
            'Mate Admin'
        );
    }

    private static function wrapHtml(string $title, string $content): string
    {
        return '
            <div style="margin:0;padding:0;background:#080D1A;color:#D8E2F0;font-family:Arial,sans-serif;">
                <div style="max-width:640px;margin:0 auto;padding:32px 20px;">
                    <div style="background:#0E1526;border:1px solid rgba(216,226,240,0.12);border-radius:18px;padding:28px;">
                        <h1 style="margin:0 0 16px;color:#F0B42A;font-size:28px;">' . htmlspecialchars($title) . '</h1>
                        <div style="font-size:16px;line-height:1.6;color:#D8E2F0;">
                            ' . $content . '
                        </div>
                        <hr style="border:none;border-top:1px solid rgba(216,226,240,0.12);margin:28px 0;">
                        <p style="margin:0;color:#7D8EA8;font-size:13px;">
                            Mate Tournaments
                        </p>
                    </div>
                </div>
            </div>
        ';
    }
}