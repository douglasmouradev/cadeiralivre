<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OutboundEmailModel;
use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

final class MailService
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config)
    {
    }

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $altBody = ''): void
    {
        if (!empty($this->config['queue'])) {
            (new OutboundEmailModel())->enqueue($toEmail, $toName, $subject, $htmlBody);

            return;
        }
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $host = (string) ($this->config['smtp_host'] ?? '');
        $smtpUser = (string) ($this->config['smtp_user'] ?? '');
        if ($smtpUser === '') {
            $mail->isMail();
        } else {
            $mail->isSMTP();
            $mail->Host = $host !== '' ? $host : 'localhost';
            $mail->SMTPAuth = ($this->config['smtp_user'] ?? '') !== '';
            $mail->Username = (string) ($this->config['smtp_user'] ?? '');
            $mail->Password = (string) ($this->config['smtp_pass'] ?? '');
            $enc = (string) ($this->config['smtp_encryption'] ?? '');
            if ($enc === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($enc === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
            $mail->Port = (int) ($this->config['smtp_port'] ?? 587);
        }
        $mail->setFrom((string) $this->config['from_address'], (string) $this->config['from_name']);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $altBody !== '' ? $altBody : strip_tags($htmlBody);
        try {
            $mail->send();
        } catch (MailerException $e) {
            if (filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL)) {
                throw new \RuntimeException('Falha ao enviar e-mail: ' . $mail->ErrorInfo, 0, $e);
            }
            throw new \RuntimeException('Falha ao enviar e-mail.', 0, $e);
        }
    }
}
