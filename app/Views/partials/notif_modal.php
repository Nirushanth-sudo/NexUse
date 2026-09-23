<?php
/**
 * NexUse — the notifications dialog, opened from "Notification" in the header.
 *
 * MVC layer: View partial. Member 4. Rendered once by the layout, for signed-in
 * members only.
 *
 * A centred dialog, the same as the donation one. Works with JavaScript off:
 * the header button is a link to #notifications, and CSS :target shows this
 * overlay. app.js upgrades it — Escape, focus kept inside, page scroll locked.
 *
 * @var list<array<string, mixed>> $recent
 * @var int                        $unread
 */
?>
<div class="modal notif-modal" id="notifications" role="dialog" aria-modal="true" aria-labelledby="notif-title">
  <a class="modal-backdrop" href="#" data-modal-close tabindex="-1" aria-hidden="true"></a>

  <div class="modal-panel notif-panel">
    <a class="modal-close" href="#" data-modal-close aria-label="Close">×</a>

    <div class="notif-head">
      <h2 id="notif-title">Notifications</h2>
      <?php if ($unread > 0): ?>
        <span class="count"><?= (int) $unread ?> unread</span>
        <form method="post" action="<?= url('/notifications/read-all') ?>" class="inline-form">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-ghost btn-sm">Mark all read</button>
        </form>
      <?php endif; ?>
    </div>

    <div class="notif-body">
      <?php if (empty($recent)): ?>
        <p class="notif-empty mb-0">
          Nothing yet. Activity on your listings and requests shows up here.
        </p>
      <?php else: ?>
        <?php foreach ($recent as $n): ?>
          <a class="notif-item<?= $n['is_read'] ? '' : ' unread' ?>"
             href="<?= $n['link'] ? url((string) $n['link']) : url('/notifications') ?>">
            <strong><?= e($n['title']) ?></strong>
            <?= e(excerpt((string) $n['message'], 140)) ?>
            <span class="when"><?= e(time_ago((string) $n['created_at'])) ?></span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="notif-foot">
      <a class="btn btn-sm" href="<?= url('/notifications') ?>">View all notifications</a>
      <a class="btn btn-secondary btn-sm" href="#" data-modal-close>Close</a>
    </div>
  </div>
</div>
