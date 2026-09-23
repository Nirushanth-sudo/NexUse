<?php
/**
 * NexUse — admin category management.
 *
 * MVC layer: View. Member 4, on Member 1's categories table.
 *
 * @var list<array<string, mixed>> $categories
 * @var array<string, string>      $errors
 * @var string                     $adminNav
 */

use App\Core\View;
?>

<div class="page-head">
  <div>
    <h1>Categories</h1>
    <p class="subtitle">The list people choose from when they post an item.</p>
  </div>
</div>

<div class="split">
  <?php View::partial('partials.admin_nav', ['adminNav' => $adminNav]); ?>

  <div>
<div class="card mb-2">
  <div class="card-head"><h2>Add a category</h2></div>

  <form method="post" action="<?= url('/admin/categories/create') ?>" class="search-row" style="margin-bottom:0;">
    <?= csrf_field() ?>
    <input type="text" name="name" value="<?= e(old('name')) ?>"
           class="<?= isset($errors['name']) ? 'has-error' : '' ?>"
           placeholder="Garden &amp; Outdoor" required>
    <button type="submit" class="btn">Add</button>
  </form>
  <?php if (isset($errors['name'])): ?>
    <p class="field-error"><?= e($errors['name']) ?></p>
  <?php endif; ?>
</div>

<?php if (empty($categories)): ?>
  <div class="empty">
    <div class="icon">🏷️</div>
    <h3>No categories yet</h3>
    <p>Add the first one above. Listings can be posted without a category, but they are harder to find.</p>
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Name</th><th>Slug</th><th class="num">Listings</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $category): ?>
          <?php $cid = (int) $category['category_id']; ?>
          <tr>
            <td style="min-width:220px;">
              <form method="post" action="<?= url('/admin/categories/' . $cid . '/update') ?>"
                    class="flex gap-sm">
                <?= csrf_field() ?>
                <input type="text" name="name" value="<?= e($category['name']) ?>" required
                       style="max-width:240px;">
                <button type="submit" class="btn btn-secondary btn-sm">Rename</button>
              </form>
            </td>
            <td class="small muted"><code><?= e($category['slug']) ?></code></td>
            <td class="num">
              <?php if ((int) $category['listing_count'] > 0): ?>
                <a href="<?= url('/browse?category=' . $cid) ?>"><?= (int) $category['listing_count'] ?></a>
              <?php else: ?>
                0
              <?php endif; ?>
            </td>
            <td class="right">
              <form method="post" action="<?= url('/admin/categories/' . $cid . '/delete') ?>"
                    data-confirm="Delete this category? <?= (int) $category['listing_count'] ?> listing(s) will become uncategorised.">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--red);">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <p class="small muted mt-2">
    Deleting a category does not delete any listings — those listings simply become
    uncategorised and can be reassigned by their owner.
  </p>
<?php endif; ?>
  </div>
</div>
