<?php
declare(strict_types=1);

/** @var string|null $attachmentPath */

$path = (isset($attachmentPath) && is_string($attachmentPath) && $attachmentPath !== '')
  ? $attachmentPath
  : '';

$e = static function (string $s): string {
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<figure class="announcement-card-media relative h-36 w-full shrink-0 overflow-hidden bg-base-200/50 sm:h-40">
  <?php if ($path === ''): ?>
    <div class="absolute inset-0 flex flex-col items-center justify-center gap-1 px-4 text-center text-base-content/30" aria-hidden="true">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9 shrink-0 opacity-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      <span class="text-[0.65rem] font-medium uppercase tracking-wide opacity-80">Pas d’aperçu</span>
    </div>
  <?php elseif (announcement_attachment_is_pdf($path)): ?>
    <div class="absolute inset-0 flex items-center justify-center p-3">
      <a class="btn btn-sm btn-outline border-base-content/20 bg-base-200/50 hover:bg-base-200/80" href="<?= $e($path) ?>" target="_blank" rel="noopener noreferrer">PDF joint</a>
    </div>
  <?php elseif (announcement_attachment_is_image($path)): ?>
    <img
      class="h-full w-full object-cover object-center"
      src="<?= $e($path) ?>"
      alt=""
      loading="lazy"
      decoding="async"
    >
  <?php else: ?>
    <div class="absolute inset-0 flex items-center justify-center p-3">
      <a class="btn btn-sm btn-ghost border border-base-content/10" href="<?= $e($path) ?>" target="_blank" rel="noopener noreferrer">Pièce jointe</a>
    </div>
  <?php endif; ?>
</figure>
