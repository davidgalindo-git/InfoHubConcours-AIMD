<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/repositories.php';
?>

<?php require __DIR__ . '/../templates/header.php'; ?>

<?php
$currentMonth = date('Y-m');
$contest = getContestOfMonth($currentMonth);
$newsList = getLatestFeaturedNews(1);
$newsItem = $newsList[0] ?? null;

$annList = getLatestFeaturedAnnouncements(1);
$announcementItem = $annList[0] ?? null;

$adsList = getLatestAds(1);
$adItem = $adsList[0] ?? null;

$contestHref = $contest ? ('index.php?route=news_detail&id=' . (int)$contest['id']) : 'index.php?route=news';
?>

<section class="intro">
  <p>Bienvenue</p>
  <p>Vous êtes sur infohub.mycpnv.ch, <br> la plateforme de partage de la section info du CPNV ! </p>
  <p>En attendant la mise en ligne du site complet, vous pouvez accéder aux principales sections ci‑dessous.</p>
  <p>Contact: CPNV_Infohub@eduvaud.ch</p>
</section>

<div class="tiles">
  <!-- Concours du mois -->
  <a class="tile" href="<?= h($contestHref) ?>">
    <?php if ($contest && !empty($contest['image_path'])): ?>
      <img src="<?= h($contest['image_path']) ?>" alt="">
    <?php else: ?>
      <div class="tile-placeholder">Concours</div>
    <?php endif; ?>
    <div class="tile-title">Le concours du mois</div>
    <?php if ($contest && !empty($contest['title'])): ?>
      <div class="tile-subtitle"><?= h((string)$contest['title']) ?></div>
    <?php endif; ?>
  </a>

  <!-- News -->
  <a class="tile" href="index.php?route=news">
    <?php if ($newsItem && !empty($newsItem['image_path'])): ?>
      <img src="<?= h($newsItem['image_path']) ?>" alt="">
    <?php else: ?>
      <div class="tile-placeholder">News</div>
    <?php endif; ?>
    <div class="tile-title">Les news</div>
    <?php if ($newsItem && !empty($newsItem['title'])): ?>
      <div class="tile-subtitle"><?= h((string)$newsItem['title']) ?></div>
    <?php endif; ?>
  </a>

  <!-- Annonces -->
  <a class="tile" href="index.php?route=announcements">
    <?php if ($announcementItem && !empty($announcementItem['image_path'])): ?>
      <img src="<?= h($announcementItem['image_path']) ?>" alt="">
    <?php else: ?>
      <div class="tile-placeholder">Annonces</div>
    <?php endif; ?>
    <div class="tile-title">Les annonces</div>
    <?php if ($announcementItem && !empty($announcementItem['title'])): ?>
      <div class="tile-subtitle"><?= h((string)$announcementItem['title']) ?></div>
    <?php endif; ?>
  </a>

  <!-- Pubs -->
  <a class="tile" href="index.php?route=ads">
    <?php if ($adItem && !empty($adItem['image_path'])): ?>
      <img src="<?= h($adItem['image_path']) ?>" alt="">
    <?php else: ?>
      <div class="tile-placeholder">Pubs</div>
    <?php endif; ?>
    <div class="tile-title">Les pubs</div>
    <?php if ($adItem && !empty($adItem['title'])): ?>
      <div class="tile-subtitle"><?= h((string)$adItem['title']) ?></div>
    <?php endif; ?>
  </a>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>

