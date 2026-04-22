<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * Service d'envoi d'e-mails transactionnels.
 *
 * Utilise PHPMailer si disponible (vendor/autoload.php).
 * Retourne false silencieusement si la config SMTP est absente/invalide.
 */
function mailer_send(string $toEmail, string $toName, string $subject, string $html, string $text = ''): bool
{
  $toEmail = trim($toEmail);
  if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
    return false;
  }
  if (SMTP_HOST === '' || SMTP_USERNAME === '' || SMTP_PASSWORD === '' || MAIL_FROM_ADDRESS === '') {
    return false;
  }

  $autoload = __DIR__ . '/../vendor/autoload.php';
  if (is_file($autoload)) {
    require_once $autoload;
  }

  if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
    return false;
  }

  try {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->Port = SMTP_PORT;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $enc = strtolower(trim(SMTP_ENCRYPTION));
    if ($enc === 'ssl') {
      $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    } else {
      $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    }
    $mail->CharSet = 'UTF-8';
    $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
    $mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $html;
    $mail->AltBody = $text !== '' ? $text : strip_tags($html);
    return $mail->send();
  } catch (Throwable $e) {
    error_log('Mailer error: ' . $e->getMessage());
    return false;
  }
}
