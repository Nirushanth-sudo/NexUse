<?php
/**
 * NexUse — admin complaint queue.
 *
 * MVC layer: View. Member 4, on Member 3's complaints table.
 *
 * @var list<array<string, mixed>> $complaints
 * @var string                     $status, $adminNav
 * @var array<string, int>         $counts
 * @var int                        $total
 */

use App\Core\View;
use App\Models\Complaint;
?>

<div class="page-head">
  <div>
    <h1>Complaints</h1>
    <p class="subtitle">Disputes reported by members, oldest and most urgent first.</p>
  </div>
</div>

<div class="split">
  <?php View::partial('partials.admin_nav', ['adminNav' => $adminNav]); ?>

  <div>
    <div class="tabs">
      <a href="<?= url('/admin/complaints') ?>" class="<?= $status === '' ? 'active' : '' ?>">
        All<span class="count"><?= $total ?></span>
      </a>
      <?php foreach (Complaint::STATUSES as $s): ?>
        <a href="<?= url('/admin/complaints?status=' . $s) ?>" class="<?= $status === $s ? 'active' : '' ?>">
          <?= e(ucfirst($s)) ?><span class="count"><?= (int) ($counts[$s] ?? 0) ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if (empty($complaints)): ?>
      <div class="empty">
        <div class="icon">🛡️</div>
        <h3>Nothing here</h3>
        <p>
          <?= $status === '' ? 'No complaints have been filed.' : 'No complaints with that status.' ?>
        </p>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Subject</th><th>From</th><th>Against</th><th>Status</th><th>Filed</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($complaints as $c): ?>
              <?php $cid = (int) $c['complaint_id']; ?>
              <tr>
                <td>
                  <a href="<?= url('/admin/complaints/' . $cid) ?>" class="primary"><?= e($c['subject']) ?></a>
                  <?php if (!empty($c['listing_title'])): ?>
                    <span class="sub">about <?= e($c['listing_title']) ?></span>
                  <?php endif; ?>
                </td>
                <td><?= e($c['complainant_name']) ?></td>
                <td><?= e($c['against_name'] ?? '—') ?></td>
                <td><span class="badge badge-<?= e($c['status']) ?>"><?= e(ucfirst((string) $c['status'])) ?></span></td>
                <td class="small muted nowrap"><?= e(time_ago((string) $c['created_at'])) ?></td>
                <td class="right">
                  <a class="btn btn-secondary btn-sm" href="<?= url('/admin/complaints/' . $cid) ?>">Review</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
