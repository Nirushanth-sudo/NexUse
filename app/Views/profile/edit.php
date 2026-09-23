<?php
/**
 * NexUse — edit profile.
 *
 * MVC layer: View. Member 3.
 *
 * @var array<string, mixed>  $user
 * @var array<string, string> $errors
 */

$field = static fn(string $name): string => (string) old($name, $user[$name] ?? '');
?>

<div class="card">
  <div class="card-head"><h1 style="font-size:20px;margin:0;">Edit your profile</h1></div>

  <?php /* enctype matters: without it the browser sends only the field names
           and the picture never arrives. */ ?>
  <form method="post" action="<?= url('/profile/edit') ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <div class="form-group">
      <label>Profile picture</label>
      <div class="avatar-edit">
        <?= avatar($user, 'avatar-lg') ?>

        <div class="avatar-edit-controls">
          <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp"
                 data-avatar-input>
          <p class="field-hint">JPG, PNG, GIF or WEBP, up to 2 MB. Square pictures look best.</p>

          <?php if (!empty($user['avatar_path'])): ?>
            <label class="checkbox-line">
              <input type="checkbox" name="remove_avatar" value="1">
              Remove my current picture and go back to initials
            </label>
          <?php endif; ?>
        </div>
      </div>
      <?php if (isset($errors['avatar'])): ?>
        <p class="field-error"><?= e($errors['avatar']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="name">Full name</label>
      <input type="text" id="name" name="name" value="<?= e($field('name')) ?>"
             class="<?= isset($errors['name']) ? 'has-error' : '' ?>" required>
      <?php if (isset($errors['name'])): ?>
        <p class="field-error"><?= e($errors['name']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="email">Email address</label>
      <input type="email" id="email" name="email" value="<?= e($field('email')) ?>"
             class="<?= isset($errors['email']) ? 'has-error' : '' ?>" required>
      <?php if (isset($errors['email'])): ?>
        <p class="field-error"><?= e($errors['email']) ?></p>
      <?php else: ?>
        <p class="field-hint">You sign in with this address.</p>
      <?php endif; ?>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="phone">Phone <span class="optional">(optional)</span></label>
        <input type="tel" id="phone" name="phone" value="<?= e($field('phone')) ?>"
               class="<?= isset($errors['phone']) ? 'has-error' : '' ?>">
        <?php if (isset($errors['phone'])): ?>
          <p class="field-error"><?= e($errors['phone']) ?></p>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="city">City <span class="optional">(optional)</span></label>
        <input type="text" id="city" name="city" value="<?= e($field('city')) ?>" placeholder="Colombo">
      </div>
    </div>

    <div class="form-group">
      <label for="bio">About you <span class="optional">(optional)</span></label>
      <textarea id="bio" name="bio" style="min-height:90px;"
                class="<?= isset($errors['bio']) ? 'has-error' : '' ?>"
                placeholder="A line or two about what you tend to list or look for."><?= e($field('bio')) ?></textarea>
      <?php if (isset($errors['bio'])): ?>
        <p class="field-error"><?= e($errors['bio']) ?></p>
      <?php else: ?>
        <p class="field-hint">Shown on your public profile. Up to 500 characters.</p>
      <?php endif; ?>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn">Save changes</button>
      <a class="btn btn-ghost" href="<?= url('/profile') ?>">Cancel</a>
    </div>
  </form>
</div>
