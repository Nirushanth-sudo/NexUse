<?php
/**
 * NexUse — one listing card.
 *
 * MVC layer: View partial. Shared by home, browse, profile and my-listings so a
 * listing looks identical everywhere it appears.
 *
 * @var array<string, mixed> $item
 */

use App\Models\ListingImage;

$cardImage = ListingImage::primaryPath((int) $item['listing_id']);
$cardType  = (string) $item['listing_type'];
$cardCond  = (string) ($item['item_condition'] ?? '');
?>
<article class="listing-card">

  <div class="listing-thumb">
    <a href="<?= url('/listings/' . (int) $item['listing_id']) ?>">
      <?php if ($cardImage !== null): ?>
        <img src="<?= url($cardImage) ?>" alt="<?= e($item['title']) ?>" loading="lazy">
      <?php else: ?>
        <span class="placeholder" aria-hidden="true">📦</span>
      <?php endif; ?>
    </a>
    <span class="badge badge-<?= e($cardType) ?> type-badge"><?= e(listing_type_label($cardType)) ?></span>

    <?php /* New or used, stated on the thumbnail for sale items — a buyer should
             not have to open the listing to find out what they are paying for. */ ?>
    <?php if ($cardCond !== '' && shows_condition_flag($cardType)): ?>
      <span class="condition-flag condition-<?= e(condition_flag($cardCond) === 'New' ? 'new' : 'used') ?>"
            title="<?= e(condition_label($cardCond)) ?>">
        <?= e(condition_flag($cardCond)) ?>
      </span>
    <?php endif; ?>
  </div>

  <div class="listing-body">
    <h3>
      <a href="<?= url('/listings/' . (int) $item['listing_id']) ?>"><?= e($item['title']) ?></a>
    </h3>

    <p class="desc"><?= e(excerpt((string) $item['description'], 76)) ?></p>

    <div class="listing-meta">
      <?php if ($cardType === 'sell'): ?>
        <span class="listing-price"><?= e(money($item['price'])) ?></span>
      <?php elseif ($cardType === 'rent'): ?>
        <span class="listing-price"><?= e(money($item['price'])) ?><span class="per"> / day</span></span>
      <?php else: ?>
        <span class="listing-price free">Free</span>
      <?php endif; ?>

      <span><?= location_or_prompt($item['location'] ?? null) ?></span>
    </div>
  </div>

</article>
