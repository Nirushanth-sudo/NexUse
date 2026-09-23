<?php
/**
 * NexUse — edit a review.
 *
 * MVC layer: View. Member 3 · Update.
 *
 * @var array<string, mixed>  $review
 * @var array<string, string> $errors
 */

$id       = (int) $review['review_id'];
$selected = (int) old('rating', (int) $review['rating']);
?>

<div class="card">
  <div class="card-head"><h1 style="font-size:20px;margin:0;">Edit your review</h1></div>

  <p class="small muted mb-2">
    About <strong><?= e($review['reviewee_name']) ?></strong> ·
    <a href="<?= url('/listings/' . (int) $review['listing_id']) ?>"><?= e($review['listing_title']) ?></a>
    · written <?= e(time_ago((string) $review['created_at'])) ?>
  </p>

  <form method="post" action="<?= url('/reviews/' . $id . '/edit') ?>" novalidate>
    <?= csrf_field() ?>

    <div class="form-group">
      <label>Rating</label>
      <div class="star-input">
        <?php for ($value = 5; $value >= 1; $value--): ?>
          <input type="radio" id="rating-<?= $value ?>" name="rating" value="<?= $value ?>"
                 <?= $selected === $value ? 'checked' : '' ?>>
          <label for="rating-<?= $value ?>" title="<?= $value ?> star<?= $value === 1 ? '' : 's' ?>">★</label>
        <?php endfor; ?>
      </div>
      <?php if (isset($errors['rating'])): ?>
        <p class="field-error"><?= e($errors['rating']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="comment">Your review <span class="optional">(optional)</span></label>
      <textarea id="comment" name="comment"
                class="<?= isset($errors['comment']) ? 'has-error' : '' ?>"><?= e(old('comment', (string) $review['comment'])) ?></textarea>
      <?php if (isset($errors['comment'])): ?>
        <p class="field-error"><?= e($errors['comment']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn">Save changes</button>
      <a class="btn btn-ghost" href="<?= url('/reviews?tab=written') ?>">Cancel</a>
    </div>
  </form>

  <hr>

  <form method="post" action="<?= url('/reviews/' . $id . '/delete') ?>"
        data-confirm="Delete this review permanently?">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-danger btn-sm">Delete this review</button>
  </form>
</div>
