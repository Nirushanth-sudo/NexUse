<?php
/**
 * NexUse — admin creates a user account.
 *
 * MVC layer: View. Member 4 · Create (admin side of the users entity).
 *
 * @var array<string, string> $errors
 * @var string                $adminNav
 */

use App\Core\View;
?>

<div class="split">
  <?php View::partial('partials.admin_nav', ['adminNav' => $adminNav]); ?>

  <div class="admin-form">

<div class="card">
  <div class="card-head"><h1 style="font-size:20px;margin:0;">Add a user</h1></div>

  <p class="small muted mb-2">
    Creates an account directly. The person is notified and asked to change the password
    you set here.
  </p>

  <form method="post" action="<?= url('/admin/users/create') ?>" novalidate>
    <?= csrf_field() ?>

    <div class="form-group">
      <label for="name">Full name</label>
      <input type="text" id="name" name="name" value="<?= e(old('name')) ?>"
             class="<?= isset($errors['name']) ? 'has-error' : '' ?>" required>
      <?php if (isset($errors['name'])): ?>
        <p class="field-error"><?= e($errors['name']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="email">Email address</label>
      <input type="email" id="email" name="email" value="<?= e(old('email')) ?>"
             class="<?= isset($errors['email']) ? 'has-error' : '' ?>" required>
      <?php if (isset($errors['email'])): ?>
        <p class="field-error"><?= e($errors['email']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="phone">Phone <span class="optional">(optional)</span></label>
        <input type="tel" id="phone" name="phone" value="<?= e(old('phone')) ?>">
      </div>
      <div class="form-group">
        <label for="city">City <span class="optional">(optional)</span></label>
        <input type="text" id="city" name="city" value="<?= e(old('city')) ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="role">Role</label>
        <select id="role" name="role" class="<?= isset($errors['role']) ? 'has-error' : '' ?>">
          <option value="member" <?= (string) old('role', 'member') === 'member' ? 'selected' : '' ?>>Member</option>
          <option value="admin"  <?= (string) old('role') === 'admin' ? 'selected' : '' ?>>Administrator</option>
        </select>
        <?php if (isset($errors['role'])): ?>
          <p class="field-error"><?= e($errors['role']) ?></p>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="password">Temporary password</label>
        <input type="password" id="password" name="password"
               class="<?= isset($errors['password']) ? 'has-error' : '' ?>"
               autocomplete="new-password" required>
        <?php if (isset($errors['password'])): ?>
          <p class="field-error"><?= e($errors['password']) ?></p>
        <?php else: ?>
          <p class="field-hint">At least 8 characters.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn">Create account</button>
      <a class="btn btn-ghost" href="<?= url('/admin/users') ?>">Cancel</a>
    </div>
  </form>
</div>

  </div>
</div>
