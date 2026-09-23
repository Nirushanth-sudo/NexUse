<?php
/**
 * NexUse — requests sent and received.
 *
 * MVC layer: View. Member 2 · Read (lists).
 *
 * @var string                     $side  'sent' or 'received'
 * @var list<array<string, mixed>> $requests
 * @var array<string, int>         $counts
 * @var string                     $status
 */

$isReceived = $side === 'received';
$basePath   = $isReceived ? '/requests/received' : '/requests/sent';

$tabs = [
    ''          => 'All',
    'pending'   => 'Pending',
    'accepted'  => 'Accepted',
    'completed' => 'Completed',
    'rejected'  => 'Declined',
    'withdrawn' => 'Withdrawn',
];
?>

<div class="page-head">
  <div>
    <h1><?= $isReceived ? 'Requests received' : 'Requests I sent' ?></h1>
    <p class="subtitle">
      <?= $isReceived
          ? 'People asking for the items you have listed.'
          : 'Items you have asked other people for.' ?>
    </p>
  </div>
</div>

<div class="tabs">
  <a href="<?= url('/requests/received') ?>" class="<?= $isReceived ? 'active' : '' ?>">Received</a>
  <a href="<?= url('/requests/sent') ?>" class="<?= $isReceived ? '' : 'active' ?>">Sent</a>
</div>

<div class="tabs">
  <?php foreach ($tabs as $key => $label): ?>
    <a href="<?= url($basePath . ($key === '' ? '' : '?status=' . $key)) ?>"
       class="<?= $status === $key ? 'active' : '' ?>">
      <?= e($label) ?>
      <?php if ($key !== ''): ?>
        <span class="count"><?= (int) ($counts[$key] ?? 0) ?></span>
      <?php endif; ?>
    </a>
  <?php endforeach; ?>
</div>

<?php if (empty($requests)): ?>
  <div class="empty">
    <div class="icon"><?= $isReceived ? '📥' : '📤' ?></div>
    <h3>Nothing here</h3>
    <p>
      <?= $isReceived
          ? 'When somebody asks for one of your items, it appears here.'
          : 'Requests you send appear here so you can track what you asked for.' ?>
    </p>
    <a class="btn" href="<?= $isReceived ? url('/listings/create') : url('/browse') ?>">
      <?= $isReceived ? 'Post an item' : 'Browse items' ?>
    </a>
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Item</th>
          <th><?= $isReceived ? 'Requested by' : 'Owner' ?></th>
          <th>Type</th>
          <th>Dates</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($requests as $r): ?>
          <?php $rid = (int) $r['request_id']; ?>
          <tr>
            <td>
              <a href="<?= url('/listings/' . (int) $r['listing_id']) ?>" class="primary">
                <?= e($r['listing_title']) ?>
              </a>
              <span class="sub"><?= e(time_ago((string) $r['created_at'])) ?></span>
            </td>
            <td>
              <div class="flex gap-sm">
                <?= $isReceived
                      ? avatar(['name' => $r['requester_name'], 'avatar_path' => $r['requester_avatar'] ?? null], 'avatar-sm')
                      : avatar(['name' => $r['owner_name'], 'avatar_path' => $r['owner_avatar'] ?? null], 'avatar-sm') ?>
                <span><?= e($isReceived ? $r['requester_name'] : $r['owner_name']) ?></span>
              </div>
            </td>
            <td><?= e(request_type_label((string) $r['request_type'])) ?></td>
            <td class="nowrap small">
              <?php if (!empty($r['start_date'])): ?>
                <?= e(short_date((string) $r['start_date'])) ?>
                → <?= e(short_date((string) $r['return_date'])) ?>
              <?php else: ?>
                <span class="muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge badge-<?= e($r['status']) ?>">
                <?= e($r['status'] === 'rejected' ? 'Declined' : ucfirst((string) $r['status'])) ?>
              </span>
            </td>
            <td>
              <div class="table-actions">
                <?php if ($isReceived && $r['status'] === 'pending'): ?>
                  <a class="btn btn-sm" href="<?= url('/requests/' . $rid) ?>">Respond</a>
                <?php elseif ($isReceived && $r['status'] === 'accepted'): ?>
                  <a class="btn btn-success btn-sm" href="<?= url('/requests/' . $rid . '/return') ?>">Complete</a>
                <?php else: ?>
                  <a class="btn btn-secondary btn-sm" href="<?= url('/requests/' . $rid) ?>">View</a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
