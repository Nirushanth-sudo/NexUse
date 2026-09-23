<?php
/**
 * NexUse — write a review.
 *
 * MVC layer: View. Member 3 · Create.
 *
 * @var array<string, mixed>  $request
 * @var string                $revieweeName
 * @var array<string, string> $errors
 */

$requestId = (int) $request['request_id'];
$selected  = (int) old('rating', 0);
?>

<div class="card">
  <div class="card-head"><h1 style="font-size:20px;margin:0;">Review <?= e($revieweeName) ?></h1></div>

  <p class="small muted mb-2">
    <?= e(request_type_label((string) $request['request_type'])) ?> ·
    <a href="<?= url('/listings/' . (int) $request['listing_id']) ?>"><?= e($request['listing_title']) ?></a>
  </p>

  <form method="post" action="<?= url('/reviews/write/' . $requestId) ?>" novalidate>
    <?= csrf_field() ?>

    <div class="form-group">
      <label>How did it go?</label>
      <div class="star-input">
        <?php for ($value = 5; $value >= 1; $value--): ?>
          <input type="radio" id="rating-<?= $value ?>" name="rating" value="<?= $value ?>"
                 <?= $selected === $value ? 'checked' : '' ?>>
          <label for="rating-<?= $value ?>" title="<?= $value ?> star<?= $value === 1 ? '' : 's' ?>">★</label>
        <?php endfor; ?>
      </div>
      <?php if (isset($errors['rating'])): ?>
        <p class="field-error"><?= e($errors['rating']) ?></p>
      <?php else: ?>
        <p class="field-hint">Five stars means you would happily deal with them again.</p>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="comment">Your review <span class="optional">(optional)</span></label>
      <textarea id="comment" name="comment"
                class="<?= isset($errors['comment']) ? 'has-error' : '' ?>"
                placeholder="Were they easy to deal with? Was the item as described? Did they turn up on time?"><?= e(old('comment')) ?></textarea>
      <?php if (isset($errors['comment'])): ?>
        <p class="field-error"><?= e($errors['comment']) ?></p>
      <?php else: ?>
        <p class="field-hint">Shown publicly on their profile. Be fair and specific.</p>
      <?php endif; ?>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn">Publish review</button>
      <a class="btn btn-ghost" href="<?= url('/requests/' . $requestId) ?>">Cancel</a>
    </div>
  </form>
</div>
