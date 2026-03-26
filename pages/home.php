<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/repositories.php';
?>

<?php require __DIR__ . '/../templates/header.php'; ?>

<?php
$currentMonth = date('Y-m');
$contest = getContestOfMonth($currentMonth);
$newsItems = getLatestFeaturedNews(2);
$announcementItems = getLatestFeaturedAnnouncements(2);
$adItems = getLatestAds(2);

// Exemples "Annonces" affichés sur la page d'accueil :
// - on parcourt les images `annonce*.*` présentes dans `assets/`
$announcementExampleImages = [];
$exampleSources = [
  ['fs' => __DIR__ . '/../assets', 'url' => 'assets'],
];
$allowedExts = ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'];
$seen = [];

foreach ($exampleSources as $src) {
  if (!is_dir($src['fs'])) {
    continue;
  }

  $files = scandir($src['fs']);
  if ($files === false) {
    continue;
  }

  foreach ($files as $file) {
    if ($file === '.' || $file === '..') {
      continue;
    }

    $fullPath = $src['fs'] . '/' . $file;
    if (!is_file($fullPath)) {
      continue;
    }

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
      continue;
    }

    $baseName = pathinfo($file, PATHINFO_FILENAME); // sans extension
    if (!preg_match('/^annonce/i', (string)$baseName)) {
      continue;
    }

    $imageSrc = $src['url'] . '/' . $file;
    if (isset($seen[$imageSrc])) {
      continue;
    }
    $seen[$imageSrc] = true;

    // alt basé sur le nom de fichier (ex: "annonce1" => "Annonce1")
    $altBase = preg_replace('/[_-]+/', ' ', (string)$baseName) ?? (string)$baseName;
    $alt = ucwords(trim((string)$altBase));

    $announcementExampleImages[] = [
      'src' => $imageSrc,
      'alt' => $alt,
      'filename' => $file,
    ];
  }
}

usort($announcementExampleImages, function ($a, $b) {
  return strcmp((string)$a['filename'], (string)$b['filename']);
});
?>

<section class="intro">
  <p>Bienvenue</p>
  <p>Vous êtes sur infohub.mycpnv.ch, <br> la plateforme de partage de la section info du CPNV ! </p>
  <p>En attendant la mise en ligne du site complet, vous pouvez accéder aux principales sections ci‑dessous.</p>
  <p>Contact: CPNV_Infohub@eduvaud.ch</p>
</section>

<?php if ($contest): ?>
  <div class="tiles tiles-tight">
    <a class="tile" href="index.php?route=news_detail&id=<?= (int)$contest['id'] ?>">
    <div class="tile-title">Concours du mois</div>  
      <?php if (!empty($contest['image_path'])): ?>
        <img src="<?= h($contest['image_path']) ?>" alt="">
      <?php else: ?>
        <div class="tile-placeholder"><?= h((string)$contest['title']) ?></div>
      <?php endif; ?>
    </a>
  </div>
<?php else: ?>
  <div class="tiles tiles-tight">
    <div class="tile" aria-hidden="true">
      <div class="tile-placeholder">Concours</div>
      <div class="tile-title">Le concours du mois</div>
    </div>
  </div>
<?php endif; ?>

<div class="featured-sections">
<section class="section">
  <h1 class="section-title">Actualités à la une</h1>
  <div class="tiles tiles-tight">
    <?php foreach ($newsItems as $n): ?>
      <a class="tile" href="index.php?route=news_detail&id=<?= (int)$n['id'] ?>">
        <?php if (!empty($n['image_path'])): ?>
          <img src="<?= h($n['image_path']) ?>" alt="">
          <div class="tile-title"><?= h((string)$n['title']) ?></div>
        <?php else: ?>
          <div class="tile-placeholder"><?= h((string)$n['title']) ?></div>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="section">
  <h1 class="section-title">Annonces à la une</h1>
  <div class="tiles tiles-tight">
    <?php if (!empty($announcementExampleImages)): ?>
      <?php foreach ($announcementExampleImages as $ex): ?>
        <a class="tile" href="index.php?route=announcements">
          <img src="<?= h($ex['src']) ?>" alt="<?= h($ex['alt']) ?>">
        </a>
      <?php endforeach; ?>
    <?php else: ?>
      <?php foreach ($announcementItems as $a): ?>
        <a class="tile" href="index.php?route=announcement_detail&id=<?= (int)$a['id'] ?>">
          <?php if (!empty($a['image_path'])): ?>
            <img src="<?= h($a['image_path']) ?>" alt="<?= h((string)$a['title']) ?>">
          <?php else: ?>
            <div class="tile-placeholder"><?= h((string)$a['title']) ?></div>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <h1 class="section-title">Pubs à la une</h1>
  <div class="tiles tiles-tight">
    <?php foreach ($adItems as $ad): ?>
      <a class="tile" href="index.php?route=ad_detail&id=<?= (int)$ad['id'] ?>">
        <?php if (!empty($ad['image_path'])): ?>
          <img src="<?= h($ad['image_path']) ?>" alt="">
        <?php else: ?>
          <div class="tile-placeholder"><?= h((string)$ad['title']) ?></div>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
</section>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>

