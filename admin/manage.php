<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/repositories.php';
require_once __DIR__ . '/../lib/upload_announcement.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/auth.php';

require_admin();

$type = (string)($_GET['type'] ?? 'news');
if (!in_array($type, ['news', 'announcements', 'ads'], true)) {
  $type = 'news';
}

$error = null;
$success = null;
$info = null;

$hasAnnPrice = db_table_has_column('announcements', 'price');
$hasAnnContact = db_table_has_column('announcements', 'contact_info');
$hasAdsExpire = db_table_has_column('ads', 'expires_at');

$editId = (int)($_GET['edit_id'] ?? 0);
$editRow = null;
if ($editId > 0) {
  $editRow = match ($type) {
    'news' => getNewsById($editId),
    'announcements' => getAnnouncementRowById($editId),
    'ads' => getAdRowById($editId),
  };
  if (!$editRow) {
    $info = "L'element a modifier est introuvable.";
    $editId = 0;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $adminId = !empty($_SESSION['user']) ? (int)$_SESSION['user']['id'] : null;
  $action = (string)($_POST['action'] ?? '');
  if ($action === 'create') {
    $title = trim((string)($_POST['title'] ?? ''));
    $content = (string)($_POST['content'] ?? '');

    if ($title === '' || $content === '') {
      $error = 'Titre et contenu sont requis.';
    } elseif ($type === 'news') {
      $upNews = announcement_process_upload($_FILES['attachment'] ?? null, 'news');
      if (!$upNews['ok']) {
        $error = (string)($upNews['error'] ?? 'Fichier refusé.');
      } else {
        $is_featured = !empty($_POST['is_featured']) ? 1 : 0;
        $contest_month = trim((string)($_POST['contest_month'] ?? ''));
        $contest_month = $contest_month !== '' ? $contest_month : null;
        $imgN = trim((string)($upNews['relative_path'] ?? ''));
        $newsCols = 'title, content, image_path, is_featured, contest_month';
        $newsVals = ':t, :c, :img, :f, :cm';
        $newsParams = [
          't' => $title,
          'c' => $content,
          'img' => $imgN !== '' ? $imgN : null,
          'f' => $is_featured,
          'cm' => $contest_month,
        ];
        if (db_table_has_column('news', 'created_by')) {
          $newsCols .= ', created_by';
          $newsVals .= ', :cb';
          $newsParams['cb'] = $adminId;
        }
        db()->prepare("INSERT INTO news ($newsCols) VALUES ($newsVals)")->execute($newsParams);
        $success = 'Actualité créée.';
      }
    } elseif ($type === 'announcements') {
      $upAnn = announcement_process_upload($_FILES['attachment'] ?? null, 'announcements');
      if (!$upAnn['ok']) {
        $error = (string)($upAnn['error'] ?? 'Fichier refusé.');
      } else {
        $category_slug = (string)($_POST['category_slug'] ?? 'autres');
        if (!array_key_exists($category_slug, ANNOUNCEMENT_CATEGORIES)) {
          $category_slug = 'autres';
        }
        $is_featured = !empty($_POST['is_featured']) ? 1 : 0;
        $imgA = trim((string)($upAnn['relative_path'] ?? ''));
        $annParams = [
          't' => $title,
          'c' => $content,
          'img' => $imgA !== '' ? $imgA : null,
          'cat' => $category_slug,
          'u' => $adminId,
          's' => 'visible',
          'f' => $is_featured,
        ];
        if ($hasAnnPrice) {
          $annParams['price'] = trim((string)($_POST['price'] ?? '')) ?: null;
        }
        if ($hasAnnContact) {
          $annParams['contact'] = trim((string)($_POST['contact_info'] ?? '')) ?: null;
        }
        db()->prepare(
          'INSERT INTO announcements (title, content, image_path, category_slug, created_by, status, is_featured'
          . ($hasAnnPrice ? ', price' : '')
          . ($hasAnnContact ? ', contact_info' : '')
          . ') VALUES (:t, :c, :img, :cat, :u, :s, :f'
          . ($hasAnnPrice ? ', :price' : '')
          . ($hasAnnContact ? ', :contact' : '')
          . ')'
        )->execute($annParams);
        $success = 'Annonce créée.';
      }
    } else {
      $upAd = announcement_process_upload($_FILES['attachment'] ?? null, 'ads');
      if (!$upAd['ok']) {
        $error = (string)($upAd['error'] ?? 'Fichier refusé.');
      } else {
        $link_url = trim((string)($_POST['link_url'] ?? ''));
        $link_url = $link_url !== '' ? $link_url : null;
        $expiresAt = trim((string)($_POST['expires_at'] ?? ''));
        $expiresAt = $expiresAt !== '' ? str_replace('T', ' ', $expiresAt) . ':00' : null;
        $imgAd = trim((string)($upAd['relative_path'] ?? ''));
        $adsParams = [
          't' => $title,
          'c' => $content,
          'img' => $imgAd !== '' ? $imgAd : null,
          'l' => $link_url,
          'u' => $adminId,
          's' => 'visible',
        ];
        if ($hasAdsExpire) {
          $adsParams['exp'] = $expiresAt;
        }
        db()->prepare(
          'INSERT INTO ads (title, content, image_path, link_url, created_by, status'
          . ($hasAdsExpire ? ', expires_at' : '')
          . ') VALUES (:t, :c, :img, :l, :u, :s'
          . ($hasAdsExpire ? ', :exp' : '')
          . ')'
        )->execute($adsParams);
        $success = 'Pub créée.';
      }
    }
  } elseif ($action === 'update') {
    $updateId = (int)($_POST['id'] ?? 0);
    $title = trim((string)($_POST['title'] ?? ''));
    $content = (string)($_POST['content'] ?? '');
    if ($updateId <= 0 || $title === '' || $content === '') {
      $error = 'Impossible de mettre a jour: champs requis manquants.';
    } elseif ($type === 'news') {
      $beforeNews = getNewsById($updateId);
      $savedNewsImg = $beforeNews ? (string)($beforeNews['image_path'] ?? '') : '';
      $upN = announcement_process_upload($_FILES['attachment'] ?? null, 'news');
      if (!$upN['ok']) {
        $error = (string)($upN['error'] ?? 'Fichier refusé.');
      } else {
        $newImg = $savedNewsImg !== '' ? $savedNewsImg : null;
        if (!empty($_POST['remove_attachment'])) {
          $newImg = null;
        }
        if (!empty($upN['relative_path'])) {
          $newImg = $upN['relative_path'];
        }
        $contest_month = trim((string)($_POST['contest_month'] ?? ''));
        $contest_month = $contest_month !== '' ? $contest_month : null;
        updateNewsById($updateId, [
          'title' => $title,
          'content' => $content,
          'image_path' => $newImg,
          'is_featured' => !empty($_POST['is_featured']),
          'contest_month' => $contest_month,
        ]);
        if ($savedNewsImg !== '' && $savedNewsImg !== (string)$newImg) {
          announcement_delete_uploaded_file($savedNewsImg);
        }
        $success = 'Actualité mise a jour.';
        auth_log($adminId, 'update_news', 'news', $updateId, 'Edition admin');
        $editId = 0;
        $editRow = null;
      }
    } elseif ($type === 'announcements') {
      $beforeAnn = getAnnouncementRowById($updateId);
      $savedAnnImg = $beforeAnn ? (string)($beforeAnn['image_path'] ?? '') : '';
      $upA = announcement_process_upload($_FILES['attachment'] ?? null, 'announcements');
      if (!$upA['ok']) {
        $error = (string)($upA['error'] ?? 'Fichier refusé.');
      } else {
        $newImg = $savedAnnImg !== '' ? $savedAnnImg : null;
        if (!empty($_POST['remove_attachment'])) {
          $newImg = null;
        }
        if (!empty($upA['relative_path'])) {
          $newImg = $upA['relative_path'];
        }
        $category_slug = (string)($_POST['category_slug'] ?? 'autres');
        if (!array_key_exists($category_slug, ANNOUNCEMENT_CATEGORIES)) {
          $category_slug = 'autres';
        }
        updateAnnouncementById($updateId, [
          'title' => $title,
          'content' => $content,
          'image_path' => $newImg,
          'category_slug' => $category_slug,
          'is_featured' => !empty($_POST['is_featured']),
          'price' => trim((string)($_POST['price'] ?? '')) ?: null,
          'contact_info' => trim((string)($_POST['contact_info'] ?? '')) ?: null,
        ]);
        if ($savedAnnImg !== '' && $savedAnnImg !== (string)$newImg) {
          announcement_delete_uploaded_file($savedAnnImg);
        }
        $success = 'Annonce mise a jour.';
        auth_log($adminId, 'update_announcement', 'announcement', $updateId, 'Edition admin');
        $editId = 0;
        $editRow = null;
      }
    } else {
      $link_url = trim((string)($_POST['link_url'] ?? ''));
      $link_url = $link_url !== '' ? $link_url : null;
      if ($link_url !== null && !preg_match('/^https?:\/\/.+/i', $link_url)) {
        $error = 'Lien invalide (http/https).';
      } else {
        $beforeAd = getAdRowById($updateId);
        $savedAdImg = $beforeAd ? (string)($beforeAd['image_path'] ?? '') : '';
        $upAd = announcement_process_upload($_FILES['attachment'] ?? null, 'ads');
        if (!$upAd['ok']) {
          $error = (string)($upAd['error'] ?? 'Fichier refusé.');
        } else {
          $newImg = $savedAdImg !== '' ? $savedAdImg : null;
          if (!empty($_POST['remove_attachment'])) {
            $newImg = null;
          }
          if (!empty($upAd['relative_path'])) {
            $newImg = $upAd['relative_path'];
          }
          $expiresAt = trim((string)($_POST['expires_at'] ?? ''));
          $expiresAt = $expiresAt !== '' ? str_replace('T', ' ', $expiresAt) . ':00' : null;
          updateAdById($updateId, [
            'title' => $title,
            'content' => $content,
            'image_path' => $newImg,
            'link_url' => $link_url,
            'expires_at' => $expiresAt,
          ]);
          if ($savedAdImg !== '' && $savedAdImg !== (string)$newImg) {
            announcement_delete_uploaded_file($savedAdImg);
          }
          $success = 'Pub mise a jour.';
          auth_log($adminId, 'update_ad', 'ad', $updateId, 'Edition admin');
          $editId = 0;
          $editRow = null;
        }
      }
    }
  } elseif ($action === 'delete') {
    $deleteId = (int)($_POST['id'] ?? 0);
    if ($deleteId > 0) {
      if ($type === 'news') {
        $delNews = getNewsById($deleteId);
        if ($delNews) {
          announcement_delete_uploaded_file((string)($delNews['image_path'] ?? '') ?: null);
        }
        db()->prepare('DELETE FROM news WHERE id = :id')->execute(['id' => $deleteId]);
        auth_log($adminId, 'delete_news', 'news', $deleteId, 'Suppression par admin');
        $success = 'Actualité supprimée.';
      } elseif ($type === 'announcements') {
        $delAnn = getAnnouncementRowById($deleteId);
        if ($delAnn) {
          announcement_delete_uploaded_file((string)($delAnn['image_path'] ?? '') ?: null);
        }
        db()->prepare('DELETE FROM announcements WHERE id = :id')->execute(['id' => $deleteId]);
        auth_log($adminId, 'delete_announcement', 'announcement', $deleteId, 'Suppression par admin');
        $success = 'Annonce supprimée.';
      } else {
        $delAd = getAdRowById($deleteId);
        if ($delAd) {
          announcement_delete_uploaded_file((string)($delAd['image_path'] ?? '') ?: null);
        }
        db()->prepare('DELETE FROM ads WHERE id = :id')->execute(['id' => $deleteId]);
        auth_log($adminId, 'delete_ad', 'ad', $deleteId, 'Suppression par admin');
        $success = 'Pub supprimée.';
      }
    }
  }
}

$items = match ($type) {
  'news' => db()->query('SELECT * FROM news ORDER BY published_at DESC LIMIT 50')->fetchAll(),
  'announcements' => db()->query('SELECT * FROM announcements ORDER BY posted_at DESC LIMIT 50')->fetchAll(),
  'ads' => db()->query('SELECT * FROM ads ORDER BY posted_at DESC LIMIT 50')->fetchAll(),
};

$labels = [
  'news' => [
    'page' => 'Gérer les actualités',
    'create' => 'Créer une actualité',
    'edit' => 'Modifier une actualité',
    'list' => 'Dernières actualités',
    'del_confirm' => 'Supprimer cette actualité ?',
    'empty' => 'Aucune actualité.',
  ],
  'announcements' => [
    'page' => 'Gérer les annonces',
    'create' => 'Créer une annonce',
    'edit' => 'Modifier une annonce',
    'list' => 'Dernières annonces',
    'del_confirm' => 'Supprimer cette annonce ?',
    'empty' => 'Aucune annonce.',
  ],
  'ads' => [
    'page' => 'Gérer les pubs',
    'create' => 'Créer une pub',
    'edit' => 'Modifier une pub',
    'list' => 'Dernières pubs',
    'del_confirm' => 'Supprimer cette pub ?',
    'empty' => 'Aucune pub.',
  ],
];
$L = $labels[$type];

$isEditMode = $editRow !== null;
$formAction = $isEditMode ? 'update' : 'create';
$formTitle = $isEditMode ? $L['edit'] : $L['create'];
$formButton = $isEditMode ? 'Enregistrer' : 'Créer';
$titleValue = (string)($editRow['title'] ?? '');
$contentValue = (string)($editRow['content'] ?? '');
$imageValue = (string)($editRow['image_path'] ?? '');
$featuredValue = !empty($editRow['is_featured']);
$contestValue = (string)($editRow['contest_month'] ?? '');
$categoryValue = (string)($editRow['category_slug'] ?? 'autres');
$linkValue = (string)($editRow['link_url'] ?? '');
$priceValue = (string)($editRow['price'] ?? '');
$contactValue = (string)($editRow['contact_info'] ?? '');
$expiresRaw = (string)($editRow['expires_at'] ?? '');
$expiresValue = $expiresRaw !== '' ? date('Y-m-d\TH:i', strtotime($expiresRaw)) : '';

require __DIR__ . '/header.php';
?>

<section class="my-2">
  <h1 class="text-xl font-bold mb-5"><?= htmlspecialchars($L['page']) ?></h1>

  <?php if ($error): ?>
    <div role="alert" class="alert alert-error mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($info): ?>
    <div role="alert" class="alert alert-info mb-4 text-sm"><?= htmlspecialchars($info) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div role="alert" class="alert alert-success mb-4 text-sm"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.35fr)] min-w-0">
    <div class="card bg-base-200/50 border border-base-content/10 min-w-0">
      <div class="card-body gap-3 min-w-0">
        <h2 class="card-title text-lg"><?= htmlspecialchars($formTitle) ?></h2>
        <form method="post" enctype="multipart/form-data" action="manage.php?type=<?= htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" class="flex flex-col gap-3">
          <input type="hidden" name="action" value="<?= $formAction ?>">
          <?php if ($isEditMode): ?>
            <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">
          <?php endif; ?>
          <input class="input input-bordered w-full bg-base-100/70 border-base-content/15" type="text" name="title" placeholder="Titre" required value="<?= htmlspecialchars($titleValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

          <textarea name="content" placeholder="Contenu (Markdown simple)" rows="8" class="textarea textarea-bordered w-full bg-base-100/70 border-base-content/15 text-base leading-relaxed" required><?= htmlspecialchars($contentValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>

          <p class="text-sm text-base-content/60 -mb-1">Image ou PDF (optionnel) : glisser-déposer ou cliquer sur la zone — <strong>JPG</strong>, <strong>PNG</strong> ou <strong>PDF</strong>, max 5 Mo (comme sur le site public).</p>
          <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
          <?php if ($isEditMode && $imageValue !== ''): ?>
            <label class="label cursor-pointer justify-start gap-2 py-1">
              <input type="checkbox" name="remove_attachment" value="1" class="checkbox checkbox-sm checkbox-primary">
              <span class="label-text text-sm">Retirer la pièce jointe actuelle</span>
            </label>
          <?php endif; ?>
          <input type="file" id="manage-att-file" name="attachment" class="hidden" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
          <div id="manage-att-dropzone" class="rounded-xl border-2 border-dashed border-base-content/25 bg-base-100/50 px-4 py-8 text-center text-sm text-base-content/65 cursor-pointer hover:border-primary/40 transition-colors">
            <span data-fn class="font-medium text-base-content/80"><?= $isEditMode && $imageValue !== '' ? htmlspecialchars(basename($imageValue), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : 'Glisser-déposer ou cliquer pour joindre JPG, PNG ou PDF' ?></span>
          </div>

          <?php if ($type === 'news'): ?>
            <label class="label cursor-pointer justify-start gap-3 py-1">
              <input type="checkbox" name="is_featured" value="1" class="checkbox checkbox-primary checkbox-sm" <?= $featuredValue ? 'checked' : '' ?>>
              <span class="label-text text-base-content/75 font-semibold text-sm">Marquer « à la une »</span>
            </label>
            <input type="text" name="contest_month" placeholder="concours : YYYY-MM (optionnel)"
              class="input input-bordered w-full bg-base-100/70 border-base-content/15"
              value="<?= htmlspecialchars($contestValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
          <?php elseif ($type === 'announcements'): ?>
            <label class="form-control w-full">
              <span class="label-text text-sm font-bold text-base-content/65">Catégorie</span>
              <select name="category_slug" class="select select-bordered w-full bg-base-100/70 border-base-content/15">
                <?php foreach (ANNOUNCEMENT_CATEGORIES as $slug => $label): ?>
                  <option value="<?= htmlspecialchars($slug) ?>" <?= $categoryValue === $slug ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <?php if ($hasAnnPrice): ?>
              <input type="text" name="price" placeholder="Prix (optionnel)"
                class="input input-bordered w-full bg-base-100/70 border-base-content/15"
                value="<?= htmlspecialchars($priceValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <?php endif; ?>
            <?php if ($hasAnnContact): ?>
              <input type="text" name="contact_info" placeholder="Contact (optionnel)"
                class="input input-bordered w-full bg-base-100/70 border-base-content/15"
                value="<?= htmlspecialchars($contactValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <?php endif; ?>
            <label class="label cursor-pointer justify-start gap-3 py-1">
              <input type="checkbox" name="is_featured" value="1" class="checkbox checkbox-primary checkbox-sm" <?= $featuredValue ? 'checked' : '' ?>>
              <span class="label-text text-base-content/75 font-semibold text-sm">Marquer « à la une »</span>
            </label>
          <?php else: ?>
            <input type="url" name="link_url" placeholder="Lien optionnel (https://...)"
              class="input input-bordered w-full bg-base-100/70 border-base-content/15"
              value="<?= htmlspecialchars($linkValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <?php if ($hasAdsExpire): ?>
              <input type="datetime-local" name="expires_at"
                class="input input-bordered w-full bg-base-100/70 border-base-content/15"
                value="<?= htmlspecialchars($expiresValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <?php endif; ?>
          <?php endif; ?>

          <div class="flex flex-wrap gap-2">
            <button class="btn btn-primary w-fit transition-transform duration-200 hover:scale-[1.02]" type="submit"><?= htmlspecialchars($formButton) ?></button>
            <?php if ($isEditMode): ?>
              <a class="btn btn-ghost border border-base-content/15" href="manage.php?type=<?= htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Annuler</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <div class="card bg-base-200/50 border border-base-content/10 min-w-0">
      <div class="card-body gap-3 min-w-0">
        <h2 class="card-title text-lg"><?= htmlspecialchars($L['list']) ?></h2>
        <?php if (!$items): ?>
          <p class="text-sm text-base-content/55"><?= htmlspecialchars($L['empty']) ?></p>
        <?php else: ?>
          <div class="flex flex-col gap-3">
            <?php foreach ($items as $row): ?>
              <?php
                $publicUrl = match ($type) {
                  'news' => '../index.php?route=news_detail&id=' . (int)$row['id'],
                  'announcements' => '../index.php?route=announcement_detail&id=' . (int)$row['id'],
                  'ads' => '../index.php?route=ad_detail&id=' . (int)$row['id'],
                };
              ?>
              <div class="rounded-xl border border-base-content/10 bg-base-100/40 p-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                  <div class="min-w-0">
                    <a class="link link-primary font-semibold text-base-content break-words" href="<?= htmlspecialchars($publicUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars((string)$row['title']) ?></a>
                    <div class="text-xs font-bold text-base-content/55 mt-1.5">
                      <?php if ($type === 'news'): ?>
                        <?= !empty($row['is_featured']) ? 'À la une' : '—' ?>
                        <?php if (!empty($row['contest_month'])): ?>
                          · Concours: <?= htmlspecialchars((string)$row['contest_month']) ?>
                        <?php endif; ?>
                        · <?= htmlspecialchars((string)$row['published_at']) ?>
                      <?php elseif ($type === 'announcements'): ?>
                        <?= htmlspecialchars(ANNOUNCEMENT_CATEGORIES[$row['category_slug']] ?? (string)$row['category_slug']) ?>
                        <?php if (!empty($row['is_featured'])): ?> · À la une<?php endif; ?>
                        · <?= htmlspecialchars((string)$row['posted_at']) ?>
                      <?php else: ?>
                        <?= htmlspecialchars((string)$row['posted_at']) ?>
                        <?php if (!empty($row['link_url'])): ?> · lien<?php endif; ?>
                      <?php endif; ?>
                    </div>
                  </div>
                  <form method="post" action="manage.php?type=<?= htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" onsubmit="return confirm('<?= htmlspecialchars($L['del_confirm'], ENT_QUOTES) ?>');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <div class="flex gap-2">
                      <a class="btn btn-sm btn-outline border-base-content/25" href="manage.php?type=<?= htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>&edit_id=<?= (int)$row['id'] ?>">Modifier</a>
                      <button class="btn btn-sm btn-outline border-error/40 text-error hover:bg-error/10" type="submit">Supprimer</button>
                    </div>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="card bg-base-200/40 border border-dashed border-primary/25 min-w-0">
      <div class="card-body">
        <h3 class="card-title text-base">Aide rapide</h3>
        <p class="text-sm text-base-content/65">
          Markdown simple dans les champs texte :
          <ul>
              <li>Titres : # Titre1, ## Titre2</li>
              <li>Gras : **<strong>texte gras</strong>**</li>
              <li>Liens : [titre_lien](url)</li>
          </ul>
        </p>
      </div>
    </div>
  </div>
</section>

<script>
(function () {
  var z = document.getElementById('manage-att-dropzone');
  var inp = document.getElementById('manage-att-file');
  if (!z || !inp) return;
  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (ev) {
    z.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); });
  });
  z.addEventListener('dragover', function () { z.classList.add('border-primary', 'bg-primary/5'); });
  z.addEventListener('dragleave', function () { z.classList.remove('border-primary', 'bg-primary/5'); });
  z.addEventListener('drop', function (e) {
    z.classList.remove('border-primary', 'bg-primary/5');
    var f = e.dataTransfer.files[0];
    if (!f) return;
    inp.files = e.dataTransfer.files;
    var el = z.querySelector('[data-fn]');
    if (el) el.textContent = f.name;
  });
  z.addEventListener('click', function () { inp.click(); });
})();
</script>

<?php require __DIR__ . '/footer.php'; ?>
