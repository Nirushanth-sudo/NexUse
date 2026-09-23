<?php
/**
 * NexUse — admin user list.
 *
 * MVC layer: View. Member 4 · Read.
 *
 * @var list<array<string, mixed>> $users
 * @var string                     $keyword, $role, $status, $adminNav
 * @var array<string, int>         $counts
 * @var int                        $total
 */

use App\Core\View;
?>

<div class="page-head">
  <div>
    <h1>Users</h1>
    <p class="subtitle">
      <?= number_format($total) ?> account<?= $total === 1 ? '' : 's' ?> ·
      <?= (int) ($counts['admin'] ?? 0) ?> admin,
      <?= (int) ($counts['member'] ?? 0) ?> member
    </p>
  </div>
  <a class="btn" href="<?= url('/admin/users/create') ?>">+ Add a user</a>
</div>

<div class="split">
  <?php View::partial('partials.admin_nav', ['adminNav' => $adminNav]); ?>

  <div>
    <form class="filter-bar" method="get" action="<?= url('/admin/users') ?>">
      <div class="search-row">
        <input type="search" name="q" value="<?= e($keyword) ?>" placeholder="Search name or email…">
        <button type="submit" class="btn">Search</button>
      </div>

      <div class="form-row" style="margin-bottom:0;">
        <div class="form-group mb-0">
          <label for="role">Role</label>
          <select id="role" name="role" data-auto-submit>
            <option value="">All roles</option>
            <option value="member" <?= $role === 'member' ? 'selected' : '' ?>>Members</option>
            <option value="admin"  <?= $role === 'admin' ? 'selected' : '' ?>>Administrators</option>
          </select>
        </div>
        <div class="form-group mb-0">
          <label for="status">Status</label>
          <select id="status" name="status" data-auto-submit>
            <option value="">Any status</option>
            <option value="active"    <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
          </select>
        </div>
      </div>
    </form>

    <?php if (empty($users)): ?>
      <div class="empty">
        <div class="icon">👤</div>
        <h3>No users match that</h3>
        <p>Try a different search or clear the filters.</p>
        <a class="btn btn-secondary" href="<?= url('/admin/users') ?>">Clear filters</a>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>User</th><th>Role</th><th>Status</th>
              <th class="num">Listings</th><th class="num">Requests</th>
              <th>Joined</th><th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <?php $uid = (int) $u['user_id']; ?>
              <tr>
                <td>
                  <div class="flex gap-sm">
                    <?= avatar($u, 'avatar-sm') ?>
                    <div style="min-width:0;">
                      <a href="<?= url('/users/' . $uid) ?>" class="primary"><?= e($u['name']) ?></a>
                      <span class="sub"><?= e($u['email']) ?></span>
                    </div>
                  </div>
                </td>
                <td><span class="badge badge-<?= e($u['role']) ?>"><?= e(ucfirst((string) $u['role'])) ?></span></td>
                <td><span class="badge badge-<?= e($u['status']) ?>"><?= e(ucfirst((string) $u['status'])) ?></span></td>
                <td class="num"><?= (int) $u['listing_count'] ?></td>
                <td class="num"><?= (int) $u['request_count'] ?></td>
                <td class="small muted nowrap"><?= e(short_date((string) $u['created_at'])) ?></td>
                <td>
                  <div class="table-actions">
                    <a class="btn btn-secondary btn-sm" href="<?= url('/admin/users/' . $uid . '/edit') ?>">Edit</a>
                    <?php if (!is_admin() || auth_id() !== $uid): ?>
                      <form method="post" action="<?= url('/admin/users/' . $uid . '/delete') ?>"
                            data-confirm="Delete <?= e($u['name']) ?> and everything they posted? This cannot be undone.">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--red);">Delete</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
