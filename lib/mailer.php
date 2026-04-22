<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * Dernière erreur rencontrée par le mailer.
 */
function mailer_set_last_error(string $message): void
{
  $GLOBALS['mailer_last_error'] = $message;
}

function mailer_last_error(): string
{
  return (string)($GLOBALS['mailer_last_error'] ?? '');
}

function mailer_configuration_issue(): string
{
  if (SMTP_HOST === '' || SMTP_USERNAME === '' || SMTP_PASSWORD === '' || MAIL_FROM_ADDRESS === '') {
    return 'Configuration SMTP incomplète. Vérifie le fichier .env (SMTP_HOST, SMTP_USERNAME, SMTP_PASSWORD, MAIL_FROM_ADDRESS).';
  }
  $smtpPassword = trim((string)SMTP_PASSWORD);
  if (stripos($smtpPassword, 'ton_app_password') !== false) {
    return 'Le mot de passe SMTP est encore un placeholder (TON_APP_PASSWORD...).';
  }
  $autoload = __DIR__ . '/../vendor/autoload.php';
  if (is_file($autoload)) {
    require_once $autoload;
  }
  if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
    return 'PHPMailer est introuvable. Lance `composer install` dans le projet.';
  }
  return '';
}

/**
 * Service d'envoi d'e-mails transactionnels.
 *
 * Utilise PHPMailer si disponible (vendor/autoload.php).
 * Retourne false si la config SMTP est absente/invalide.
 */
function mailer_send(string $toEmail, string $toName, string $subject, string $html, string $text = ''): bool
{
  mailer_set_last_error('');
  $toEmail = trim($toEmail);
  if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
    mailer_set_last_error('Adresse destinataire invalide.');
    return false;
  }
  $configIssue = mailer_configuration_issue();
  if ($configIssue !== '') {
    mailer_set_last_error($configIssue);
    return false;
  }

  $autoload = __DIR__ . '/../vendor/autoload.php';
  if (is_file($autoload)) {
    require_once $autoload;
  }

  if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
    mailer_set_last_error('PHPMailer est introuvable. Lance `composer install` dans le projet.');
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
    $message = $e->getMessage();
    mailer_set_last_error('Erreur SMTP: ' . $message);
    error_log('Mailer error: ' . $message);
    return false;
  }
}
