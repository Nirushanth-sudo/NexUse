<?php
/**
 * NexUse — message inbox.
 *
 * MVC layer: View. Member 2 · messaging.
 *
 * @var list<array<string, mixed>> $conversations
 * @var int                        $me
 */
?>

<div class="page-head">
  <div>
    <h1>Messages</h1>
    <p class="subtitle">Your conversations with sellers, lenders and donors.</p>
  </div>
  <a class="btn btn-secondary" href="<?= url('/browse') ?>">Browse items</a>
</div>

<?php if (empty($conversations)): ?>
  <div class="empty">
    <div class="icon">💬</div>
    <h3>No conversations yet</h3>
    <p>
      Open any listing and choose <strong>Message the owner</strong> to ask a question
      before you send a request.
    </p>
    <a class="btn" href="<?= url('/browse') ?>">Find something</a>
  </div>
<?php else: ?>
  <div class="card card-tight">
    <?php foreach ($conversations as $c): ?>
      <?php
      $cid       = (int) $c['conversation_id'];
      $isBuyer   = (int) $c['buyer_id'] === $me;
      $otherName = $isBuyer ? $c['owner_name'] : $c['buyer_name'];
      $unread    = (int) $c['unread_count'];
      $lastMine  = (int) $c['last_sender_id'] === $me;
      ?>
      <a class="thread-row<?= $unread > 0 ? ' unread' : '' ?>" href="<?= url('/messages/' . $cid) ?>">
        <?= avatar(['name' => $otherName, 'avatar_path' => $isBuyer ? ($c['owner_avatar'] ?? null) : ($c['buyer_avatar'] ?? null)]) ?>

        <span class="thread-body">
          <span class="thread-top">
            <strong><?= e($otherName) ?></strong>
            <span class="badge badge-<?= e($c['listing_type']) ?>">
              <?= e(listing_type_label((string) $c['listing_type'])) ?>
            </span>
            <span class="when"><?= e(time_ago((string) $c['last_message_at'])) ?></span>
          </span>

          <span class="thread-item"><?= e($c['listing_title']) ?></span>

          <span class="thread-last">
            <?php if (!empty($c['last_body'])): ?>
              <?= $lastMine ? '<em>You:</em> ' : '' ?><?= e(excerpt((string) $c['last_body'], 80)) ?>
            <?php else: ?>
              <em class="muted">No messages yet — say hello.</em>
            <?php endif; ?>
          </span>
        </span>

        <?php if ($unread > 0): ?>
          <span class="thread-count"><?= $unread ?></span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
