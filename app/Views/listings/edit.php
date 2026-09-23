<?php
/**
 * NexUse — edit a listing.
 *
 * MVC layer: View. Member 1 · Update.
 *
 * @var array<string, mixed>       $listing
 * @var list<array<string, mixed>> $images
 * @var list<array<string, mixed>> $categories
 * @var array<string, string>      $errors
 */

use App\Models\Listing;
use App\Models\ListingImage;

$id = (int) $listing['listing_id'];

/** The resubmitted value if the form was rejected, otherwise the stored one. */
$field = static fn(string $name): string => (string) old($name, $listing[$name] ?? '');

$typeBlurbs = [
    'sell'   => ['For sale',  'Sold for a price'],
    'rent'   => ['For rent',  'Returned, for a fee'],
    'share'  => ['To borrow', 'Returned, free'],
    'donate' => ['Donation',  'Given away free'],
];
?>

<div class="page-head">
  <div>
    <h1>Edit listing</h1>
    <p class="subtitle">Changes are live as soon as you save.</p>
  </div>
  <a class="btn btn-ghost" href="<?= url('/listings/' . $id) ?>">View listing</a>
</div>

<?php if (isset($errors['form'])): ?>
  <div class="alert alert-error"><span><?= e($errors['form']) ?></span></div>
<?php endif; ?>

<form method="post" action="<?= url('/listings/' . $id . '/edit') ?>" enctype="multipart/form-data" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="image_id" id="image-id-field" value="">

  <div class="card mb-2">
    <div class="card-head"><h2>How are you offering it?</h2></div>

    <div class="radio-cards">
      <?php foreach ($typeBlurbs as $value => [$label, $blurb]): ?>
        <label class="radio-card">
          <input type="radio" name="listing_type" value="<?= e($value) ?>"
                 <?= $field('listing_type') === $value ? 'checked' : '' ?>>
          <strong><?= e($label) ?></strong>
          <span><?= e($blurb) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card mb-2">
    <div class="card-head"><h2>About the item</h2></div>

    <div class="form-group">
      <label for="title">Title</label>
      <input type="text" id="title" name="title" value="<?= e($field('title')) ?>"
             class="<?= isset($errors['title']) ? 'has-error' : '' ?>" required>
      <?php if (isset($errors['title'])): ?>
        <p class="field-error"><?= e($errors['title']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="description">Description</label>
      <textarea id="description" name="description"
                class="<?= isset($errors['description']) ? 'has-error' : '' ?>"
                required><?= e($field('description')) ?></textarea>
      <?php if (isset($errors['description'])): ?>
        <p class="field-error"><?= e($errors['description']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id">
          <option value="">Choose a category…</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?= (int) $category['category_id'] ?>"
                    <?= $field('category_id') === (string) $category['category_id'] ? 'selected' : '' ?>>
              <?= e($category['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="item_condition">Condition</label>
        <select id="item_condition" name="item_condition">
          <?php foreach (Listing::CONDITIONS as $c): ?>
            <option value="<?= e($c) ?>" <?= $field('item_condition') === $c ? 'selected' : '' ?>>
              <?= e(condition_label($c)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group" id="price-group">
        <label for="price">Price (Rs)</label>
        <input type="number" id="price" name="price" min="0" step="0.01"
               value="<?= e($field('price')) ?>"
               class="<?= isset($errors['price']) ? 'has-error' : '' ?>">
        <?php if (isset($errors['price'])): ?>
          <p class="field-error"><?= e($errors['price']) ?></p>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="location">Location</label>
        <input type="text" id="location" name="location" value="<?= e($field('location')) ?>">
      </div>
    </div>

    <div class="form-group">
      <label for="status">Availability</label>
      <select id="status" name="status">
        <option value="available" <?= $field('status') === 'available' ? 'selected' : '' ?>>Available — accepting requests</option>
        <option value="reserved"  <?= $field('status') === 'reserved'  ? 'selected' : '' ?>>Reserved — promised to someone</option>
        <option value="completed" <?= $field('status') === 'completed' ? 'selected' : '' ?>>Completed — gone</option>
      </select>
      <p class="field-hint">Only available items appear in browse results.</p>
    </div>
  </div>

  <div class="card mb-2">
    <div class="card-head"><h2>Photos</h2></div>

    <?php if (!empty($images)): ?>
      <div class="flex flex-wrap mb-2" style="gap:12px;">
        <?php foreach ($images as $image): ?>
          <div style="width:132px;">
            <img src="<?= url((string) $image['image_path']) ?>" alt=""
                 style="width:132px;height:100px;object-fit:cover;border-radius:5px;border:2px solid <?= $image['is_primary'] ? 'var(--blue)' : 'var(--border)' ?>;">
            <div class="flex gap-sm mt-1" style="justify-content:space-between;">
              <?php if ((int) $image['is_primary'] === 1): ?>
                <span class="badge badge-sell">Main</span>
              <?php else: ?>
                <button type="submit" name="action" value="make_primary" formnovalidate
                        class="btn btn-ghost btn-sm"
                        onclick="document.getElementById('image-id-field').value='<?= (int) $image['image_id'] ?>';">
                  Make main
                </button>
              <?php endif; ?>

              <button type="submit" name="action" value="remove_image" formnovalidate
                      class="btn btn-ghost btn-sm" style="color:var(--red);"
                      data-confirm="Remove this image?"
                      onclick="document.getElementById('image-id-field').value='<?= (int) $image['image_id'] ?>';">
                Remove
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="muted small">No photos yet.</p>
    <?php endif; ?>

    <?php if (count($images) < ListingImage::MAX_PER_LISTING): ?>
      <?php $room = ListingImage::MAX_PER_LISTING - count($images); ?>
      <div class="form-group">
        <label for="images">Add more images</label>
        <input type="file" id="images" name="images[]" accept="image/*" multiple data-preview-input>
        <p class="field-hint">Up to <?= ListingImage::MAX_PER_LISTING ?> in total — <?= $room ?> slot<?= $room === 1 ? '' : 's' ?> left.</p>
      </div>
      <div id="image-preview" class="flex flex-wrap gap-sm"></div>
    <?php endif; ?>
  </div>

  <div class="form-actions">
    <button type="submit" name="action" value="save" class="btn btn-lg">Save changes</button>
    <a class="btn btn-ghost" href="<?= url('/listings/' . $id) ?>">Cancel</a>
    <a class="btn btn-danger btn-sm" style="margin-left:auto;"
       href="<?= url('/listings/' . $id . '/delete') ?>">Delete listing</a>
  </div>
</form>
