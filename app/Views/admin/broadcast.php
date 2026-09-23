<?php
/**
 * NexUse — admin broadcast notification.
 *
 * MVC layer: View. Member 4 · Create (notifications entity, admin side).
 *
 * @var array<string, string> $errors
 * @var int                   $memberCount, $adminCount
 * @var string                $adminNav
 */

use App\Core\View;
?>

<div class="page-head">
  <div>
    <h1>Send a notification</h1>
    <p class="subtitle">Reaches everyone's notification bell straight away.</p>
  </div>
  <a class="btn btn-ghost" href="<?= url('/admin') ?>">← Dashboard</a>
</div>

<div class="split">
  <?php View::partial('partials.admin_nav', ['adminNav' => $adminNav]); ?>

  <div class="admin-form">
<div class="card">
  <form method="post" action="<?= url('/admin/broadcast') ?>" novalidate>
    <?= csrf_field() ?>

    <div class="form-group">
      <label>Who receives it?</label>
      <div class="radio-cards radio-cards-3">
        <label class="radio-card">
          <input type="radio" name="audience" value="all"
                 <?= (string) old('audience', 'all') === 'all' ? 'checked' : '' ?>>
          <strong>Everyone</strong>
          <span><?= $memberCount + $adminCount ?> active accounts</span>
        </label>
        <label class="radio-card">
          <input type="radio" name="audience" value="member"
                 <?= (string) old('audience') === 'member' ? 'checked' : '' ?>>
          <strong>Members only</strong>
          <span><?= $memberCount ?> people</span>
        </label>
        <label class="radio-card">
          <input type="radio" name="audience" value="admin"
                 <?= (string) old('audience') === 'admin' ? 'checked' : '' ?>>
          <strong>Administrators</strong>
          <span><?= $adminCount ?> people</span>
        </label>
      </div>
      <?php if (isset($errors['audience'])): ?>
        <p class="field-error"><?= e($errors['audience']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="title">Title</label>
      <input type="text" id="title" name="title" value="<?= e(old('title')) ?>"
             class="<?= isset($errors['title']) ? 'has-error' : '' ?>"
             placeholder="Scheduled maintenance this Sunday" required>
      <?php if (isset($errors['title'])): ?>
        <p class="field-error"><?= e($errors['title']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="message">Message <span class="optional">(optional)</span></label>
      <textarea id="message" name="message"
                class="<?= isset($errors['message']) ? 'has-error' : '' ?>"
                placeholder="NexUse will be briefly unavailable on Sunday morning."><?= e(old('message')) ?></textarea>
      <?php if (isset($errors['message'])): ?>
        <p class="field-error"><?= e($errors['message']) ?></p>
      <?php else: ?>
        <p class="field-hint">Up to 500 characters. Nobody can reply to a broadcast.</p>
      <?php endif; ?>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn"
              data-confirm="Send this notification now?">Send notification</button>
      <a class="btn btn-ghost" href="<?= url('/admin') ?>">Cancel</a>
    </div>
  </form>
</div>
  </div>
</div>
