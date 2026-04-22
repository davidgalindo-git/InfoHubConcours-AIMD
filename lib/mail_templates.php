<?php
declare(strict_types=1);

function mail_tpl_wrap(string $title, string $intro, string $contentHtml): string
{
  return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
    . '<style>body{margin:0;padding:0;background:#f3f6fb;font-family:Arial,sans-serif;color:#112} .card{max-width:640px;margin:24px auto;background:#fff;border-radius:14px;overflow:hidden;border:1px solid #e5ecf5}'
    . '.hd{background:#0d1023;color:#d9e6ff;padding:16px 20px;font-weight:700} .bd{padding:20px} .btn{display:inline-block;background:#0b78ff;color:#fff;text-decoration:none;padding:12px 16px;border-radius:10px;font-weight:700}'
    . '.muted{color:#5d6b84;font-size:13px} @media(max-width:640px){.card{margin:10px;border-radius:10px}}</style></head><body>'
    . '<div class="card"><div class="hd">' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div><div class="bd">'
    . '<p>' . $intro . '</p>' . $contentHtml
    . '<p class="muted">Cet e-mail est envoyé automatiquement par InfoHub.</p>'
    . '</div></div></body></html>';
}

function mail_tpl_verify_account(string $fullName, string $verifyUrl): array
{
  $subject = 'Confirme ton compte InfoHub';
  $intro = 'Bonjour <strong>' . htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong>, merci pour ton inscription.';
  $html = mail_tpl_wrap(
    'Confirmation de compte',
    $intro,
    '<p>Clique sur le bouton ci-dessous pour activer ton compte :</p>'
    . '<p><a class="btn" href="' . htmlspecialchars($verifyUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">Confirmer mon compte</a></p>'
    . '<p class="muted">Si le bouton ne fonctionne pas, copie ce lien :<br>'
    . htmlspecialchars($verifyUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
  );
  $text = "Bonjour $fullName,\nConfirme ton compte avec ce lien : $verifyUrl";
  return ['subject' => $subject, 'html' => $html, 'text' => $text];
}

function mail_tpl_signin_alert(string $fullName, string $when, string $ip): array
{
  $subject = 'Alerte de connexion InfoHub';
  $intro = 'Bonjour <strong>' . htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong>, une connexion a été détectée sur ton compte.';
  $html = mail_tpl_wrap(
    'Nouvelle connexion',
    $intro,
    '<p><strong>Date/heure :</strong> ' . htmlspecialchars($when, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '<br>'
    . '<strong>IP :</strong> ' . htmlspecialchars($ip, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
    . '<p>Si ce n’était pas toi, change immédiatement ton mot de passe.</p>'
  );
  $text = "Connexion détectée.\nDate: $when\nIP: $ip";
  return ['subject' => $subject, 'html' => $html, 'text' => $text];
}

function mail_tpl_password_reset(string $fullName, string $resetUrl): array
{
  $subject = 'Réinitialisation du mot de passe InfoHub';
  $intro = 'Bonjour <strong>' . htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong>, une demande de réinitialisation a été reçue.';
  $html = mail_tpl_wrap(
    'Réinitialisation mot de passe',
    $intro,
    '<p>Utilise ce lien pour choisir un nouveau mot de passe :</p>'
    . '<p><a class="btn" href="' . htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">Réinitialiser mon mot de passe</a></p>'
    . '<p class="muted">Si tu n’es pas à l’origine de cette demande, ignore cet e-mail.</p>'
  );
  $text = "Réinitialisation demandée: $resetUrl";
  return ['subject' => $subject, 'html' => $html, 'text' => $text];
}

function mail_tpl_password_changed(string $fullName, string $when): array
{
  $subject = 'Mot de passe modifié - InfoHub';
  $intro = 'Bonjour <strong>' . htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong>, ton mot de passe a été modifié.';
  $html = mail_tpl_wrap(
    'Confirmation de changement',
    $intro,
    '<p><strong>Date/heure :</strong> ' . htmlspecialchars($when, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
    . '<p>Si tu n’es pas à l’origine de cette action, contacte un administrateur.</p>'
  );
  $text = "Ton mot de passe a été modifié le $when";
  return ['subject' => $subject, 'html' => $html, 'text' => $text];
}
