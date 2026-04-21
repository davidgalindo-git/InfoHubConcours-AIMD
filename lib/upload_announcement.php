<?php
declare(strict_types=1);

/**
 * Enregistre une pièce jointe annonce (JPG, PNG ou PDF), avec contrôles MIME / contenu.
 * Si l’extension GD est disponible, les images sont ré-encodées (meilleure défense contre fichiers polymorphes).
 * Sans GD : copie stricte après MIME + getimagesize + contrôle du type IMAGETYPE_* sur le fichier final.
 *
 * @return array{ok:bool, relative_path?:string|null, error?:string}
 */
function announcement_process_upload(?array $file): array
{
  if ($file === null || !isset($file['error']) || (int)$file['error'] === UPLOAD_ERR_NO_FILE) {
    return ['ok' => true, 'relative_path' => null];
  }
  if ((int)$file['error'] !== UPLOAD_ERR_OK) {
    return ['ok' => false, 'error' => 'Erreur lors de l’envoi du fichier.'];
  }
  $size = (int)($file['size'] ?? 0);
  if ($size <= 0 || $size > 5 * 1024 * 1024) {
    return ['ok' => false, 'error' => 'Fichier trop volumineux (maximum 5 Mo).'];
  }
  $tmp = (string)($file['tmp_name'] ?? '');
  if ($tmp === '' || !is_uploaded_file($tmp)) {
    return ['ok' => false, 'error' => 'Fichier invalide.'];
  }

  $origName = (string)($file['name'] ?? '');
  $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'], true)) {
    return ['ok' => false, 'error' => 'Extension non autorisée (JPG, PNG ou PDF uniquement).'];
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($tmp) ?: '';
  $allowedMime = [
    'jpg' => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png' => ['image/png'],
    'pdf' => ['application/pdf'],
  ];
  if (!in_array($mime, $allowedMime[$ext] ?? [], true)) {
    return ['ok' => false, 'error' => 'Le type du fichier ne correspond pas à une image JPG/PNG ou à un PDF.'];
  }

  if ($ext === 'pdf') {
    $head = @file_get_contents($tmp, false, null, 0, 5);
    if ($head === false || strncmp($head, '%PDF-', 5) !== 0) {
      return ['ok' => false, 'error' => 'Fichier PDF invalide ou corrompu.'];
    }
    $destDir = dirname(__DIR__) . '/uploads/announcements';
    if (!is_dir($destDir) && !@mkdir($destDir, 0755, true)) {
      return ['ok' => false, 'error' => 'Impossible de créer le dossier de stockage.'];
    }
    $destName = bin2hex(random_bytes(16)) . '.pdf';
    $destAbs = $destDir . '/' . $destName;
    if (!@move_uploaded_file($tmp, $destAbs)) {
      return ['ok' => false, 'error' => 'Échec de l’enregistrement du PDF.'];
    }
    return ['ok' => true, 'relative_path' => 'uploads/announcements/' . $destName];
  }

  $info = @getimagesize($tmp);
  if ($info === false) {
    return ['ok' => false, 'error' => 'Image illisible ou corrompue.'];
  }
  [$w, $h] = $info;
  if ($w < 1 || $h < 1 || $w > 8000 || $h > 8000) {
    return ['ok' => false, 'error' => 'Dimensions d’image non acceptées.'];
  }

  $typeTag = (int)($info[2] ?? 0);
  if ($ext === 'png' && $typeTag !== IMAGETYPE_PNG) {
    return ['ok' => false, 'error' => 'Le fichier ne correspond pas à une image PNG.'];
  }
  if (in_array($ext, ['jpg', 'jpeg'], true) && $typeTag !== IMAGETYPE_JPEG) {
    return ['ok' => false, 'error' => 'Le fichier ne correspond pas à une image JPEG.'];
  }

  $destDir = dirname(__DIR__) . '/uploads/announcements';
  if (!is_dir($destDir) && !@mkdir($destDir, 0755, true)) {
    return ['ok' => false, 'error' => 'Impossible de créer le dossier de stockage.'];
  }

  $outExt = ($ext === 'png') ? 'png' : 'jpg';
  $destName = bin2hex(random_bytes(16)) . '.' . $outExt;
  $destAbs = $destDir . '/' . $destName;

  if (function_exists('imagecreatefromstring')) {
    $bin = @file_get_contents($tmp);
    if ($bin === false) {
      return ['ok' => false, 'error' => 'Lecture du fichier impossible.'];
    }
    $im = @imagecreatefromstring($bin);
    if ($im === false) {
      return ['ok' => false, 'error' => 'Image non reconnue après analyse (fichier suspect ou corrompu).'];
    }
    $ok = $outExt === 'png'
      ? @imagepng($im, $destAbs, 6)
      : @imagejpeg($im, $destAbs, 88);
    imagedestroy($im);
    if (!$ok) {
      return ['ok' => false, 'error' => 'Échec de l’enregistrement sécurisé de l’image.'];
    }
  } else {
    if (!@move_uploaded_file($tmp, $destAbs)) {
      return ['ok' => false, 'error' => 'Échec de l’enregistrement de l’image.'];
    }
    $mimeAfterMove = $finfo->file($destAbs) ?: '';
    if ($outExt === 'png' && $mimeAfterMove !== 'image/png') {
      @unlink($destAbs);
      return ['ok' => false, 'error' => 'Contrôle final : fichier image invalide.'];
    }
    if ($outExt === 'jpg' && !in_array($mimeAfterMove, ['image/jpeg', 'image/jpg'], true)) {
      @unlink($destAbs);
      return ['ok' => false, 'error' => 'Contrôle final : fichier image invalide.'];
    }
    $infoDest = @getimagesize($destAbs);
    if ($infoDest === false) {
      @unlink($destAbs);
      return ['ok' => false, 'error' => 'Image illisible après enregistrement.'];
    }
    $typeDest = (int)($infoDest[2] ?? 0);
    if ($outExt === 'png' && $typeDest !== IMAGETYPE_PNG) {
      @unlink($destAbs);
      return ['ok' => false, 'error' => 'Contrôle final : contenu PNG invalide.'];
    }
    if ($outExt === 'jpg' && $typeDest !== IMAGETYPE_JPEG) {
      @unlink($destAbs);
      return ['ok' => false, 'error' => 'Contrôle final : contenu JPEG invalide.'];
    }
  }

  $mimeAfter = $finfo->file($destAbs) ?: '';
  if ($outExt === 'png' && $mimeAfter !== 'image/png') {
    @unlink($destAbs);
    return ['ok' => false, 'error' => 'Contrôle final : fichier image invalide.'];
  }
  if ($outExt === 'jpg' && !in_array($mimeAfter, ['image/jpeg', 'image/jpg'], true)) {
    @unlink($destAbs);
    return ['ok' => false, 'error' => 'Contrôle final : fichier image invalide.'];
  }

  return ['ok' => true, 'relative_path' => 'uploads/announcements/' . $destName];
}

function announcement_safe_stored_path(?string $path): bool
{
  if ($path === null || $path === '') {
    return true;
  }
  return (bool)preg_match('#^(uploads/announcements/[a-zA-Z0-9._-]+|assets/[a-zA-Z0-9/_\\.-]+)$#', $path);
}

function announcement_delete_uploaded_file(?string $relativePath): void
{
  if ($relativePath === null || $relativePath === '') {
    return;
  }
  if (!preg_match('#^uploads/announcements/[a-f0-9]{32}\\.(jpg|png|pdf)$#i', $relativePath)) {
    return;
  }
  $abs = dirname(__DIR__) . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
  if (is_file($abs)) {
    @unlink($abs);
  }
}
