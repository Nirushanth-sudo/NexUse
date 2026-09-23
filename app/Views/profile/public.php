<?php
/**
 * NexUse — somebody else's public profile.
 *
 * MVC layer: View. Member 3.
 *
 * @var array<string, mixed>              $profile
 * @var array{average: float|null, count: int} $rating
 * @var array<int, int>                   $distribution
 * @var list<array<string, mixed>>        $reviews
 * @var list<array<string, mixed>>        $listings
 * @var int                               $completed
 */

use App\Core\View;

$id = (int) $profile['user_id'];

/* Bars are drawn as a share of the total number of reviews, not of the biggest
   bucket — so "3 of 4 reviews were five star" reads as 75%, and two buckets with
   different counts can never come out the same width. */
$totalRatings = max(1, array_sum($distribution));
?>

<div class="page-head">
  <div class="profile-head">
    <?= avatar($profile, 'avatar-lg') ?>
    <div class="who">
      <h1><?= e($profile['name']) ?></h1>
      <p class="subtitle mb-1">
        <?= e($profile['city'] ?: 'Sri Lanka') ?> ·
        member since <?= e(short_date((string) $profile['created_at'])) ?> ·
        <?= (int) $completed ?> completed exchange<?= $completed === 1 ? '' : 's' ?>
      </p>
      <div class="rating-line">
        <?php if ($rating['average'] !== null): ?>
          <span class="rating-stars"><?= e(stars($rating['average'])) ?></span>
          <span><?= e((string) $rating['average']) ?> from <?= (int) $rating['count'] ?> review<?= $rating['count'] === 1 ? '' : 's' ?></span>
        <?php else: ?>
          <span class="muted small">No reviews yet</span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php if (is_logged_in()): ?>
    <a class="btn btn-ghost btn-sm" href="<?= url('/complaints/report?user=' . $id) ?>">Report this member</a>
  <?php endif; ?>
</div>

<?php if (!empty($profile['bio'])): ?>
  <div class="card mb-3">
    <p class="mb-0" style="white-space:pre-line;color:var(--ink-2);"><?= e($profile['bio']) ?></p>
  </div>
<?php endif; ?>

<div class="split">
  <aside>
    <div class="card">
      <h3 style="font-size:14px;">Rating breakdown</h3>
      <?php if ($rating['count'] === 0): ?>
        <p class="small muted mb-0">No ratings yet.</p>
      <?php else: ?>
        <div class="bar-chart">
          <?php for ($stars = 5; $stars >= 1; $stars--): ?>
            <?php
              $count   = $distribution[$stars];
              $percent = (int) round($count / $totalRatings * 100);
            ?>
            <div class="bar-row" style="grid-template-columns:48px 1fr 58px;"
                 title="<?= $count ?> of <?= (int) $rating['count'] ?> review<?= $rating['count'] === 1 ? '' : 's' ?> gave <?= $stars ?> star<?= $stars === 1 ? '' : 's' ?>">
              <span class="bar-label"><?= $stars ?> star</span>
              <span class="bar-track">
                <span class="bar-fill amber<?= $count === 0 ? ' is-zero' : '' ?>"
                      style="width:<?= $percent ?>%;"></span>
              </span>
              <span class="bar-value"><?= $count ?> <span class="muted">(<?= $percent ?>%)</span></span>
            </div>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    </div>
  </aside>

  <div>
    <?php if (!empty($listings)): ?>
      <section class="section">
        <div class="section-head">
          <h2>Available from <?= e(explode(' ', (string) $profile['name'])[0]) ?></h2>
        </div>
        <div class="listing-grid">
          <?php foreach ($listings as $item): ?>
            <?php View::partial('partials.listing_card', ['item' => $item]); ?>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <section class="section">
      <div class="section-head"><h2>Reviews</h2></div>

      <div class="card">
        <?php if (empty($reviews)): ?>
          <p class="muted small mb-0">No reviews yet.</p>
        <?php else: ?>
          <?php foreach ($reviews as $review): ?>
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
    </section>
  </div>
</div>
