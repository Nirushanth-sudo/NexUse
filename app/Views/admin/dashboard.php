<?php
/**
 * NexUse — admin dashboard.
 *
 * MVC layer: View. Member 4.
 *
 * @var array<string, int>         $stats
 * @var array<string, int>         $listingTypes, $requestStatus
 * @var float|null                 $averageRating
 * @var list<array<string, mixed>> $latestListings, $latestRequests, $openComplaints
 * @var string                     $adminNav
 */

use App\Core\View;

$typeTotal   = max(1, array_sum($listingTypes));
$statusTotal = max(1, array_sum($requestStatus));

$typeColours = ['sell' => '', 'rent' => 'amber', 'share' => 'violet', 'donate' => 'green'];
?>

<div class="page-head">
  <div>
    <h1>Admin dashboard</h1>
    <p class="subtitle">Everything happening on NexUse at a glance.</p>
  </div>
  <a class="btn" href="<?= url('/admin/broadcast') ?>">Send a notification</a>
</div>

<div class="split">
  <?php View::partial('partials.admin_nav', ['adminNav' => $adminNav]); ?>

  <div>
    <?php if ($stats['complaints'] > 0): ?>
      <div class="alert alert-warning">
        <span>
          <strong><?= (int) $stats['complaints'] ?></strong>
          complaint<?= $stats['complaints'] === 1 ? '' : 's' ?> need attention.
          <a href="<?= url('/admin/complaints') ?>">Review them →</a>
        </span>
      </div>
    <?php endif; ?>

    <div class="stat-grid">
      <div class="stat accent-blue">
        <div class="label">Users</div>
        <div class="value"><?= number_format($stats['users']) ?></div>
        <div class="foot">
          <?= (int) $stats['members'] ?> members
          <?php if ($stats['suspended'] > 0): ?>· <?= (int) $stats['suspended'] ?> suspended<?php endif; ?>
        </div>
      </div>
      <div class="stat accent-green">
        <div class="label">Listings</div>
        <div class="value"><?= number_format($stats['listings']) ?></div>
        <div class="foot"><?= (int) $stats['available'] ?> available now</div>
      </div>
      <div class="stat accent-violet">
        <div class="label">Requests</div>
        <div class="value"><?= number_format($stats['requests']) ?></div>
        <div class="foot"><?= (int) $stats['completed'] ?> completed</div>
      </div>
      <div class="stat accent-amber">
        <div class="label">Reviews</div>
        <div class="value"><?= number_format($stats['reviews']) ?></div>
        <div class="foot">
          <?= $averageRating !== null ? 'average ' . e((string) $averageRating) . ' / 5' : 'none yet' ?>
        </div>
      </div>
    </div>

    <div class="grid grid-2 mb-3">
      <div class="card">
        <h3>Listings by type</h3>
        <div class="bar-chart">
          <?php foreach (['sell', 'rent', 'share', 'donate'] as $type): ?>
            <?php $count = $listingTypes[$type] ?? 0; ?>
            <div class="bar-row">
              <span class="bar-label"><?= e(listing_type_label($type)) ?></span>
              <span class="bar-track">
                <span class="bar-fill <?= $typeColours[$type] ?>"
                      style="width:<?= (int) round($count / $typeTotal * 100) ?>%;"></span>
              </span>
              <span class="bar-value"><?= $count ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card">
        <h3>Requests by status</h3>
        <div class="bar-chart">
          <?php
          $statusColours = [
              'pending'   => 'amber',
              'accepted'  => 'green',
              'completed' => '',
              'rejected'  => 'violet',
              'withdrawn' => 'violet',
          ];
          ?>
          <?php foreach ($statusColours as $status => $colour): ?>
            <?php $count = $requestStatus[$status] ?? 0; ?>
            <div class="bar-row">
              <span class="bar-label"><?= e($status === 'rejected' ? 'Declined' : ucfirst($status)) ?></span>
              <span class="bar-track">
                <span class="bar-fill <?= $colour ?>"
                      style="width:<?= (int) round($count / $statusTotal * 100) ?>%;"></span>
              </span>
              <span class="bar-value"><?= $count ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <section class="section">
      <div class="section-head">
        <h2>Newest listings</h2>
        <a href="<?= url('/browse') ?>">Browse all →</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Item</th><th>Owner</th><th>Type</th><th>Status</th><th>Posted</th></tr></thead>
          <tbody>
            <?php foreach ($latestListings as $l): ?>
              <tr>
                <td><a href="<?= url('/listings/' . (int) $l['listing_id']) ?>" class="primary"><?= e($l['title']) ?></a></td>
                <td><?= e($l['owner_name']) ?></td>
                <td><span class="badge badge-<?= e($l['listing_type']) ?>"><?= e(listing_type_label((string) $l['listing_type'])) ?></span></td>
                <td><span class="badge badge-<?= e($l['status']) ?>"><?= e(ucfirst((string) $l['status'])) ?></span></td>
                <td class="small muted nowrap"><?= e(time_ago((string) $l['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($latestListings)): ?>
              <tr><td colspan="5" class="muted center">No listings yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="section">
      <div class="section-head"><h2>Newest requests</h2></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Item</th><th>From</th><th>To</th><th>Status</th><th>Sent</th></tr></thead>
          <tbody>
            <?php foreach ($latestRequests as $r): ?>
              <tr>
                <td><?= e($r['listing_title']) ?></td>
                <td><?= e($r['requester_name']) ?></td>
                <td><?= e($r['owner_name']) ?></td>
                <td><span class="badge badge-<?= e($r['status']) ?>">
                  <?= e($r['status'] === 'rejected' ? 'Declined' : ucfirst((string) $r['status'])) ?>
                </span></td>
                <td class="small muted nowrap"><?= e(time_ago((string) $r['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($latestRequests)): ?>
              <tr><td colspan="5" class="muted center">No requests yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <?php if (!empty($openComplaints)): ?>
      <section class="section">
        <div class="section-head">
          <h2>Open complaints</h2>
          <a href="<?= url('/admin/complaints') ?>">All complaints →</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Subject</th><th>From</th><th>Against</th><th>Filed</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($openComplaints as $c): ?>
                <tr>
                  <td class="primary"><?= e($c['subject']) ?></td>
                  <td><?= e($c['complainant_name']) ?></td>
                  <td><?= e($c['against_name'] ?? '—') ?></td>
                  <td class="small muted nowrap"><?= e(time_ago((string) $c['created_at'])) ?></td>
                  <td class="right">
                    <a class="btn btn-secondary btn-sm"
                       href="<?= url('/admin/complaints/' . (int) $c['complaint_id']) ?>">Review</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
    <?php endif; ?>
  </div>
</div>
