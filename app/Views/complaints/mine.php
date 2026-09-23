<?php
/**
 * NexUse — complaints I filed.
 *
 * MVC layer: View. Member 3 · Read (second CRUD set).
 *
 * @var list<array<string, mixed>> $complaints
 */
?>

<div class="page-head">
  <div>
    <h1>My complaints</h1>
    <p class="subtitle">Problems you have reported, and what an administrator did about them.</p>
  </div>
  <a class="btn" href="<?= url('/complaints/report') ?>">Report a problem</a>
</div>

<?php if (empty($complaints)): ?>
  <div class="empty">
    <div class="icon">🛡️</div>
    <h3>You have not reported anything</h3>
    <p>
      If an exchange goes wrong — an item is not as described, or someone does not turn
      up — you can report it and an administrator will look at it.
    </p>
    <a class="btn btn-secondary" href="<?= url('/complaints/report') ?>">Report a problem</a>
  </div>
<?php else: ?>
  <?php foreach ($complaints as $complaint): ?>
    <?php $id = (int) $complaint['complaint_id']; ?>
    <div class="card mb-2">
      <div class="flex-between flex-wrap mb-1">
        <h3 class="mb-0"><?= e($complaint['subject']) ?></h3>
        <span class="badge badge-<?= e($complaint['status']) ?>"><?= e(ucfirst((string) $complaint['status'])) ?></span>
      </div>

      <p class="small muted mb-2">
        Filed <?= e(time_ago((string) $complaint['created_at'])) ?>
        <?php if (!empty($complaint['against_name'])): ?>
          · against <?= e($complaint['against_name']) ?>
        <?php endif; ?>
        <?php if (!empty($complaint['listing_title'])): ?>
          · about <?= e($complaint['listing_title']) ?>
        <?php endif; ?>
      </p>

      <p style="white-space:pre-line;color:var(--ink-2);"><?= e($complaint['description']) ?></p>

      <?php if (!empty($complaint['admin_note'])): ?>
        <div class="alert alert-info mt-2 mb-0">
          <span><strong>Administrator:</strong> <?= e($complaint['admin_note']) ?></span>
        </div>
      <?php endif; ?>

      <?php if (in_array($complaint['status'], ['open', 'reviewing'], true)): ?>
        <div class="btn-row mt-2">
          <a class="btn btn-secondary btn-sm" href="<?= url('/complaints/' . $id . '/edit') ?>">Edit</a>
          <form method="post" action="<?= url('/complaints/' . $id . '/delete') ?>"
                data-confirm="Withdraw this complaint?">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--red);">Withdraw</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
