<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
?>
<!doctype html>
<html lang="fr" data-theme="infohub" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <base href="<?= htmlspecialchars(base_url(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
  <title>Admin - InfoHub</title>
  <link rel="stylesheet" href="../assets/app.css">
  <link rel="stylesheet" href="../assets/site.css">
</head>
<body class="min-h-screen overflow-x-clip">
<main class="shell admin-shell-wide mt-6 pb-16 animate-fade-in min-w-0">
  <nav class="flex flex-nowrap sm:flex-wrap items-center gap-2 mb-6 overflow-x-auto pb-1 -mx-1 px-1 scroll-smooth [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:overflow-visible touch-pan-x">
    <a class="btn btn-sm shrink-0 min-h-10 btn-outline border-primary/30 text-primary hover:bg-primary/10 gap-1" href="../index.php?route=home">
      <span aria-hidden="true">←</span> Site public
    </a>
    <span class="hidden sm:inline w-px h-6 bg-base-content/15 mx-1 shrink-0" aria-hidden="true"></span>
    <a class="btn btn-sm shrink-0 min-h-10 btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200" href="dashboard.php">Dashboard</a>
    <?php $at = (string)($_GET['type'] ?? ''); ?>
    <a class="btn btn-sm shrink-0 min-h-10 <?= $at === 'news' ? 'btn-primary' : 'btn-ghost border border-base-content/10 hover:border-primary/35' ?> transition-all duration-200" href="manage.php?type=news">Actualités</a>
    <a class="btn btn-sm shrink-0 min-h-10 <?= $at === 'announcements' ? 'btn-primary' : 'btn-ghost border border-base-content/10 hover:border-primary/35' ?> transition-all duration-200" href="manage.php?type=announcements">Annonces</a>
    <a class="btn btn-sm shrink-0 min-h-10 <?= $at === 'ads' ? 'btn-primary' : 'btn-ghost border border-base-content/10 hover:border-primary/35' ?> transition-all duration-200" href="manage.php?type=ads">Pubs</a>
    <a class="btn btn-sm shrink-0 min-h-10 <?= basename((string)($_SERVER['SCRIPT_NAME'] ?? '')) === 'users.php' ? 'btn-primary' : 'btn-ghost border border-base-content/10 hover:border-primary/35' ?> transition-all duration-200" href="users.php">Utilisateurs</a>
    <a class="btn btn-sm shrink-0 min-h-10 <?= basename((string)($_SERVER['SCRIPT_NAME'] ?? '')) === 'moderation.php' ? 'btn-primary' : 'btn-ghost border border-base-content/10 hover:border-primary/35' ?> transition-all duration-200" href="moderation.php">Logs modération</a>
    <a class="btn btn-sm shrink-0 min-h-10 btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200" href="invites.php">Invitations</a>
    <a class="btn btn-sm shrink-0 min-h-10 btn-outline border-error/30 text-error hover:bg-error/10 sm:ml-auto" href="logout.php">Déconnexion</a>
  </nav>
