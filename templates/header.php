<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="fr" data-theme="infohub" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="description" content="<?= htmlspecialchars(SITE_TITLE, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> — concours du mois, actualités et annonces section informatique.">
  <base href="<?= htmlspecialchars(base_url(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
  <title><?= htmlspecialchars(SITE_TITLE) ?></title>
  <link rel="stylesheet" href="assets/app.css">
  <link rel="stylesheet" href="assets/site.css">
</head>
<body class="min-h-screen flex flex-col">
  <header class="shell pt-4 pb-2 animate-fade-in">
    <div class="card bg-base-200/70 backdrop-blur-md border border-base-content/10 shadow-xl transition-shadow duration-300 hover:shadow-xl min-w-0">
      <div class="card-body py-4 px-4 sm:px-5 flex-row items-center gap-3 sm:gap-4 min-w-0">
        <div class="relative shrink-0 w-11 h-11 rounded-xl border border-base-content/15 bg-base-300/50 flex items-center justify-center overflow-hidden ring-2 ring-primary/20 aspect-square">
          <img class="absolute inset-0 z-[1] m-auto max-h-full max-w-full object-contain p-1" src="assets/logo.png" alt="Logo" onerror="this.style.display='none'">
          <span class="relative z-0 px-1 text-center text-[10px] font-extrabold leading-tight"><?= htmlspecialchars(SITE_TITLE) ?></span>
        </div>
        <div class="min-w-0 flex-1">
          <div class="font-extrabold text-base min-[400px]:text-lg tracking-tight text-balance break-words"><?= htmlspecialchars(SITE_TITLE) ?></div>
          <div class="text-sm text-base-content/60">Section informatique</div>
        </div>
      </div>
    </div>
  </header>
  <?php require __DIR__ . '/navbar.php'; ?>
  <main class="shell flex-1 pb-16 min-h-0">
