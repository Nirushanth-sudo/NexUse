<?php
/**
 * NexUse — sign in form.
 *
 * MVC layer: View. Criterion 1.
 *
 * @var array<string, string> $errors
 */
?>

<div class="card">
  <div class="card-head"><h1 style="font-size:21px;margin:0;">Sign in to NexUse</h1></div>

  <?php if (isset($errors['form'])): ?>
    <div class="alert alert-error"><span><?= e($errors['form']) ?></span></div>
  <?php endif; ?>

  <form method="post" action="<?= url('/login') ?>" novalidate>
    <?= csrf_field() ?>

    <div class="form-group">
      <label for="email">Email address</label>
      <input type="email" id="email" name="email" value="<?= e(old('email')) ?>"
             autocomplete="email" required autofocus>
    </div>

    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" autocomplete="current-password" required>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-block">Sign in</button>
    </div>
  </form>

  <p class="center small muted mt-2 mb-0">
    New here? <a href="<?= url('/register') ?>">Create an account</a>
  </p>
</div>
