<?php
/**
 * NexUse — one listing in full.
 *
 * MVC layer: View. Member 1 · Read (detail).
 *
 * @var array<string, mixed>              $listing
 * @var list<array<string, mixed>>        $images
 * @var array{average: float|null, count: int} $ownerRating
 * @var bool                              $isOwner
 * @var array<string, mixed>|null         $existingRequest
 * @var list<array<string, mixed>>        $otherListings, $relatedListings
 */

use App\Core\View;

$type      = (string) $listing['listing_type'];
$ownerId   = (int) $listing['user_id'];
$id        = (int) $listing['listing_id'];
$condition = (string) $listing['item_condition'];
?>

<nav class="breadcrumb">
  <a href="<?= url('/browse') ?>">Browse</a> ›
  <a href="<?= url('/browse?type=' . e($type)) ?>"><?= e(listing_type_label($type)) ?></a> ›
  <span><?= e(excerpt((string) $listing['title'], 40)) ?></span>
</nav>

<div class="listing-detail">

  <div>
    <div class="gallery-main">
      <?php if (!empty($images)): ?>
        <img id="gallery-main-image" src="<?= url((string) $images[0]['image_path']) ?>"
             alt="<?= e($listing['title']) ?>">
      <?php else: ?>
        <span aria-hidden="true"
              style="display:flex;align-items:center;justify-content:center;height:100%;font-size:52px;color:var(--faint);">📦</span>
      <?php endif; ?>
    </div>

    <?php if (count($images) > 1): ?>
      <div class="gallery-thumbs">
        <?php foreach ($images as $i => $image): ?>
          <button type="button" class="<?= $i === 0 ? 'active' : '' ?>"
                  data-gallery-thumb="<?= url((string) $image['image_path']) ?>"
                  aria-label="View image <?= $i + 1 ?>">
            <img src="<?= url((string) $image['image_path']) ?>" alt="">
          </button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="card mt-3">
      <h3>Description</h3>
      <p style="white-space:pre-line;color:var(--ink-2);"><?= e($listing['description']) ?></p>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="flex flex-wrap mb-1">
        <span class="badge badge-<?= e($type) ?>"><?= e(listing_type_label($type)) ?></span>
        <span class="badge badge-<?= e($listing['status']) ?>"><?= e(ucfirst((string) $listing['status'])) ?></span>
      </div>

      <h1 style="font-size:23px;"><?= e($listing['title']) ?></h1>

      <div class="mb-2">
        <?php if ($type === 'sell'): ?>
          <span class="listing-price" style="font-size:26px;"><?= e(money($listing['price'])) ?></span>
          <?php /* A buyer's first question about a second-hand sale is always
                   "new or used?" — so it sits next to the price, not buried in
                   the spec list below. */ ?>
          <span class="condition-flag condition-<?= $condition === 'new' ? 'new' : 'used' ?> flag-inline">
            <?= e(condition_flag($condition)) ?>
          </span>
        <?php elseif ($type === 'rent'): ?>
          <span class="listing-price" style="font-size:26px;"><?= e(money($listing['price'])) ?><span class="per"> per day</span></span>
        <?php else: ?>
          <span class="listing-price free" style="font-size:26px;">Free</span>
        <?php endif; ?>
      </div>

      <ul class="spec-list mb-2">
        <li>
          <span class="k">Condition</span>
          <span class="v">
            <?= e(condition_label($condition)) ?>
            <?php if ($condition !== 'new'): ?><span class="muted">(used)</span><?php endif; ?>
          </span>
        </li>
        <li><span class="k">Category</span><span class="v"><?= e($listing['category_name'] ?? 'Uncategorised') ?></span></li>
        <li><span class="k">Location</span><span class="v"><?= location_or_prompt($listing['location'] ?? null, '—') ?></span></li>
        <li><span class="k">Listed</span><span class="v"><?= e(time_ago((string) $listing['created_at'])) ?></span></li>
      </ul>

      <?php if ($isOwner): ?>
        <div class="alert alert-info mb-2"><span>This is your listing.</span></div>
        <a class="btn btn-secondary btn-block" href="<?= url('/listings/' . $id . '/edit') ?>">Edit listing</a>
        <a class="btn btn-ghost btn-block mt-1" href="<?= url('/requests/received') ?>">View requests received</a>

      <?php elseif ($listing['status'] !== 'available'): ?>
        <div class="alert alert-warning mb-0">
          <span>This item is currently reserved. Check back later, or browse similar items.</span>
        </div>

      <?php elseif ($existingRequest !== null): ?>
        <div class="alert alert-success mb-2">
          <span>You already have a <strong><?= e($existingRequest['status']) ?></strong> request on this item.</span>
        </div>
        <a class="btn btn-secondary btn-block"
           href="<?= url('/requests/' . (int) $existingRequest['request_id']) ?>">View my request</a>

      <?php elseif (!is_logged_in()): ?>
        <a class="btn btn-block btn-lg" href="<?= url('/login') ?>">Sign in to request this</a>
        <p class="small muted center mt-1 mb-0">
          <a href="<?= url('/register') ?>">Create an account</a> — it takes a minute.
        </p>

      <?php else: ?>
        <a class="btn btn-block btn-lg" href="<?= url('/requests/send/' . $id) ?>">
          <?= match ($type) {
              'sell'  => 'Request to buy',
              'rent'  => 'Request to rent',
              'share' => 'Ask to borrow',
              default => 'Request this donation',
          } ?>
        </a>
        <p class="small muted center mt-1 mb-0">
          The owner accepts or declines — nothing is automatic.
        </p>
      <?php endif; ?>

      <?php /* Chat is open to any signed-in member who does not own the item,
               whatever the listing status — questions are useful before and after. */ ?>
      <?php if (is_logged_in() && !$isOwner): ?>
        <a class="btn btn-secondary btn-block mt-1" href="<?= url('/messages/start/' . $id) ?>">
          💬 Message the <?= e(match ($type) {
              'rent', 'share' => 'lender',
              'donate'        => 'donor',
              default         => 'seller',
          }) ?>
        </a>
      <?php endif; ?>
    </div>

    <div class="card mt-2">
      <h3 style="font-size:14px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;">Listed by</h3>

      <div class="flex mb-1">
        <?= avatar(['name' => $listing['owner_name'], 'avatar_path' => $listing['owner_avatar'] ?? null]) ?>
        <div>
          <?php /* The owner's profile page is members-only, so a guest is offered
                   the sign-in route rather than a link that would bounce them. */ ?>
          <?php if (is_logged_in()): ?>
            <a href="<?= url('/users/' . $ownerId) ?>" style="font-weight:600;"><?= e($listing['owner_name']) ?></a>
          <?php else: ?>
            <span style="font-weight:600;"><?= e($listing['owner_name']) ?></span>
          <?php endif; ?>
          <div class="small muted"><?= location_or_prompt($listing['owner_city'] ?? null) ?></div>
        </div>
      </div>

      <?php if (!is_logged_in()): ?>
        <p class="small muted mt-1 mb-0">
          <a href="<?= url('/login') ?>">Sign in</a> to see this member's full profile,
          their other items and where they are based.
        </p>
      <?php endif; ?>

      <div class="rating-line">
        <?php if ($ownerRating['average'] !== null): ?>
          <span class="rating-stars"><?= e(stars($ownerRating['average'])) ?></span>
          <span><?= e((string) $ownerRating['average']) ?> from <?= (int) $ownerRating['count'] ?> review<?= $ownerRating['count'] === 1 ? '' : 's' ?></span>
        <?php else: ?>
          <span class="muted small">No reviews yet</span>
        <?php endif; ?>
      </div>

      <?php if (is_logged_in() && !$isOwner): ?>
        <div class="mt-2">
          <a class="small muted" href="<?= url('/complaints/report?user=' . $ownerId . '&listing=' . $id) ?>">
            Report this listing or user
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php if (!empty($relatedListings)): ?>
  <section class="section mt-3">
    <div class="section-head">
      <div>
        <h2>Related items</h2>
        <p class="muted small mb-0">
          Similar category, exchange type or location — from across NexUse.
        </p>
      </div>
      <a href="<?= url('/browse?category=' . (int) ($listing['category_id'] ?? 0)) ?>">
        More like this →
      </a>
    </div>
    <div class="listing-grid">
      <?php foreach ($relatedListings as $item): ?>
        <?php View::partial('partials.listing_card', ['item' => $item]); ?>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<?php if (!empty($otherListings)): ?>
  <section class="section mt-3">
    <div class="section-head">
      <h2>More from <?= e(explode(' ', (string) $listing['owner_name'])[0]) ?></h2>
      <?php /* Their profile is members-only, so a guest is not offered a link
               that would only bounce them to the sign-in page. */ ?>
      <?php if (is_logged_in()): ?>
        <a href="<?= url('/users/' . $ownerId) ?>">View profile →</a>
      <?php else: ?>
        <a href="<?= url('/login') ?>">Sign in to view profile →</a>
      <?php endif; ?>
    </div>
    <div class="listing-grid">
      <?php foreach ($otherListings as $item): ?>
        <?php View::partial('partials.listing_card', ['item' => $item]); ?>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>
