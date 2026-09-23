<?php
/**
 * NexUse — 403 page.
 *
 * @var string|null $message
 */
?>
<div class="empty" style="margin-top:40px;">
  <div class="icon">🔒</div>
  <h3>You do not have access to that</h3>
  <p><?= e($message ?? 'That page belongs to someone else, or needs different permissions.') ?></p>
  <div class="btn-row" style="justify-content:center;">
    <a class="btn" href="<?= url('/') ?>">Go home</a>
  </div>
</div>
