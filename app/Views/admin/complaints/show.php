<?php
/**
 * NexUse — review one complaint.
 *
 * MVC layer: View. Member 4, on Member 3's complaints table.
 *
 * @var array<string, mixed> $complaint
 * @var string               $adminNav
 */

use App\Core\View;
use App\Models\Complaint;

$id = (int) $complaint['complaint_id'];
?>

<nav class="breadcrumb">
  <a href="<?= url('/admin') ?>">Admin</a> ›
  <a href="<?= url('/admin/complaints') ?>">Complaints</a> ›
  <span>#<?= $id ?></span>
</nav>

<div class="split">
  <?php View::partial('partials.admin_nav', ['adminNav' => $adminNav]); ?>

  <div>
<div class="card mb-2">
  <div class="card-head">
    <h1 style="font-size:20px;margin:0;"><?= e($complaint['subject']) ?></h1>
    <span class="badge badge-<?= e($complaint['status']) ?>"><?= e(ucfirst((string) $complaint['status'])) ?></span>
  </div>

  <ul class="spec-list mb-3">
    <li>
      <span class="k">Filed by</span>
      <span class="v">
        <a href="<?= url('/users/' . (int) $complaint['complainant_id']) ?>"><?= e($complaint['complainant_name']) ?></a>
        · <?= e($complaint['complainant_email']) ?>
      </span>
    </li>
    <li>
      <span class="k">Against</span>
      <span class="v">
        <?php if (!empty($complaint['against_name'])): ?>
          <a href="<?= url('/users/' . (int) $complaint['against_user_id']) ?>"><?= e($complaint['against_name']) ?></a>
        <?php else: ?>—<?php endif; ?>
      </span>
    </li>
    <li>
      <span class="k">About</span>
      <span class="v">
        <?php if (!empty($complaint['listing_title'])): ?>
          <a href="<?= url('/listings/' . (int) $complaint['listing_id']) ?>"><?= e($complaint['listing_title']) ?></a>
        <?php else: ?>No specific listing<?php endif; ?>
      </span>
    </li>
    <li><span class="k">Filed</span><span class="v"><?= e(short_date((string) $complaint['created_at'])) ?> · <?= e(time_ago((string) $complaint['created_at'])) ?></span></li>
  </ul>

  <h3 style="font-size:14px;">What was reported</h3>
  <p style="white-space:pre-line;color:var(--ink-2);"><?= e($complaint['description']) ?></p>
</div>

<div class="card">
  <div class="card-head"><h2>Your decision</h2></div>

  <p class="small muted mb-2">
    NexUse records disputes and passes them on. It does not enforce compensation — your
    note is shown to the person who filed the report.
  </p>

  <form method="post" action="<?= url('/admin/complaints/' . $id) ?>">
    <?= csrf_field() ?>

    <div class="form-group">
      <label for="status">Status</label>
      <select id="status" name="status">
        <?php foreach (Complaint::STATUSES as $s): ?>
          <option value="<?= e($s) ?>" <?= $complaint['status'] === $s ? 'selected' : '' ?>>
            <?= e(ucfirst($s)) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <p class="field-hint">
        <strong>Reviewing</strong> means you are looking into it.
        <strong>Resolved</strong> means it was dealt with.
        <strong>Dismissed</strong> means no action was warranted.
      </p>
    </div>

    <div class="form-group">
      <label for="admin_note">Note to the complainant <span class="optional">(optional)</span></label>
      <textarea id="admin_note" name="admin_note" style="min-height:90px;"
                placeholder="What you have done, or why no action was taken."><?= e((string) $complaint['admin_note']) ?></textarea>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn">Save decision</button>
      <a class="btn btn-ghost" href="<?= url('/admin/complaints') ?>">Back to queue</a>
      <?php if (!empty($complaint['against_user_id'])): ?>
        <a class="btn btn-secondary btn-sm" style="margin-left:auto;"
           href="<?= url('/admin/users/' . (int) $complaint['against_user_id'] . '/edit') ?>">
          Manage <?= e($complaint['against_name']) ?>
        </a>
      <?php endif; ?>
    </div>
  </form>
</div>
  </div>
</div>
