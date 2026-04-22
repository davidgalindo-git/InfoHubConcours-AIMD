<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/repositories.php';
require_admin();

$selectedAnnouncementId = isset($_GET['announcement_id']) ? (int)$_GET['announcement_id'] : 0;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string)($_POST['action'] ?? '');
  $adminId = !empty($_SESSION['user']) ? (int)$_SESSION['user']['id'] : null;

  if ($action === 'toggle_announcement') {
    $id = (int)($_POST['announcement_id'] ?? 0);
    $status = (string)($_POST['status'] ?? 'visible');
    $next = $status === 'hidden' ? 'visible' : 'hidden';
    db()->prepare('UPDATE announcements SET status = :s WHERE id = :id')->execute(['s' => $next, 'id' => $id]);
    auth_log($adminId, 'toggle_announcement_status', 'announcement', $id, 'Nouveau statut: ' . $next);
    $success = 'Statut annonce mis à jour.';
    $selectedAnnouncementId = $id;
  }
}

$announcements = db()->query('SELECT a.id, a.title, a.status, a.posted_at, u.full_name FROM announcements a LEFT JOIN users u ON u.id = a.created_by ORDER BY a.posted_at DESC LIMIT 100')->fetchAll();

$logRows = [];
if ($selectedAnnouncementId > 0) {
  $stmt = db()->prepare(
    "SELECT l.*, u.full_name AS actor_name
     FROM activity_logs l
     LEFT JOIN users u ON u.id = l.actor_user_id
     WHERE (l.target_type = 'announcement' AND l.target_id = :aid)
        OR (l.details LIKE :needle)
     ORDER BY l.created_at DESC"
  );
  $stmt->execute([
    'aid' => $selectedAnnouncementId,
    'needle' => '%annonce%',
  ]);
  $logRows = $stmt->fetchAll();
}

require __DIR__ . '/header.php';
?>
<section class="my-2">
  <h1 class="text-2xl font-bold mb-2">Modération (admin uniquement)</h1>
  <p class="text-sm text-base-content/60 mb-6">Page cachée pour modérer annonces et comptes, avec logs d'actions.</p>
  <?php if ($success): ?><div class="alert alert-success mb-4 text-sm"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <div class="grid gap-6 xl:grid-cols-2">
    <div class="card bg-base-200/50 border border-base-content/10">
      <div class="card-body">
        <h2 class="card-title text-lg">Annonces</h2>
        <div class="flex flex-col gap-2">
          <?php foreach ($announcements as $a): ?>
            <div class="p-3 rounded-xl border border-base-content/10 flex flex-wrap justify-between gap-2 items-center">
              <div class="min-w-0 flex-1">
                <a class="font-semibold link link-primary break-words" href="../index.php?route=announcement_detail&id=<?= (int)$a['id'] ?>" target="_blank" rel="noopener"><?= htmlspecialchars((string)$a['title']) ?></a>
                <a class="block text-xs text-base-content/55 link mt-0.5" href="moderation.php?announcement_id=<?= (int)$a['id'] ?>">Voir les logs modération</a>
              </div>
              <form method="post">
                <input type="hidden" name="action" value="toggle_announcement">
                <input type="hidden" name="announcement_id" value="<?= (int)$a['id'] ?>">
                <input type="hidden" name="status" value="<?= htmlspecialchars((string)$a['status']) ?>">
                <button class="btn btn-xs <?= (string)$a['status'] === 'hidden' ? 'btn-success' : 'btn-warning' ?>" type="submit">
                  <?= (string)$a['status'] === 'hidden' ? 'Réactiver' : 'Masquer' ?>
                </button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="card bg-base-200/50 border border-base-content/10">
      <div class="card-body gap-2">
        <h2 class="card-title text-lg">Comptes</h2>
        <p class="text-sm text-base-content/65">
          Les suspensions temporisées, la levée de suspension, la suppression de compte et les journaux utilisateur se gèrent dans
          <a class="link link-primary font-semibold" href="users.php">Utilisateurs</a>.
        </p>
      </div>
    </div>
  </div>

  <?php if ($selectedAnnouncementId > 0): ?>
    <div class="card bg-base-200/50 border border-base-content/10 mt-6">
      <div class="card-body">
        <h2 class="card-title text-lg">Logs annonce #<?= $selectedAnnouncementId ?></h2>
        <?php if (!$logRows): ?>
          <p class="text-sm text-base-content/60">Aucun log trouvé.</p>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="table table-zebra table-sm">
              <thead><tr><th>Date</th><th>Acteur</th><th>Action</th><th>Détails</th></tr></thead>
              <tbody>
              <?php foreach ($logRows as $row): ?>
                <tr>
                  <td><?= htmlspecialchars((string)$row['created_at']) ?></td>
                  <td><?= htmlspecialchars((string)($row['actor_name'] ?? 'Système')) ?></td>
                  <td><?= htmlspecialchars((string)$row['action']) ?></td>
                  <td><?= htmlspecialchars((string)($row['details'] ?? '')) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/footer.php'; ?>

