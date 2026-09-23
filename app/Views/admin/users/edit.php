<?php
/**
 * NexUse — admin edits a user account.
 *
 * MVC layer: View. Member 4 · Update (admin side of the users entity).
 *
 * @var array<string, mixed>  $target
 * @var bool                  $isSelf
 * @var array<string, int>    $activity
 * @var array<string, string> $errors
 * @var string                $adminNav
 */

use App\Core\View;

$id    = (int) $target['user_id'];
$field = static fn(string $name): string => (string) old($name, $target[$name] ?? '');
?>

<div class="split">
  <?php View::partial('partials.admin_nav', ['adminNav' => $adminNav]); ?>

  <div class="admin-form">

<div class="card mb-2">
  <div class="card-head">
    <h1 style="font-size:20px;margin:0;">Edit <?= e($target['name']) ?></h1>
    <span class="badge badge-<?= e($target['status']) ?>"><?= e(ucfirst((string) $target['status'])) ?></span>
  </div>

  <?php if ($isSelf): ?>
    <div class="alert alert-info">
      <span>This is your own account. You cannot remove your own administrator access.</span>
    </div>
  <?php endif; ?>

  <ul class="spec-list mb-3">
    <li><span class="k">Listings</span><span class="v"><?= (int) $activity['listings'] ?></span></li>
    <li><span class="k">Requests involved in</span><span class="v"><?= (int) $activity['requests'] ?></span></li>
    <li><span class="k">Reviews received</span><span class="v"><?= (int) $activity['reviews'] ?></span></li>
    <li><span class="k">Complaints against them</span><span class="v"><?= (int) $activity['complaints'] ?></span></li>
    <li><span class="k">Joined</span><span class="v"><?= e(short_date((string) $target['created_at'])) ?></span></li>
  </ul>

  <form method="post" action="<?= url('/admin/users/' . $id . '/edit') ?>" novalidate>
    <?= csrf_field() ?>

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
      <?php endif; ?>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="phone">Phone</label>
        <input type="tel" id="phone" name="phone" value="<?= e($field('phone')) ?>">
      </div>
      <div class="form-group">
        <label for="city">City</label>
        <input type="text" id="city" name="city" value="<?= e($field('city')) ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="role">Role</label>
        <select id="role" name="role" class="<?= isset($errors['role']) ? 'has-error' : '' ?>"
                <?= $isSelf ? 'disabled' : '' ?>>
          <option value="member" <?= $field('role') === 'member' ? 'selected' : '' ?>>Member</option>
          <option value="admin"  <?= $field('role') === 'admin' ? 'selected' : '' ?>>Administrator</option>
        </select>
        <?php if ($isSelf): ?>
          <input type="hidden" name="role" value="admin">
        <?php endif; ?>
        <?php if (isset($errors['role'])): ?>
          <p class="field-error"><?= e($errors['role']) ?></p>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="status">Status</label>
        <select id="status" name="status" class="<?= isset($errors['status']) ? 'has-error' : '' ?>"
                <?= $isSelf ? 'disabled' : '' ?>>
          <option value="active"    <?= $field('status') === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="suspended" <?= $field('status') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
        </select>
        <?php if ($isSelf): ?>
          <input type="hidden" name="status" value="active">
        <?php else: ?>
          <p class="field-hint">A suspended member cannot sign in.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="form-group">
      <label for="password">Reset password <span class="optional">(optional)</span></label>
      <input type="password" id="password" name="password"
             class="<?= isset($errors['password']) ? 'has-error' : '' ?>"
             autocomplete="new-password" placeholder="Leave blank to keep the current password">
      <?php if (isset($errors['password'])): ?>
        <p class="field-error"><?= e($errors['password']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn">Save changes</button>
      <a class="btn btn-ghost" href="<?= url('/admin/users') ?>">Cancel</a>
    </div>
  </form>
</div>

<?php if (!$isSelf): ?>
  <div class="card">
    <h3 style="color:var(--red);">Delete this account</h3>
    <p class="small muted mb-2">
      Removes <?= e($target['name']) ?> along with their listings, requests, reviews and
      notifications. This cannot be undone — suspending is usually the better option.
    </p>
    <form method="post" action="<?= url('/admin/users/' . $id . '/delete') ?>"
          data-confirm="Permanently delete <?= e($target['name']) ?> and everything they posted?">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-danger btn-sm">Delete account</button>
    </form>
  </div>
<?php endif; ?>

  </div>
</div>
