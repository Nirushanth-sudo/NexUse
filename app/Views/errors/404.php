<?php
/**
 * NexUse — 404 page.
 *
 * @var string|null $message
 */
?>
<div class="empty" style="margin-top:40px;">
  <div class="icon">🧭</div>
  <h3>That page could not be found</h3>
  <p><?= e($message ?? 'The address may be mistyped, or the item may have been removed.') ?></p>
  <div class="btn-row" style="justify-content:center;">
    <a class="btn" href="<?= url('/browse') ?>">Browse items</a>
    <a class="btn btn-secondary" href="<?= url('/') ?>">Go home</a>
  </div>
</div>
