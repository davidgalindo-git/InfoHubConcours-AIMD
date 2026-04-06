<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
?>
<!doctype html>
<html lang="fr" data-theme="infohub" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="<?= htmlspecialchars(base_url(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
  <title>Admin - InfoHub</title>
  <link rel="stylesheet" href="../assets/app.css">
</head>
<body>
<main class="max-w-[1100px] mx-auto px-4 mt-6 pb-16 animate-fade-in">
  <nav class="flex flex-wrap gap-2 mb-6">
    <a class="btn btn-sm btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200" href="dashboard.php">Dashboard</a>
    <a class="btn btn-sm btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200" href="news_manage.php">Actualités</a>
    <a class="btn btn-sm btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200" href="announcements_manage.php">Annonces</a>
    <a class="btn btn-sm btn-ghost border border-base-content/10 hover:border-primary/35 transition-all duration-200" href="ads_manage.php">Pubs</a>
    <a class="btn btn-sm btn-outline border-error/30 text-error hover:bg-error/10 ml-auto" href="logout.php">Déconnexion</a>
  </nav>
