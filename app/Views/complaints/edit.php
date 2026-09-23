<?php
/**
 * NexUse — edit a complaint.
 *
 * MVC layer: View. Member 3 · Update (second CRUD set).
 *
 * @var array<string, mixed>  $complaint
 * @var array<string, string> $errors
 */

$id = (int) $complaint['complaint_id'];
?>

<div class="card">
  <div class="card-head"><h1 style="font-size:20px;margin:0;">Edit your report</h1></div>

  <p class="small muted mb-2">
    Filed <?= e(time_ago((string) $complaint['created_at'])) ?> ·
    currently <span class="badge badge-<?= e($complaint['status']) ?>"><?= e(ucfirst((string) $complaint['status'])) ?></span>
  </p>

  <form method="post" action="<?= url('/complaints/' . $id . '/edit') ?>" novalidate>
    <?= csrf_field() ?>

    <div class="form-group">
      <label for="subject">Subject</label>
      <input type="text" id="subject" name="subject"
             value="<?= e(old('subject', (string) $complaint['subject'])) ?>"
             class="<?= isset($errors['subject']) ? 'has-error' : '' ?>" required>
      <?php if (isset($errors['subject'])): ?>
        <p class="field-error"><?= e($errors['subject']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="description">What happened?</label>
      <textarea id="description" name="description"
                class="<?= isset($errors['description']) ? 'has-error' : '' ?>"
                required><?= e(old('description', (string) $complaint['description'])) ?></textarea>
      <?php if (isset($errors['description'])): ?>
        <p class="field-error"><?= e($errors['description']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn">Save changes</button>
      <a class="btn btn-ghost" href="<?= url('/complaints') ?>">Cancel</a>
    </div>
  </form>

  <hr>

  <form method="post" action="<?= url('/complaints/' . $id . '/delete') ?>"
        data-confirm="Withdraw this complaint? It will be removed entirely.">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-danger btn-sm">Withdraw this complaint</button>
  </form>
</div>
