<?php
/**
 * NexUse — my listings.
 *
 * MVC layer: View. Member 1 · Read (owner's dashboard).
 *
 * @var list<array<string, mixed>> $listings
 * @var string                     $status
 * @var array<string, int>         $counts
 */

use App\Models\ListingImage;

$tabs = [
    ''          => 'All',
    'available' => 'Available',
    'reserved'  => 'Reserved',
    'completed' => 'Completed',
];
?>

<div class="page-head">
  <div>
    <h1>My listings</h1>
    <p class="subtitle">Everything you have posted, and how many people have asked for it.</p>
  </div>
  <a class="btn" href="<?= url('/listings/create') ?>">+ Post an item</a>
</div>

<div class="tabs">
  <?php foreach ($tabs as $key => $label): ?>
    <a href="<?= url('/listings' . ($key === '' ? '' : '?status=' . $key)) ?>"
       class="<?= $status === $key ? 'active' : '' ?>">
      <?= e($label) ?>
      <span class="count"><?= (int) ($counts[$key === '' ? 'all' : $key] ?? 0) ?></span>
    </a>
  <?php endforeach; ?>
</div>

<?php if (empty($listings)): ?>
  <div class="empty">
    <div class="icon">📦</div>
    <h3><?= $status === '' ? 'You have not posted anything yet' : 'Nothing here' ?></h3>
    <p>
      <?= $status === ''
          ? 'Post the first thing you no longer use — it takes a couple of minutes.'
          : 'No listings with that status. Try another tab.' ?>
    </p>
    <a class="btn" href="<?= url('/listings/create') ?>">Post an item</a>
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Item</th>
          <th>Type</th>
          <th>Price</th>
          <th>Status</th>
          <th class="num">Requests</th>
          <th>Posted</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($listings as $item): ?>
          <?php
          $id    = (int) $item['listing_id'];
          $thumb = ListingImage::primaryPath($id);
          ?>
          <tr>
            <td>
              <div class="flex">
                <?php if ($thumb !== null): ?>
                  <img src="<?= url($thumb) ?>" alt=""
                       style="width:44px;height:34px;object-fit:cover;border-radius:4px;flex:none;">
                <?php else: ?>
                  <span style="width:44px;height:34px;border-radius:4px;background:var(--surface-2);
                               display:inline-flex;align-items:center;justify-content:center;flex:none;">📦</span>
                <?php endif; ?>
                <div style="min-width:0;">
                  <a href="<?= url('/listings/' . $id) ?>" class="primary"><?= e($item['title']) ?></a>
                  <span class="sub"><?= e($item['category_name'] ?? 'Uncategorised') ?></span>
                </div>
              </div>
            </td>
            <td>
              <span class="badge badge-<?= e($item['listing_type']) ?>">
                <?= e(listing_type_label((string) $item['listing_type'])) ?>
              </span>
            </td>
            <td class="num nowrap">
              <?= in_array($item['listing_type'], ['sell', 'rent'], true) ? e(money($item['price'])) : 'Free' ?>
            </td>
            <td>
              <span class="badge badge-<?= e($item['status']) ?>"><?= e(ucfirst((string) $item['status'])) ?></span>
            </td>
            <td class="num">
              <?php if ((int) $item['pending_requests'] > 0): ?>
                <a href="<?= url('/requests/received') ?>">
                  <strong><?= (int) $item['pending_requests'] ?> pending</strong>
                </a>
                <span class="sub"><?= (int) $item['total_requests'] ?> total</span>
              <?php else: ?>
                <?= (int) $item['total_requests'] ?>
              <?php endif; ?>
            </td>
            <td class="nowrap small muted"><?= e(time_ago((string) $item['created_at'])) ?></td>
            <td>
              <div class="table-actions">
                <a class="btn btn-secondary btn-sm" href="<?= url('/listings/' . $id . '/edit') ?>">Edit</a>
                <a class="btn btn-ghost btn-sm" style="color:var(--red);"
                   href="<?= url('/listings/' . $id . '/delete') ?>">Delete</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
