<?php
/**
 * NexUse — general error page.
 *
 * @var string|null $message
 * @var int|null    $status
 */
?>
<div class="empty" style="margin-top:40px;">
  <div class="icon">⚠️</div>
  <h3>Something went wrong</h3>
  <p><?= e($message ?? 'The request could not be completed. Please try again.') ?></p>
  <div class="btn-row" style="justify-content:center;">
    <a class="btn" href="<?= url('/') ?>">Go home</a>
  </div>
</div>
