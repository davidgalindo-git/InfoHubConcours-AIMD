<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/repositories.php';

require_admin();

$adminId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
$schemaOk = auth_users_has_suspended_until_column();

$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$viewId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');
  $targetId = (int)($_POST['user_id'] ?? 0);

  if ($targetId <= 0) {
    $error = 'Requête invalide.';
  } else {
    $stmt = db()->prepare('SELECT id, role, status FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $targetId]);
    $target = $stmt->fetch();
    if (!$target) {
      $error = 'Utilisateur introuvable.';
    } elseif ((string)$target['role'] === 'admin') {
      $error = 'Action impossible sur un compte administrateur.';
    } elseif ($action === 'suspend') {
      if (!$schemaOk) {
        $error = 'Colonne suspended_until absente : exécute database/migrate_users_suspension.sql puis réessaie.';
      } else {
        $kind = (string)($_POST['suspend_kind'] ?? 'timed');
        $permanent = $kind === 'permanent';
        $minutes = 0;
        if (!$permanent) {
          $n = max(1, (int)($_POST['duration_n'] ?? 1));
          $unit = (string)($_POST['duration_unit'] ?? 'hours');
          if ($unit === 'days') {
            $minutes = min($n * 1440, 365 * 1440);
          } else {
            $minutes = min($n * 60, 365 * 1440);
          }
        }
        db()->beginTransaction();
        try {
          if ($permanent) {
            db()->prepare(
              "UPDATE users SET status = 'suspended', suspended_until = NULL WHERE id = :id AND role <> 'admin'"
            )->execute(['id' => $targetId]);
            auth_log($adminId, 'suspend_user', 'user', $targetId, auth_suspend_activity_details(true, null, null));
          } else {
            $uStmt = db()->prepare('SELECT DATE_ADD(NOW(), INTERVAL :m MINUTE) AS until_at');
            $uStmt->execute(['m' => $minutes]);
            $until = (string)$uStmt->fetchColumn();
            db()->prepare(
              "UPDATE users SET status = 'suspended', suspended_until = DATE_ADD(NOW(), INTERVAL :m MINUTE) WHERE id = :id AND role <> 'admin'"
            )->execute(['m' => $minutes, 'id' => $targetId]);
            auth_log($adminId, 'suspend_user', 'user', $targetId, auth_suspend_activity_details(false, $minutes, $until));
          }
          db()->commit();
          $success = $permanent ? 'Compte suspendu (sans date de fin automatique).' : 'Compte suspendu jusqu’à la date calculée par le serveur.';
        } catch (Throwable $e) {
          db()->rollBack();
          $error = 'Impossible d’appliquer la suspension.';
        }
      }
    } elseif ($action === 'unsuspend') {
      if (!$schemaOk) {
        $error = 'Colonne suspended_until absente : exécute database/migrate_users_suspension.sql puis réessaie.';
      } else {
        db()->prepare(
          "UPDATE users SET status = 'active', suspended_until = NULL WHERE id = :id AND role <> 'admin'"
        )->execute(['id' => $targetId]);
        auth_log($adminId, 'unsuspend_user', 'user', $targetId, 'mode=manual');
        $success = 'Suspension levée.';
      }
    } elseif ($action === 'delete_user') {
      if ($targetId === $adminId) {
        $error = 'Tu ne peux pas supprimer ton propre compte ici.';
      } else {
        db()->beginTransaction();
        try {
          db()->prepare('UPDATE announcements SET created_by = NULL WHERE created_by = :id')->execute(['id' => $targetId]);
          db()->prepare('UPDATE ads SET created_by = NULL WHERE created_by = :id')->execute(['id' => $targetId]);
          if (db_table_has_column('news', 'created_by')) {
            db()->prepare('UPDATE news SET created_by = NULL WHERE created_by = :id')->execute(['id' => $targetId]);
          }
          db()->prepare('UPDATE user_invites SET created_by = NULL WHERE created_by = :id')->execute(['id' => $targetId]);
          db()->prepare('UPDATE activity_logs SET actor_user_id = NULL WHERE actor_user_id = :id')->execute(['id' => $targetId]);
          auth_log($adminId, 'delete_user', 'user', $targetId, 'Compte supprimé par admin');
          $del = db()->prepare("DELETE FROM users WHERE id = :id AND role <> 'admin'");
          $del->execute(['id' => $targetId]);
          if ($del->rowCount() < 1) {
            db()->rollBack();
            $error = 'Suppression impossible (compte introuvable ou protégé).';
          } else {
            db()->commit();
            $success = 'Compte supprimé.';
            if ($viewId === $targetId) {
              header('Location: users.php');
              exit;
            }
          }
        } catch (Throwable $e) {
          db()->rollBack();
          $error = 'Suppression impossible (contrainte base de données).';
        }
      }
    } else {
      $error = 'Action inconnue.';
    }
  }
}

$whereSql = '1=1';
$whereParams = [];
if ($q !== '') {
  $whereSql .= ' AND (full_name LIKE :q OR email LIKE :q)';
  $whereParams['q'] = '%' . $q . '%';
}

$countStmt = db()->prepare('SELECT COUNT(*) FROM users WHERE ' . $whereSql);
$countStmt->execute($whereParams);
$totalUsers = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalUsers / $perPage));
if ($page > $totalPages) {
  $page = $totalPages;
  $offset = ($page - 1) * $perPage;
}

$cols = 'id, full_name, email, role, status, created_at';
if ($schemaOk) {
  $cols .= ', suspended_until';
}
$listSql = 'SELECT ' . $cols . ' FROM users WHERE ' . $whereSql . ' ORDER BY created_at DESC LIMIT :lim OFFSET :off';
$listStmt = db()->prepare($listSql);
foreach ($whereParams as $k => $v) {
  $listStmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
}
$listStmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':off', $offset, PDO::PARAM_INT);
$listStmt->execute();
$userRows = $listStmt->fetchAll();

$detailUser = null;
$logRows = [];
$annExtras = [];
$adExtras = [];
$newsExtras = [];
$suspStats = ['suspension_count' => 0, 'planned_minutes' => 0];

if ($viewId > 0) {
  $du = db()->prepare('SELECT ' . $cols . ' FROM users WHERE id = :id LIMIT 1');
  $du->execute(['id' => $viewId]);
  $detailUser = $du->fetch() ?: null;
  if ($detailUser) {
    $suspStats = auth_user_suspension_stats($viewId);
    $lg = db()->prepare(
      'SELECT l.*, u.full_name AS actor_name
       FROM activity_logs l
       LEFT JOIN users u ON u.id = l.actor_user_id
       WHERE l.actor_user_id = :uid OR (l.target_type = \'user\' AND l.target_id = :uid2)
       ORDER BY l.created_at DESC
       LIMIT 250'
    );
    $lg->execute(['uid' => $viewId, 'uid2' => $viewId]);
    $logRows = $lg->fetchAll();

    $annIds = [];
    $adIds = [];
    $newsIds = [];
    foreach ($logRows as $lr) {
      $tid = isset($lr['target_id']) ? (int)$lr['target_id'] : 0;
      if ($tid <= 0) {
        continue;
      }
      $tt = (string)($lr['target_type'] ?? '');
      if ($tt === 'announcement') {
        $annIds[$tid] = true;
      } elseif ($tt === 'ad') {
        $adIds[$tid] = true;
      } elseif ($tt === 'news') {
        $newsIds[$tid] = true;
      }
    }
    if ($annIds) {
      $in = implode(',', array_map('intval', array_keys($annIds)));
      foreach (db()->query('SELECT id, title, content FROM announcements WHERE id IN (' . $in . ')')->fetchAll() as $r) {
        $annExtras[(int)$r['id']] = $r;
      }
    }
    if ($adIds) {
      $in = implode(',', array_map('intval', array_keys($adIds)));
      foreach (db()->query('SELECT id, title, content FROM ads WHERE id IN (' . $in . ')')->fetchAll() as $r) {
        $adExtras[(int)$r['id']] = $r;
      }
    }
    if ($newsIds) {
      $in = implode(',', array_map('intval', array_keys($newsIds)));
      foreach (db()->query('SELECT id, title, content FROM news WHERE id IN (' . $in . ')')->fetchAll() as $r) {
        $newsExtras[(int)$r['id']] = $r;
      }
    }
  }
}

function users_excerpt(string $text, int $max = 220): string
{
  $t = trim(preg_replace('/\s+/', ' ', $text));
  if (function_exists('mb_strlen') && function_exists('mb_substr')) {
    if (mb_strlen($t) <= $max) {
      return $t;
    }
    return mb_substr($t, 0, $max) . '…';
  }
  if (strlen($t) <= $max) {
    return $t;
  }
  return substr($t, 0, $max) . '…';
}

function users_log_context(array $lr, array $annExtras, array $adExtras, array $newsExtras): string
{
  $tid = isset($lr['target_id']) ? (int)$lr['target_id'] : 0;
  $tt = (string)($lr['target_type'] ?? '');
  if ($tid <= 0) {
    return '';
  }
  if ($tt === 'announcement' && isset($annExtras[$tid])) {
    $r = $annExtras[$tid];
    return 'Annonce : « ' . htmlspecialchars((string)$r['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
      . ' » — ' . htmlspecialchars(users_excerpt(strip_tags((string)$r['content'])), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
  if ($tt === 'ad' && isset($adExtras[$tid])) {
    $r = $adExtras[$tid];
    return 'Pub : « ' . htmlspecialchars((string)$r['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
      . ' » — ' . htmlspecialchars(users_excerpt(strip_tags((string)$r['content'])), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
  if ($tt === 'news' && isset($newsExtras[$tid])) {
    $r = $newsExtras[$tid];
    return 'Actualité : « ' . htmlspecialchars((string)$r['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
      . ' » — ' . htmlspecialchars(users_excerpt(strip_tags((string)$r['content'])), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
  return '';
}

require __DIR__ . '/header.php';

$qsBase = [];
if ($q !== '') {
  $qsBase['q'] = $q;
}
$qsPage = static function (int $p) use ($qsBase): string {
  $qs = $qsBase;
  if ($p > 1) {
    $qs['page'] = (string)$p;
  }
  return $qs ? '?' . http_build_query($qs) : '';
};

?>
<section class="my-2 min-w-0">
  <h1 class="text-2xl font-bold mb-2">Utilisateurs</h1>
  <p class="text-sm text-base-content/60 mb-4 max-w-3xl">
    Recherche, suspension paramétrée (durée calculée côté serveur MySQL), levée de suspension, suppression de compte.
    Les durées cumulées affichées proviennent des suspensions temporaires enregistrées dans les journaux.
  </p>

  <?php if (!$schemaOk): ?>
    <div role="alert" class="alert alert-warning mb-4 text-sm">
      Migration requise : importe <code class="text-xs">database/migrate_users_suspension.sql</code> pour activer les suspensions temporisées.
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div role="alert" class="alert alert-error mb-4 text-sm"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div role="alert" class="alert alert-success mb-4 text-sm"><?= htmlspecialchars($success, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
  <?php endif; ?>

  <?php if ($viewId > 0 && $detailUser): ?>
    <div class="mb-6 flex flex-wrap items-center gap-2">
      <a class="btn btn-sm btn-ghost border border-base-content/10" href="users.php<?= htmlspecialchars($qsPage(1) ?: '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">← Liste</a>
    </div>

    <div class="card bg-base-200/50 border border-base-content/10 mb-6">
      <div class="card-body gap-2">
        <h2 class="card-title text-lg flex-wrap">
          <?= htmlspecialchars((string)$detailUser['full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
          <span class="text-sm font-normal text-base-content/60"><?= htmlspecialchars((string)$detailUser['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
        </h2>
        <p class="text-sm text-base-content/70">
          Rôle : <strong><?= htmlspecialchars((string)$detailUser['role'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
          · Statut : <strong><?= htmlspecialchars((string)$detailUser['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
          <?php if ($schemaOk && !empty($detailUser['suspended_until']) && (string)$detailUser['suspended_until'] !== '0000-00-00 00:00:00'): ?>
            · Fin suspension auto : <strong><?= htmlspecialchars((string)$detailUser['suspended_until'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong> (serveur)
          <?php endif; ?>
        </p>
        <p class="text-sm text-base-content/65">
          Suspensions enregistrées : <strong><?= (int)$suspStats['suspension_count'] ?></strong>
          · Durée totale planifiée (suspensions temporisées) :
          <strong><?= (int)$suspStats['planned_minutes'] ?></strong> min
          <?php
            $pm = (int)$suspStats['planned_minutes'];
            $approx = $pm >= 1440
              ? '≈ ' . round($pm / 1440, 1) . ' j'
              : '≈ ' . round($pm / 60, 1) . ' h';
          ?>
          (<span class="whitespace-nowrap"><?= htmlspecialchars($approx, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>)
        </p>

        <?php if ((string)$detailUser['role'] !== 'admin'): ?>
          <div class="flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-end mt-2">
            <form method="post" class="flex flex-wrap items-end gap-2 rounded-xl border border-base-content/10 bg-base-100/40 p-3">
              <input type="hidden" name="user_id" value="<?= (int)$detailUser['id'] ?>">
              <input type="hidden" name="action" value="suspend">
              <span class="text-xs font-bold text-base-content/55 w-full">Suspendre</span>
              <label class="form-control">
                <span class="label-text text-xs">Type</span>
                <select name="suspend_kind" class="select select-bordered select-sm bg-base-100/70">
                  <option value="timed">Durée</option>
                  <option value="permanent">Sans fin auto</option>
                </select>
              </label>
              <label class="form-control">
                <span class="label-text text-xs">Valeur</span>
                <input class="input input-bordered input-sm w-20 bg-base-100/70" type="number" name="duration_n" value="24" min="1" max="365">
              </label>
              <label class="form-control">
                <span class="label-text text-xs">Unité</span>
                <select name="duration_unit" class="select select-bordered select-sm bg-base-100/70">
                  <option value="hours">Heures</option>
                  <option value="days">Jours</option>
                </select>
              </label>
              <button class="btn btn-sm btn-warning" type="submit" onclick="return confirm('Confirmer la suspension ?');">Appliquer</button>
            </form>

            <form method="post" class="flex flex-wrap gap-2 rounded-xl border border-base-content/10 bg-base-100/40 p-3">
              <input type="hidden" name="user_id" value="<?= (int)$detailUser['id'] ?>">
              <input type="hidden" name="action" value="unsuspend">
              <button class="btn btn-sm btn-success" type="submit" <?= !$schemaOk ? 'disabled' : '' ?>>Lever la suspension</button>
            </form>

            <form method="post" class="flex flex-wrap gap-2 rounded-xl border border-error/25 bg-error/5 p-3" onsubmit="return confirm('Supprimer définitivement ce compte et détacher son contenu ?');">
              <input type="hidden" name="user_id" value="<?= (int)$detailUser['id'] ?>">
              <input type="hidden" name="action" value="delete_user">
              <button class="btn btn-sm btn-outline border-error/50 text-error" type="submit">Supprimer le compte</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card bg-base-200/50 border border-base-content/10">
      <div class="card-body gap-3">
        <h3 class="card-title text-base">Journal &amp; contenus liés</h3>
        <?php if (!$logRows): ?>
          <p class="text-sm text-base-content/60">Aucune entrée.</p>
        <?php else: ?>
          <div class="overflow-x-auto w-full">
            <table class="table table-zebra table-sm w-full min-w-[640px]">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Acteur</th>
                  <th>Action</th>
                  <th>Cible</th>
                  <th>Détails</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($logRows as $lr): ?>
                <?php
                  $ctx = users_log_context($lr, $annExtras, $adExtras, $newsExtras);
                  $pub = '';
                  $tt = (string)($lr['target_type'] ?? '');
                  $tid = isset($lr['target_id']) ? (int)$lr['target_id'] : 0;
                  if ($tt === 'announcement' && $tid > 0) {
                    $pub = '../index.php?route=announcement_detail&id=' . $tid;
                  } elseif ($tt === 'ad' && $tid > 0) {
                    $pub = '../index.php?route=ad_detail&id=' . $tid;
                  } elseif ($tt === 'news' && $tid > 0) {
                    $pub = '../index.php?route=news_detail&id=' . $tid;
                  }
                ?>
                <tr>
                  <td class="whitespace-nowrap text-xs"><?= htmlspecialchars((string)$lr['created_at'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                  <td class="text-xs"><?= htmlspecialchars((string)($lr['actor_name'] ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                  <td class="text-xs font-mono"><?= htmlspecialchars((string)$lr['action'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                  <td class="text-xs">
                    <?php if ($pub !== ''): ?>
                      <a class="link link-primary" href="<?= htmlspecialchars($pub, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($tt . ' #' . $tid, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a>
                    <?php elseif ($tt !== '' && $tid > 0): ?>
                      <?= htmlspecialchars($tt . ' #' . $tid, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                    <?php else: ?>
                      —
                    <?php endif; ?>
                  </td>
                  <td class="text-xs max-w-md">
                    <div><?= htmlspecialchars((string)($lr['details'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                    <?php if ($ctx !== ''): ?>
                      <div class="mt-1 text-base-content/70"><?= $ctx ?></div>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  <?php elseif ($viewId > 0): ?>
    <div class="alert alert-warning mb-4">Utilisateur introuvable.</div>
    <a class="btn btn-sm btn-ghost" href="users.php">← Liste</a>

  <?php else: ?>

    <form method="get" class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-end mb-4">
      <label class="form-control w-full sm:max-w-md">
        <span class="label-text text-xs font-bold text-base-content/60">Recherche (nom ou e-mail)</span>
        <input class="input input-bordered input-sm bg-base-100/70" type="search" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="ex. dupont ou @eduvaud.ch">
      </label>
      <button class="btn btn-sm btn-primary" type="submit">Filtrer</button>
      <?php if ($q !== ''): ?>
        <a class="btn btn-sm btn-ghost" href="users.php">Réinitialiser</a>
      <?php endif; ?>
    </form>

    <p class="text-xs text-base-content/55 mb-2"><?= (int)$totalUsers ?> compte(s) · page <?= (int)$page ?> / <?= (int)$totalPages ?></p>

    <div class="overflow-x-auto rounded-xl border border-base-content/10">
      <table class="table table-zebra table-sm w-full min-w-[720px]">
        <thead>
          <tr>
            <th>Nom</th>
            <th>E-mail</th>
            <th>Rôle</th>
            <th>Statut</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($userRows as $u): ?>
          <tr>
            <td>
              <a class="link link-hover font-semibold" href="users.php?id=<?= (int)$u['id'] ?><?= $q !== '' ? '&amp;q=' . rawurlencode($q) : '' ?>">
                <?= htmlspecialchars((string)$u['full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
              </a>
            </td>
            <td class="text-xs sm:text-sm"><?= htmlspecialchars((string)$u['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)$u['role'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
            <td class="text-xs">
              <?= htmlspecialchars((string)$u['status'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
              <?php
                $su = $schemaOk ? (string)($u['suspended_until'] ?? '') : '';
                if ($su !== '' && $su !== '0000-00-00 00:00:00'):
              ?>
                <div class="text-base-content/55">jusqu’à <?= htmlspecialchars($su, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
              <?php endif; ?>
            </td>
            <td class="text-right">
              <a class="btn btn-xs btn-ghost border border-base-content/10" href="users.php?id=<?= (int)$u['id'] ?>">Fiche / logs</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <div class="join mt-4 flex flex-wrap justify-center gap-1">
        <?php for ($pi = 1; $pi <= $totalPages; $pi++): ?>
          <?php
            $qs = $qsBase;
            if ($pi > 1) {
              $qs['page'] = (string)$pi;
            }
            $href = 'users.php' . ($qs ? '?' . http_build_query($qs) : '');
          ?>
          <a class="join-item btn btn-sm <?= $pi === $page ? 'btn-active' : '' ?>" href="<?= htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= $pi ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</section>

<?php require __DIR__ . '/footer.php'; ?>
