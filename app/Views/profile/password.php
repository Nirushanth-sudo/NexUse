<?php
/**
 * NexUse — change password.
 *
 * MVC layer: View. Member 3 / Member 4 (authentication).
 *
 * @var array<string, string> $errors
 */
?>

<div class="card">
  <div class="card-head"><h1 style="font-size:20px;margin:0;">Change your password</h1></div>

  <form method="post" action="<?= url('/profile/password') ?>" novalidate>
    <?= csrf_field() ?>

    <div class="form-group">
      <label for="current_password">Current password</label>
      <input type="password" id="current_password" name="current_password"
             class="<?= isset($errors['current_password']) ? 'has-error' : '' ?>"
             autocomplete="current-password" required autofocus>
      <?php if (isset($errors['current_password'])): ?>
        <p class="field-error"><?= e($errors['current_password']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="password">New password</label>
      <input type="password" id="password" name="password"
             class="<?= isset($errors['password']) ? 'has-error' : '' ?>"
             autocomplete="new-password" required>
      <?php if (isset($errors['password'])): ?>
        <p class="field-error"><?= e($errors['password']) ?></p>
      <?php else: ?>
        <p class="field-hint">At least 8 characters.</p>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="password_confirm">Confirm new password</label>
      <input type="password" id="password_confirm" name="password_confirm"
             class="<?= isset($errors['password_confirm']) ? 'has-error' : '' ?>"
             autocomplete="new-password" required>
      <?php if (isset($errors['password_confirm'])): ?>
        <p class="field-error"><?= e($errors['password_confirm']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn">Change password</button>
      <a class="btn btn-ghost" href="<?= url('/profile') ?>">Cancel</a>
    </div>
  </form>
</div>
