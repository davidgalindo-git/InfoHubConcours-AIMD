<?php
declare(strict_types=1);

/** @var string $err_title */
/** @var string $err_text */
/** @var string $err_style neutral|danger */
/** @var bool $err_show_home */
/** @var Throwable|null $err_debug */

require __DIR__ . '/../templates/header.php';

$boxClass = $err_style === 'danger'
  ? 'rounded-2xl border border-dashed border-error/30 bg-error/10 px-5 py-6 text-base-content/80'
  : 'rounded-2xl border border-dashed border-base-content/20 bg-base-200/40 px-5 py-6 text-base-content/70';
$hClass = $err_style === 'danger' ? 'text-lg font-bold text-error mb-2' : 'text-lg font-bold text-base-content mb-2';
?>

<section class="my-6 animate-fade-in min-w-0 max-w-full">
  <div class="<?= htmlspecialchars($boxClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> min-w-0 max-w-full overflow-x-auto">
    <h1 class="<?= htmlspecialchars($hClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= h($err_title) ?></h1>
    <p class="mb-4"><?= h($err_text) ?></p>

    <?php if (!empty($err_show_home)): ?>
      <a class="btn btn-primary transition-transform duration-200 hover:scale-[1.02]" href="index.php?route=home">Retour à l’accueil</a>
    <?php endif; ?>

    <?php if ($err_style === 'danger' && defined('APP_DEBUG') && APP_DEBUG && isset($err_debug) && $err_debug instanceof Throwable): ?>
      <div class="mt-4 rounded-xl bg-base-300/50 border border-base-content/10 p-4 text-left text-sm">
        <p class="font-mono text-error whitespace-pre-wrap break-words"><?= h($err_debug->getMessage()) ?></p>
        <p class="mt-2 text-xs text-base-content/55">
          <?= h($err_debug->getFile()) ?> · ligne <?= (int)$err_debug->getLine() ?>
        </p>
      </div>
      <p class="mt-3 text-xs text-base-content/55">
        Vérifie <code class="text-xs">config.php</code> (<code class="text-xs">DB_*</code>). Si l’erreur parle de la colonne <code class="text-xs">status</code>, exécute dans phpMyAdmin (SQL) le fichier <code class="text-xs">database/migrate_announcements_ads_columns.sql</code> : les tables créées avant la mise à jour n’ont pas été modifiées par un simple réimport de <code class="text-xs">install.sql</code>.
      </p>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/../templates/footer.php'; ?>
