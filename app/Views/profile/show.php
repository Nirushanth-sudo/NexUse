<?php
/**
 * NexUse — my profile.
 *
 * MVC layer: View. Member 3.
 *
 * @var array<string, mixed>              $user
 * @var array{average: float|null, count: int} $rating
 * @var list<array<string, mixed>>        $reviews
 * @var array<string, int>                $summary
 */
?>

<div class="page-head">
  <div class="profile-head">
    <?= avatar($user, 'avatar-lg') ?>
    <div class="who">
      <h1><?= e($user['name']) ?></h1>
      <p class="subtitle mb-1">
        <?= e($user['city'] ?: 'Sri Lanka') ?> ·
        member since <?= e(short_date((string) $user['created_at'])) ?>
      </p>
      <div class="rating-line">
        <?php if ($rating['average'] !== null): ?>
          <span class="rating-stars"><?= e(stars($rating['average'])) ?></span>
          <span><?= e((string) $rating['average']) ?> from <?= (int) $rating['count'] ?> review<?= $rating['count'] === 1 ? '' : 's' ?></span>
        <?php else: ?>
          <span class="muted small">No reviews yet — complete an exchange to get your first.</span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="btn-row">
    <a class="btn btn-secondary" href="<?= url('/profile/edit') ?>">Edit profile</a>
    <a class="btn btn-ghost" href="<?= url('/profile/password') ?>">Change password</a>
  </div>
</div>

<?php if (!empty($user['bio'])): ?>
  <div class="card mb-2">
    <p class="mb-0" style="white-space:pre-line;color:var(--ink-2);"><?= e($user['bio']) ?></p>
  </div>
<?php endif; ?>

<div class="stat-grid">
  <div class="stat accent-blue">
    <div class="label">Listings</div>
    <div class="value"><?= (int) $summary['listings'] ?></div>
    <div class="foot"><?= (int) $summary['available'] ?> still available</div>
  </div>
  <div class="stat accent-green">
    <div class="label">Completed</div>
    <div class="value"><?= (int) $summary['completed'] ?></div>
    <div class="foot">exchanges finished</div>
  </div>
  <div class="stat accent-violet">
    <div class="label">Requests sent</div>
    <div class="value"><?= (int) $summary['sent'] ?></div>
    <div class="foot">items you asked for</div>
  </div>
  <div class="stat accent-amber">
    <div class="label">Requests received</div>
    <div class="value"><?= (int) $summary['received'] ?></div>
    <div class="foot">people asking you</div>
  </div>
</div>

<div class="card mb-2">
  <div class="card-head"><h2>Your details</h2></div>
  <ul class="spec-list">
    <li><span class="k">Name</span><span class="v"><?= e($user['name']) ?></span></li>
    <li><span class="k">Email</span><span class="v"><?= e($user['email']) ?></span></li>
    <li><span class="k">Phone</span><span class="v"><?= $user['phone'] ? e($user['phone']) : '—' ?></span></li>
    <li><span class="k">City</span><span class="v"><?= $user['city'] ? e($user['city']) : '—' ?></span></li>
    <li><span class="k">Account type</span>
        <span class="v"><span class="badge badge-<?= e($user['role']) ?>"><?= e(ucfirst((string) $user['role'])) ?></span></span></li>
  </ul>
  <p class="field-hint mt-2 mb-0">
    Your email and phone number are only shared with the other party once a request
    has been accepted.
  </p>
</div>

<div class="card">
  <div class="card-head">
    <h2>Reviews about you</h2>
    <a class="small" href="<?= url('/reviews') ?>">All reviews →</a>
  </div>

  <?php if (empty($reviews)): ?>
    <p class="muted small mb-0">
      Nobody has reviewed you yet. Reviews appear here once you complete an exchange.
    </p>
  <?php else: ?>
    <?php foreach (array_slice($reviews, 0, 5) as $review): ?>
      <div class="review">
        <div class="review-head">
          <?= avatar(['name' => $review['reviewer_name'], 'avatar_path' => $review['reviewer_avatar'] ?? null], 'avatar-sm') ?>
          <span class="name"><?= e($review['reviewer_name']) ?></span>
          <span class="rating-stars"><?= e(stars((int) $review['rating'])) ?></span>
          <span class="when"><?= e(time_ago((string) $review['created_at'])) ?></span>
        </div>
        <?php if (!empty($review['comment'])): ?>
          <p><?= e($review['comment']) ?></p>
        <?php endif; ?>
        <p class="small muted mt-1 mb-0">
          on <a href="<?= url('/listings/' . (int) $review['listing_id']) ?>"><?= e($review['listing_title']) ?></a>
        </p>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
