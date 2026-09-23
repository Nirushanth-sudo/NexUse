<?php
/**
 * NexUse — create account form.
 *
 * MVC layer: View. Criterion 1.
 *
 * @var array<string, string> $errors
 */
?>

<div class="card">
  <div class="card-head"><h1 style="font-size:21px;margin:0;">Create your account</h1></div>

  <p class="muted small mb-2">
    One account covers everything — selling, renting, borrowing and donating.
  </p>

  <?php if (isset($errors['form'])): ?>
    <div class="alert alert-error"><span><?= e($errors['form']) ?></span></div>
  <?php endif; ?>

  <form method="post" action="<?= url('/register') ?>" novalidate>
    <?= csrf_field() ?>

    <div class="form-group">
      <label for="name">Full name</label>
      <input type="text" id="name" name="name" value="<?= e(old('name')) ?>"
             class="<?= isset($errors['name']) ? 'has-error' : '' ?>"
             autocomplete="name" required>
      <?php if (isset($errors['name'])): ?>
        <p class="field-error"><?= e($errors['name']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="email">Email address</label>
      <input type="email" id="email" name="email" value="<?= e(old('email')) ?>"
             class="<?= isset($errors['email']) ? 'has-error' : '' ?>"
             autocomplete="email" required>
      <?php if (isset($errors['email'])): ?>
        <p class="field-error"><?= e($errors['email']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="phone">Phone <span class="optional">(optional)</span></label>
        <input type="tel" id="phone" name="phone" value="<?= e(old('phone')) ?>"
               class="<?= isset($errors['phone']) ? 'has-error' : '' ?>"
               autocomplete="tel">
        <?php if (isset($errors['phone'])): ?>
          <p class="field-error"><?= e($errors['phone']) ?></p>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="city">City <span class="optional">(optional)</span></label>
        <input type="text" id="city" name="city" value="<?= e(old('city')) ?>"
               autocomplete="address-level2" placeholder="Colombo">
      </div>
    </div>

    <div class="form-group">
      <label for="password">Password</label>
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
      <label for="password_confirm">Confirm password</label>
      <input type="password" id="password_confirm" name="password_confirm"
             class="<?= isset($errors['password_confirm']) ? 'has-error' : '' ?>"
             autocomplete="new-password" required>
      <?php if (isset($errors['password_confirm'])): ?>
        <p class="field-error"><?= e($errors['password_confirm']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-block">Create account</button>
    </div>
  </form>

  <p class="center small muted mt-2 mb-0">
    Already have an account? <a href="<?= url('/login') ?>">Sign in</a>
  </p>
</div>
