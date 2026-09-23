<?php
/**
 * NexUse — send a request.
 *
 * MVC layer: View. Member 2 · Create.
 *
 * @var array<string, mixed>  $listing
 * @var string                $type
 * @var bool                  $returnable
 * @var array<string, string> $errors
 */

$id = (int) $listing['listing_id'];

$heading = match ($type) {
    'buy'    => 'Request to buy this item',
    'rent'   => 'Request to rent this item',
    'borrow' => 'Ask to borrow this item',
    default  => 'Request this donation',
};
?>

<nav class="breadcrumb">
  <a href="<?= url('/browse') ?>">Browse</a> ›
  <a href="<?= url('/listings/' . $id) ?>"><?= e(excerpt((string) $listing['title'], 34)) ?></a> ›
  <span>Send request</span>
</nav>

<div class="page-head">
  <div>
    <h1><?= e($heading) ?></h1>
    <p class="subtitle">The owner decides whether to accept — nothing happens automatically.</p>
  </div>
</div>

<div class="card mb-2">
  <div class="flex flex-wrap" style="justify-content:space-between;">
    <div>
      <span class="badge badge-<?= e($listing['listing_type']) ?> mb-1">
        <?= e(listing_type_label((string) $listing['listing_type'])) ?>
      </span>
      <h3 class="mb-0"><?= e($listing['title']) ?></h3>
      <p class="small muted mb-0">
        Listed by <?= e($listing['owner_name']) ?> · <?= e($listing['location'] ?: 'Sri Lanka') ?>
      </p>
    </div>
    <div class="right">
      <?php if (in_array($listing['listing_type'], ['sell', 'rent'], true)): ?>
        <span class="listing-price"><?= e(money($listing['price'])) ?></span>
        <?php if ($listing['listing_type'] === 'rent'): ?>
          <span class="per small muted">per day</span>
        <?php endif; ?>
      <?php else: ?>
        <span class="listing-price free">Free</span>
      <?php endif; ?>
    </div>
  </div>
</div>

<form method="post" action="<?= url('/requests/send/' . $id) ?>" novalidate>
  <?= csrf_field() ?>

  <div class="card mb-2">
    <?php if ($returnable): ?>
      <div class="card-head"><h2>When do you need it?</h2></div>

      <div class="form-row">
        <div class="form-group">
          <label for="start_date">From</label>
          <input type="date" id="start_date" name="start_date"
                 value="<?= e(old('start_date')) ?>" min="<?= date('Y-m-d') ?>"
                 class="<?= isset($errors['start_date']) ? 'has-error' : '' ?>" required>
          <?php if (isset($errors['start_date'])): ?>
            <p class="field-error"><?= e($errors['start_date']) ?></p>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label for="return_date">Returning on</label>
          <input type="date" id="return_date" name="return_date"
                 value="<?= e(old('return_date')) ?>" min="<?= date('Y-m-d') ?>"
                 class="<?= isset($errors['return_date']) ? 'has-error' : '' ?>" required>
          <?php if (isset($errors['return_date'])): ?>
            <p class="field-error"><?= e($errors['return_date']) ?></p>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($listing['listing_type'] === 'rent'): ?>
        <p class="field-hint">
          The owner may adjust these dates when they accept. Payment is arranged
          directly between the two of you.
        </p>
      <?php else: ?>
        <p class="field-hint">Free to borrow — just bring it back on the agreed day.</p>
      <?php endif; ?>
    <?php else: ?>
      <div class="card-head"><h2>Your message</h2></div>
      <p class="small muted mb-2">
        <?= $type === 'donation'
            ? 'Donors often choose based on who needs the item most, so it helps to say why you are asking.'
            : 'Say when you could collect it, and ask anything you need to know.' ?>
      </p>
    <?php endif; ?>

    <div class="form-group mb-0">
      <label for="message">Message to <?= e(explode(' ', (string) $listing['owner_name'])[0]) ?>
        <span class="optional">(optional)</span></label>
      <textarea id="message" name="message"
                class="<?= isset($errors['message']) ? 'has-error' : '' ?>"
                placeholder="Introduce yourself and say when you could collect it."><?= e(old('message')) ?></textarea>
      <?php if (isset($errors['message'])): ?>
        <p class="field-error"><?= e($errors['message']) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <div class="alert alert-info">
    <span>
      NexUse does not take payment or arrange delivery. Once the owner accepts, the two
      of you agree the handover directly.
    </span>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-lg">Send request</button>
    <a class="btn btn-ghost" href="<?= url('/listings/' . $id) ?>">Cancel</a>
  </div>
</form>
