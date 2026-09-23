<?php
/**
 * NexUse — notifications.
 *
 * MVC layer: View. Member 4 · Read, and the entry point for Update/Delete.
 *
 * @var list<array<string, mixed>> $notifications
 * @var string                     $filter
 * @var int                        $unread, $total
 */

$icons = [
    'request'   => '📥',
    'accepted'  => '✅',
    'rejected'  => '✖️',
    'withdrawn' => '↩️',
    'returned'  => '📦',
    'review'    => '⭐',
    'complaint' => '🛡️',
    'broadcast' => '📢',
    'system'    => '🔔',
];
?>

<div class="page-head">
  <div>
    <h1>Notifications</h1>
    <p class="subtitle">
      <?= $unread > 0
          ? $unread . ' unread of ' . $total
          : 'All caught up — ' . $total . ' in total' ?>
    </p>
  </div>
  <div class="btn-row">
    <?php if ($unread > 0): ?>
      <form method="post" action="<?= url('/notifications/read-all') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-secondary btn-sm">Mark all read</button>
      </form>
    <?php endif; ?>
    <?php if ($total > $unread): ?>
      <form method="post" action="<?= url('/notifications/clear-read') ?>"
            data-confirm="Delete every notification you have already read?">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--red);">Clear read</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="tabs">
  <a href="<?= url('/notifications') ?>" class="<?= $filter === '' ? 'active' : '' ?>">
    All<span class="count"><?= $total ?></span>
  </a>
  <a href="<?= url('/notifications?filter=unread') ?>" class="<?= $filter === 'unread' ? 'active' : '' ?>">
    Unread<span class="count"><?= $unread ?></span>
  </a>
  <a href="<?= url('/notifications?filter=read') ?>" class="<?= $filter === 'read' ? 'active' : '' ?>">
    Read<span class="count"><?= $total - $unread ?></span>
  </a>
</div>

<?php if (empty($notifications)): ?>
  <div class="empty">
    <div class="icon">🔔</div>
    <h3><?= $filter === 'unread' ? 'Nothing unread' : 'No notifications' ?></h3>
    <p>
      <?= $filter === 'unread'
          ? 'You are all caught up.'
          : 'Activity on your listings, requests and reviews shows up here.' ?>
    </p>
    <a class="btn btn-secondary" href="<?= url('/browse') ?>">Browse items</a>
  </div>
<?php else: ?>
  <div class="card card-tight notif-list">
    <?php foreach ($notifications as $n): ?>
      <?php
      $id     = (int) $n['notification_id'];
      $isRead = (int) $n['is_read'] === 1;
      ?>
      <div class="notif-row<?= $isRead ? '' : ' unread' ?>">
        <span class="notif-dot<?= $isRead ? ' read' : '' ?>"></span>

        <div class="body">
          <strong>
            <?= e($icons[$n['type']] ?? '🔔') ?>
            <?= e($n['title']) ?>
          </strong>
          <?php if (!empty($n['message'])): ?>
            <p><?= e($n['message']) ?></p>
          <?php endif; ?>
          <span class="when"><?= e(time_ago((string) $n['created_at'])) ?></span>
        </div>

        <div class="row-actions">
          <?php if (!empty($n['link'])): ?>
            <form method="post" action="<?= url('/notifications/read/' . $id) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="follow" value="1">
              <button type="submit" class="btn btn-secondary btn-sm">Open</button>
            </form>
          <?php endif; ?>

          <?php if ($isRead): ?>
            <form method="post" action="<?= url('/notifications/unread/' . $id) ?>">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-ghost btn-sm">Unread</button>
            </form>
          <?php else: ?>
            <form method="post" action="<?= url('/notifications/read/' . $id) ?>">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-ghost btn-sm">Mark read</button>
            </form>
          <?php endif; ?>

          <form method="post" action="<?= url('/notifications/dismiss/' . $id) ?>"
                data-confirm="Dismiss this notification?">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--red);"
                    aria-label="Dismiss">✕</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
