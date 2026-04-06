<?php require __DIR__ . '/../templates/header.php'; ?>

<section class="my-6 animate-fade-in">
  <div class="rounded-2xl border border-dashed border-error/30 bg-error/10 px-5 py-6 text-base-content/80">
    <h1 class="text-lg font-bold text-error mb-2">Erreur serveur</h1>
    <p>Une erreur est survenue. Vérifie la configuration de la base de données.</p>

    <?php if (defined('APP_DEBUG') && APP_DEBUG && isset($app_exception) && $app_exception instanceof Throwable): ?>
      <div class="mt-4 rounded-xl bg-base-300/50 border border-base-content/10 p-4 text-left text-sm">
        <p class="font-mono text-error whitespace-pre-wrap break-words"><?= h($app_exception->getMessage()) ?></p>
        <p class="mt-2 text-xs text-base-content/55">
          <?= h($app_exception->getFile()) ?> · ligne <?= (int)$app_exception->getLine() ?>
        </p>
      </div>
      <p class="mt-3 text-xs text-base-content/55">
        Sous XAMPP : la base doit exister, <code class="text-xs">DB_NAME</code> / <code class="text-xs">DB_USER</code> / <code class="text-xs">DB_PASS</code> dans <code class="text-xs">config.php</code> doivent correspondre à MySQL (souvent <code class="text-xs">DB_PASS</code> vide pour root). Réimporte <code class="text-xs">database/schema.sql</code> puis <code class="text-xs">database/seed.sql</code> dans phpMyAdmin.
      </p>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>
