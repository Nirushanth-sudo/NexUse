<?php
/**
 * NexUse — confirm deleting a listing.
 *
 * MVC layer: View. Member 1 · Delete.
 *
 * @var array<string, mixed> $listing
 * @var int                  $liveRequests
 */

$id = (int) $listing['listing_id'];
?>

<div class="card">
  <div class="card-head"><h1 style="font-size:20px;margin:0;">Delete this listing?</h1></div>

  <p class="mb-2">
    You are about to permanently delete
    <strong><?= e($listing['title']) ?></strong>.
    This cannot be undone.
  </p>

  <ul class="spec-list mb-2">
    <li><span class="k">Type</span>
        <span class="v"><?= e(listing_type_label((string) $listing['listing_type'])) ?></span></li>
    <li><span class="k">Status</span>
        <span class="v"><?= e(ucfirst((string) $listing['status'])) ?></span></li>
    <li><span class="k">Posted</span>
        <span class="v"><?= e(short_date((string) $listing['created_at'])) ?></span></li>
  </ul>

  <?php if ($liveRequests > 0): ?>
    <div class="alert alert-warning">
      <span>
        <strong><?= $liveRequests ?></strong> pending or accepted
        request<?= $liveRequests === 1 ? '' : 's' ?> will be deleted with it, and
        <?= $liveRequests === 1 ? 'that person' : 'those people' ?> will not be told why.
        Consider marking the listing <em>Completed</em> instead.
      </span>
    </div>
  <?php endif; ?>

  <div class="alert alert-error">
    <span>Its photos and every request, and any reviews attached to them, are removed too.</span>
  </div>

  <form method="post" action="<?= url('/listings/' . $id . '/delete') ?>">
    <?= csrf_field() ?>
    <div class="form-actions">
      <button type="submit" class="btn btn-danger"
              data-confirm="Permanently delete this listing? This cannot be undone.">
        Yes, delete it
      </button>
      <a class="btn btn-secondary" href="<?= url('/listings/' . $id) ?>">Cancel</a>
      <a class="btn btn-ghost" href="<?= url('/listings/' . $id . '/edit') ?>">Edit instead</a>
    </div>
  </form>
</div>
