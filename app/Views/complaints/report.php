<?php
/**
 * NexUse — report a problem.
 *
 * MVC layer: View. Member 3 · Create (second CRUD set).
 *
 * @var array<string, mixed>|null $against
 * @var array<string, mixed>|null $listing
 * @var array<string, string>     $errors
 */
?>

<div class="card">
  <div class="card-head"><h1 style="font-size:20px;margin:0;">Report a problem</h1></div>

  <p class="small muted mb-2">
    NexUse records disputes and passes them to an administrator. It does not mediate
    between members or enforce compensation — but a record helps, and repeated reports
    against the same person are acted on.
  </p>

  <?php if ($against !== null || $listing !== null): ?>
    <div class="alert alert-info">
      <span>
        Reporting
        <?php if ($against !== null): ?><strong><?= e($against['name']) ?></strong><?php endif; ?>
        <?php if ($against !== null && $listing !== null): ?> regarding <?php endif; ?>
        <?php if ($listing !== null): ?><strong><?= e($listing['title']) ?></strong><?php endif; ?>.
      </span>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= url('/complaints/report') ?>" novalidate>
    <?= csrf_field() ?>

    <?php if ($against !== null): ?>
      <input type="hidden" name="against_user_id" value="<?= (int) $against['user_id'] ?>">
    <?php endif; ?>
    <?php if ($listing !== null): ?>
      <input type="hidden" name="listing_id" value="<?= (int) $listing['listing_id'] ?>">
    <?php endif; ?>

    <div class="form-group">
      <label for="subject">Subject</label>
      <input type="text" id="subject" name="subject" value="<?= e(old('subject')) ?>"
             class="<?= isset($errors['subject']) ? 'has-error' : '' ?>"
             placeholder="Item was not as described" required>
      <?php if (isset($errors['subject'])): ?>
        <p class="field-error"><?= e($errors['subject']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="description">What happened?</label>
      <textarea id="description" name="description"
                class="<?= isset($errors['description']) ? 'has-error' : '' ?>"
                placeholder="Give the facts: what was agreed, what actually happened, and what you have already tried."
                required><?= e(old('description')) ?></textarea>
      <?php if (isset($errors['description'])): ?>
        <p class="field-error"><?= e($errors['description']) ?></p>
      <?php else: ?>
        <p class="field-hint">An administrator will read this. Stick to what happened.</p>
      <?php endif; ?>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn">Submit report</button>
      <a class="btn btn-ghost" href="<?= url('/complaints') ?>">Cancel</a>
    </div>
  </form>
</div>
