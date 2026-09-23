<?php
/**
 * NexUse — my reviews.
 *
 * MVC layer: View. Member 3 · Read.
 *
 * @var string                            $tab
 * @var list<array<string, mixed>>        $received, $written, $pending
 * @var array{average: float|null, count: int} $rating
 */

$tabs = [
    'received' => ['About me',        count($received)],
    'written'  => ['Written by me',   count($written)],
    'pending'  => ['To review',       count($pending)],
];

if (!isset($tabs[$tab])) {
    $tab = 'received';
}
?>

<div class="page-head">
  <div>
    <h1>Reviews</h1>
    <p class="subtitle">
      <?php if ($rating['average'] !== null): ?>
        You are rated <span class="rating-stars"><?= e(stars($rating['average'])) ?></span>
        <?= e((string) $rating['average']) ?> from <?= (int) $rating['count'] ?> review<?= $rating['count'] === 1 ? '' : 's' ?>.
      <?php else: ?>
        Complete an exchange to receive your first review.
      <?php endif; ?>
    </p>
  </div>
</div>

<div class="tabs">
  <?php foreach ($tabs as $key => [$label, $count]): ?>
    <a href="<?= url('/reviews?tab=' . $key) ?>" class="<?= $tab === $key ? 'active' : '' ?>">
      <?= e($label) ?><span class="count"><?= $count ?></span>
    </a>
  <?php endforeach; ?>
</div>

<?php if ($tab === 'pending'): ?>

  <?php if (empty($pending)): ?>
    <div class="empty">
      <div class="icon">✅</div>
      <h3>Nothing waiting</h3>
      <p>When an exchange completes, it appears here so you can review the other person.</p>
      <a class="btn btn-secondary" href="<?= url('/requests/received') ?>">View requests</a>
    </div>
  <?php else: ?>
    <div class="card card-tight">
      <?php foreach ($pending as $p): ?>
        <div class="notif-row" style="display:flex;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid var(--border);">
          <?= avatar(['name' => $p['other_name'], 'avatar_path' => $p['other_avatar'] ?? null]) ?>
          <div style="flex:1;min-width:0;">
            <strong><?= e($p['other_name']) ?></strong>
            <p class="small muted mb-0">
              <?= e(request_type_label((string) $p['request_type'])) ?> ·
              <a href="<?= url('/listings/' . (int) $p['listing_id']) ?>"><?= e($p['listing_title']) ?></a>
            </p>
          </div>
          <a class="btn btn-sm" href="<?= url('/reviews/write/' . (int) $p['request_id']) ?>">Write review</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php else: ?>

  <?php $list = $tab === 'received' ? $received : $written; ?>

  <?php if (empty($list)): ?>
    <div class="empty">
      <div class="icon">⭐</div>
      <h3><?= $tab === 'received' ? 'No reviews about you yet' : 'You have not written any reviews' ?></h3>
      <p>
        <?= $tab === 'received'
            ? 'Once you complete an exchange, the other person can review you.'
            : 'Completed exchanges you can review appear under "To review".' ?>
      </p>
      <?php if ($tab === 'written' && !empty($pending)): ?>
        <a class="btn" href="<?= url('/reviews?tab=pending') ?>">Review an exchange</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="card">
      <?php foreach ($list as $review): ?>
        <div class="review" id="review-<?= (int) $review['review_id'] ?>">
          <div class="review-head">
            <?= $tab === 'received'
                  ? avatar(['name' => $review['reviewer_name'], 'avatar_path' => $review['reviewer_avatar'] ?? null], 'avatar-sm')
                  : avatar(['name' => $review['reviewee_name'], 'avatar_path' => $review['reviewee_avatar'] ?? null], 'avatar-sm') ?>
            <span class="name">
              <?= $tab === 'received' ? e($review['reviewer_name']) : e($review['reviewee_name']) ?>
            </span>
            <span class="rating-stars"><?= e(stars((int) $review['rating'])) ?></span>
            <span class="when"><?= e(time_ago((string) $review['created_at'])) ?></span>
          </div>

          <?php if (!empty($review['comment'])): ?>
            <p><?= e($review['comment']) ?></p>
          <?php endif; ?>

          <div class="flex flex-wrap mt-1" style="justify-content:space-between;">
            <p class="small muted mb-0">
              on <a href="<?= url('/listings/' . (int) $review['listing_id']) ?>"><?= e($review['listing_title']) ?></a>
            </p>

            <?php if ($tab === 'written'): ?>
              <div class="flex gap-sm">
                <a class="btn btn-ghost btn-sm" href="<?= url('/reviews/' . (int) $review['review_id'] . '/edit') ?>">Edit</a>
                <form method="post" action="<?= url('/reviews/' . (int) $review['review_id'] . '/delete') ?>"
                      data-confirm="Delete this review?">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--red);">Delete</button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php endif; ?>
