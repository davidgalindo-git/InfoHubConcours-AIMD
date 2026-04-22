<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/repositories.php';
require_once __DIR__ . '/../lib/mailer.php';
require_once __DIR__ . '/../lib/mail_templates.php';

auth_require_login();

auth_refresh_user();
$user = auth_user();
if ($user === null) {
  header('Location: index.php?route=sign_in');
  exit;
}
$uid = (int)$user['id'];

$rowDb = db()->prepare('SELECT email, full_name, role, status FROM users WHERE id = :id LIMIT 1');
$rowDb->execute(['id' => $uid]);
$dbUser = $rowDb->fetch();
if ($dbUser) {
  $user['email'] = (string)$dbUser['email'];
  $user['full_name'] = (string)$dbUser['full_name'];
  $user['role'] = (string)$dbUser['role'];
  $user['status'] = (string)$dbUser['status'];
  if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
    $_SESSION['user']['email'] = $user['email'];
    $_SESSION['user']['full_name'] = $user['full_name'];
    $_SESSION['user']['role'] = $user['role'];
    $_SESSION['user']['status'] = $user['status'];
  }
}

$role = (string)$user['role'];

/** Onglets visibles selon le rôle (contenu limité comme demandé). */
$profileTabs = match ($role) {
  'user' => [
    'compte' => 'Mon compte',
    'annonces' => 'Mes annonces',
  ],
  'collaborateur' => [
    'compte' => 'Mon compte',
    'pubs' => 'Mes pubs',
  ],
  'admin' => [
    'compte' => 'Mon compte',
    'annonces' => 'Mes annonces',
    'pubs' => 'Mes pubs',
    'actualites' => 'Mes actualités',
  ],
  default => ['compte' => 'Mon compte'],
};

$rawTab = preg_replace('/[^a-z]/', '', strtolower((string)($_GET['tab'] ?? 'compte')));
$tab = $rawTab !== '' ? $rawTab : 'compte';
if (!isset($profileTabs[$tab])) {
  header('Location: index.php?route=profile&tab=compte');
  exit;
}

$errName = $errPw = null;
$okName = $okPw = null;
$okFlash = isset($_GET['ok']) ? (string)$_GET['ok'] : '';
if ($okFlash === 'name') {
  $okName = 'Pseudo enregistré.';
} elseif ($okFlash === 'pw') {
  $okPw = 'Mot de passe mis à jour.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');
  if ($action === 'update_name') {
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    if ($fullName === '') {
      $errName = 'Le nom d’utilisateur est requis.';
    } elseif (mb_strlen($fullName) > 120) {
      $errName = 'Le nom d’utilisateur est trop long (120 caractères max).';
    } elseif (strcasecmp($fullName, (string)$user['email']) === 0) {
      $errName = 'Le nom d’utilisateur ne doit pas être identique à ton adresse e-mail.';
    } else {
      db()->prepare('UPDATE users SET full_name = :n WHERE id = :id')->execute(['n' => $fullName, 'id' => $uid]);
      auth_refresh_user();
      header('Location: index.php?route=profile&tab=compte&ok=name');
      exit;
    }
  } elseif ($action === 'change_password') {
    $cur = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $new2 = (string)($_POST['new_password_confirm'] ?? '');
    if ($cur === '' || $new === '' || $new2 === '') {
      $errPw = 'Remplis tous les champs mot de passe.';
    } elseif ($new !== $new2) {
      $errPw = 'Les deux nouveaux mots de passe ne correspondent pas.';
    } elseif (strlen($new) < 8) {
      $errPw = 'Le nouveau mot de passe doit faire au moins 8 caractères.';
    } else {
      $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
      $stmt->execute(['id' => $uid]);
      $row = $stmt->fetch();
      $hash = (string)($row['password_hash'] ?? '');
      if (!$row || !password_verify($cur, $hash)) {
        $errPw = 'Mot de passe actuel incorrect.';
      } else {
        db()->prepare('UPDATE users SET password_hash = :h WHERE id = :id')->execute([
          'h' => password_hash($new, PASSWORD_DEFAULT),
          'id' => $uid,
        ]);
        $tpl = mail_tpl_password_changed((string)$user['full_name'], date('d/m/Y H:i:s'));
        mailer_send((string)$user['email'], (string)$user['full_name'], $tpl['subject'], $tpl['html'], $tpl['text']);
        auth_log($uid, 'profile_change_password', 'user', $uid, 'Changement de mot de passe');
        header('Location: index.php?route=profile&tab=compte&ok=pw');
        exit;
      }
    }
  }
}

$myAnnouncements = getAnnouncementsByCreator($uid);
$myAds = getAdsByCreator($uid);
$myNews = getNewsByCreator($uid);
$canAnn = in_array($role, ['user', 'admin'], true);
$canNewsCol = db_table_has_column('news', 'created_by');

$pseudo = trim((string)$user['full_name']);
$pseudoSameAsEmail = $pseudo !== '' && strcasecmp($pseudo, (string)$user['email']) === 0;

require __DIR__ . '/../templates/header.php';

$tabQs = static function (string $t): string {
  return 'index.php?route=profile&tab=' . rawurlencode($t);
};

?>

<section class="my-6 animate-fade-in max-w-3xl mx-auto min-w-0">
  <h1 class="text-2xl font-bold mb-1">Espace personnel</h1>
  <p class="text-sm text-base-content/60 mb-4">
    <?php if ($role === 'user'): ?>
      Compte et annonces (rôle <strong class="text-base-content/80">utilisateur</strong>).
    <?php elseif ($role === 'collaborateur'): ?>
      Compte et pubs (rôle <strong class="text-base-content/80">collaborateur</strong>).
    <?php else: ?>
      Compte, annonces, pubs et actualités (rôle <strong class="text-base-content/80">admin</strong>).
    <?php endif; ?>
  </p>

  <div role="tablist" aria-label="Sections du profil" class="tabs tabs-boxed flex flex-wrap gap-1 bg-base-200/60 border border-base-content/10 p-1 rounded-xl mb-6">
    <?php foreach ($profileTabs as $tid => $label): ?>
      <a
        role="tab"
        class="tab tab-sm grow sm:grow-0 <?= $tab === $tid ? 'tab-active' : '' ?>"
        href="<?= h($tabQs($tid)) ?>"
        <?= $tab === $tid ? 'aria-selected="true"' : 'aria-selected="false"' ?>
      ><?= h($label) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if ($tab === 'compte'): ?>
    <div class="card bg-base-200/60 border border-base-content/10 shadow-sm mb-6">
      <div class="card-body gap-4 p-4 sm:p-6">
        <h2 class="card-title text-lg">Mon compte</h2>

        <div class="space-y-3 text-sm profile-info-rows">
          <div class="profile-info-row">
            <span class="text-base-content/60">Adresse e-mail</span>
            <span class="font-medium break-all text-base-content"><?= h((string)$user['email']) ?></span>
          </div>
          <div class="profile-info-row">
            <span class="text-base-content/60">Rôle</span>
            <span class="font-medium text-base-content"><?= h($role) ?></span>
          </div>
          <div class="profile-info-row">
            <span class="text-base-content/60">Statut</span>
            <span class="font-medium text-base-content"><?= h((string)$user['status']) ?></span>
          </div>
        </div>

        <?php if ($pseudoSameAsEmail): ?>
          <div role="alert" class="alert alert-warning text-sm py-3">
            Ton <strong>nom d’utilisateur</strong> est identique à ton <strong>e-mail</strong>. Choisis un pseudo distinct (ex. <code class="text-xs opacity-90">user</code>) pour qu’on t’affiche correctement sur le site.
          </div>
        <?php endif; ?>

        <div class="divider my-1">Nom d’utilisateur</div>
        <p class="text-sm text-base-content/65 -mt-1">
          C’est le nom affiché sur le site (pas ton adresse e-mail). Tu peux le modifier quand tu veux.
        </p>
        <?php if ($okName): ?><div class="alert alert-success text-sm py-2"><?= h($okName) ?></div><?php endif; ?>
        <?php if ($errName): ?><div class="alert alert-error text-sm py-2"><?= h($errName) ?></div><?php endif; ?>
        <form method="post" class="flex flex-col gap-3 max-w-md">
          <input type="hidden" name="action" value="update_name">
          <label class="form-control w-full">
            <span class="label-text text-sm font-semibold">Nom d’utilisateur (pseudo)</span>
            <input class="input input-bordered w-full bg-base-100/80 border-base-content/15" name="full_name" value="<?= h((string)$user['full_name']) ?>" maxlength="120" required autocomplete="username" inputmode="text">
          </label>
          <button class="btn btn-primary btn-sm w-fit" type="submit">Enregistrer</button>
        </form>

        <div class="divider my-1">Mot de passe</div>
        <p class="text-sm text-base-content/65 -mt-1">Le mot de passe n’est jamais affiché.</p>
        <?php if ($okPw): ?><div class="alert alert-success text-sm py-2"><?= h($okPw) ?></div><?php endif; ?>
        <?php if ($errPw): ?><div class="alert alert-error text-sm py-2"><?= h($errPw) ?></div><?php endif; ?>
        <form method="post" class="flex flex-col gap-3 max-w-md">
          <input type="hidden" name="action" value="change_password">
          <?php
          $pwId = 'pw-profile-cur';
          $pwName = 'current_password';
          $pwPlaceholder = 'Mot de passe actuel';
          $pwAutocomplete = 'current-password';
          require __DIR__ . '/../templates/partials/password_field.php';
          ?>
          <?php
          $pwId = 'pw-profile-new';
          $pwName = 'new_password';
          $pwPlaceholder = 'Nouveau mot de passe (8 caractères min.)';
          $pwAutocomplete = 'new-password';
          require __DIR__ . '/../templates/partials/password_field.php';
          ?>
          <?php
          $pwId = 'pw-profile-new2';
          $pwName = 'new_password_confirm';
          $pwPlaceholder = 'Confirmer le nouveau mot de passe';
          $pwAutocomplete = 'new-password';
          require __DIR__ . '/../templates/partials/password_field.php';
          ?>
          <button class="btn btn-primary btn-sm w-fit" type="submit">Mettre à jour le mot de passe</button>
        </form>

        <div class="divider my-2">Session</div>
        <p class="text-sm text-base-content/60 -mt-1">Déconnexion : tu quittes le site sur cet appareil.</p>
        <div class="flex flex-wrap gap-2">
          <a class="btn btn-sm btn-outline border-error/40 text-error hover:bg-error/10" href="index.php?route=logout">Déconnexion</a>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'annonces' && $canAnn): ?>
    <div class="card bg-base-200/60 border border-base-content/10 shadow-sm mb-6">
      <div class="card-body gap-3 p-4 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h2 class="card-title text-lg mb-0">Mes annonces</h2>
          <a class="btn btn-sm btn-primary" href="index.php?route=create_announcement">Nouvelle annonce</a>
        </div>
        <?php if (!$myAnnouncements): ?>
          <p class="text-sm text-base-content/60">Tu n’as pas encore publié d’annonce.</p>
        <?php else: ?>
          <ul class="divide-y divide-base-content/10 border border-base-content/10 rounded-xl overflow-hidden bg-base-100/40">
            <?php foreach ($myAnnouncements as $a): ?>
              <li class="flex flex-wrap items-center justify-between gap-2 px-3 py-2.5 text-sm">
                <div class="min-w-0">
                  <a class="link link-primary font-semibold" href="index.php?route=announcement_detail&id=<?= (int)$a['id'] ?>"><?= h((string)$a['title']) ?></a>
                  <span class="text-base-content/50"> · <?= h(ANNOUNCEMENT_CATEGORIES[(string)$a['category_slug']] ?? (string)$a['category_slug']) ?></span>
                  <span class="text-xs text-base-content/45 block sm:inline sm:before:content-['_·_']">Le <?= h(date_format(new DateTime((string)$a['posted_at']), 'd/m/Y')) ?> — <?= h((string)$a['status']) ?></span>
                </div>
                <?php if (auth_can_manage_announcement($user, $a)): ?>
                  <a class="btn btn-xs btn-outline border-base-content/20 shrink-0" href="index.php?route=edit_announcement&id=<?= (int)$a['id'] ?>">Modifier</a>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'pubs' && auth_has_role('collaborateur', 'admin')): ?>
    <div class="card bg-base-200/60 border border-base-content/10 shadow-sm mb-6">
      <div class="card-body gap-3 p-4 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h2 class="card-title text-lg mb-0">Mes pubs</h2>
          <a class="btn btn-sm btn-primary" href="index.php?route=create_ad">Nouvelle pub</a>
        </div>
        <?php if (!$myAds): ?>
          <p class="text-sm text-base-content/60">Tu n’as pas encore publié de pub.</p>
        <?php else: ?>
          <ul class="divide-y divide-base-content/10 border border-base-content/10 rounded-xl overflow-hidden bg-base-100/40">
            <?php foreach ($myAds as $ad): ?>
              <li class="flex flex-wrap items-center justify-between gap-2 px-3 py-2.5 text-sm">
                <div class="min-w-0">
                  <a class="link link-primary font-semibold" href="index.php?route=ad_detail&id=<?= (int)$ad['id'] ?>"><?= h((string)$ad['title']) ?></a>
                  <span class="text-xs text-base-content/45 block sm:inline sm:before:content-['_·_']">Le <?= h(date_format(new DateTime((string)$ad['posted_at']), 'd/m/Y')) ?> — <?= h((string)$ad['status']) ?></span>
                </div>
                <?php if (auth_can_manage_ad($user, $ad)): ?>
                  <a class="btn btn-xs btn-outline border-base-content/20 shrink-0" href="index.php?route=edit_ad&id=<?= (int)$ad['id'] ?>">Modifier</a>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($tab === 'actualites' && auth_has_role('admin')): ?>
    <div class="card bg-base-200/60 border border-base-content/10 shadow-sm mb-6">
      <div class="card-body gap-3 p-4 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h2 class="card-title text-lg mb-0">Mes actualités</h2>
          <a class="btn btn-sm btn-outline border-base-content/20" href="admin/manage.php?type=news">Espace rédaction</a>
        </div>
        <?php if (!$canNewsCol): ?>
          <p class="text-sm text-base-content/60">
            Pour lier les actualités à ton compte, exécute une fois le script SQL
            <code class="text-xs">database/migrate_news_created_by.sql</code> dans phpMyAdmin.
          </p>
        <?php elseif (!$myNews): ?>
          <p class="text-sm text-base-content/60">Aucune actualité enregistrée avec toi comme auteur.</p>
        <?php else: ?>
          <ul class="divide-y divide-base-content/10 border border-base-content/10 rounded-xl overflow-hidden bg-base-100/40">
            <?php foreach ($myNews as $n): ?>
              <li class="px-3 py-2.5 text-sm flex flex-wrap items-center justify-between gap-2">
                <div class="min-w-0">
                  <a class="link link-primary font-semibold" href="index.php?route=news_detail&id=<?= (int)$n['id'] ?>"><?= h((string)$n['title']) ?></a>
                  <span class="text-xs text-base-content/45"> · <?= h(date_format(new DateTime((string)$n['published_at']), 'd/m/Y')) ?></span>
                </div>
                <a class="btn btn-xs btn-outline border-base-content/20 shrink-0" href="admin/manage.php?type=news&edit_id=<?= (int)$n['id'] ?>">Modifier</a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>
