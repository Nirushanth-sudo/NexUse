<?php
/**
 * NexUse — one conversation.
 *
 * MVC layer: View. Member 2 · messaging.
 *
 * @var array<string, mixed>       $conversation
 * @var list<array<string, mixed>> $messages
 * @var int                        $me
 * @var string                     $otherName
 * @var int                        $otherId
 */

use App\Models\Message;

$cid       = (int) $conversation['conversation_id'];
$listingId = (int) $conversation['listing_id'];
$isBuyer   = (int) $conversation['buyer_id'] === $me;
$type      = (string) $conversation['listing_type'];

$ownerWord = match ($type) {
    'rent'   => 'lender',
    'share'  => 'lender',
    'donate' => 'donor',
    default  => 'seller',
};
?>

<nav class="breadcrumb">
  <a href="<?= url('/messages') ?>">Messages</a> ›
  <span><?= e($otherName) ?></span>
</nav>

<div class="card mb-2">
  <div class="flex flex-wrap" style="justify-content:space-between;align-items:flex-start;">
    <div class="flex">
      <?= avatar([
            'name'        => $otherName,
            'avatar_path' => $isBuyer ? ($conversation['owner_avatar'] ?? null)
                                      : ($conversation['buyer_avatar'] ?? null),
          ]) ?>
      <div>
        <a href="<?= url('/users/' . $otherId) ?>" style="font-weight:600;"><?= e($otherName) ?></a>
        <div class="small muted">
          <?= $isBuyer ? 'The ' . e($ownerWord) : 'Interested in your item' ?>
        </div>
      </div>
    </div>
    <div class="right">
      <span class="badge badge-<?= e($type) ?>"><?= e(listing_type_label($type)) ?></span>
      <div class="small mt-1">
        <a href="<?= url('/listings/' . $listingId) ?>"><?= e($conversation['listing_title']) ?></a>
      </div>
      <?php if (in_array($type, ['sell', 'rent'], true)): ?>
        <div class="small muted"><?= e(money($conversation['listing_price'])) ?><?= $type === 'rent' ? ' / day' : '' ?></div>
      <?php else: ?>
        <div class="small" style="color:var(--green);font-weight:600;">Free</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="chat" id="chat-scroll">
  <?php if (empty($messages)): ?>
    <div class="chat-empty">
      <p class="mb-0">
        No messages yet. Introduce yourself and ask what you need to know —
        when you could collect it, what condition it is in, anything the listing does
        not cover.
      </p>
    </div>
  <?php else: ?>
    <?php $lastDay = null; ?>
    <?php foreach ($messages as $m): ?>
      <?php
      $mine = (int) $m['sender_id'] === $me;
      $day  = date('Y-m-d', strtotime((string) $m['created_at']));
      ?>

      <?php if ($day !== $lastDay): ?>
        <div class="chat-day"><span><?= e(short_date((string) $m['created_at'])) ?></span></div>
        <?php $lastDay = $day; ?>
      <?php endif; ?>

      <div class="bubble-row <?= $mine ? 'mine' : 'theirs' ?>">
        <div class="bubble">
          <p><?= nl2br(e($m['body'])) ?></p>
          <span class="bubble-meta">
            <?= e(date('g:ia', strtotime((string) $m['created_at']))) ?>
            <?php if ($mine && (int) $m['is_read'] === 1): ?> · read<?php endif; ?>
          </span>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<form method="post" action="<?= url('/messages/' . $cid) ?>" class="chat-compose">
  <?= csrf_field() ?>
  <textarea name="body" rows="2" maxlength="<?= Message::MAX_LENGTH ?>"
            placeholder="Write a message to <?= e(explode(' ', $otherName)[0]) ?>…" required></textarea>
  <button type="submit" class="btn">Send</button>
</form>

<p class="small muted center mt-2">
  NexUse never asks for payment details. Arrange money and handover directly, and
  <a href="<?= url('/complaints/report?user=' . $otherId . '&listing=' . $listingId) ?>">report a problem</a>
  if something goes wrong.
</p>

<?php if ($isBuyer && $conversation['listing_status'] === 'available'): ?>
  <div class="card mt-2 center">
    <p class="small muted mb-2">Ready to go ahead? A message is not a request.</p>
    <a class="btn" href="<?= url('/requests/send/' . $listingId) ?>">Send a formal request</a>
  </div>
<?php endif; ?>
