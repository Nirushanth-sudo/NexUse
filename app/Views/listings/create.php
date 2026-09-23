<?php
/**
 * NexUse — post a new item.
 *
 * MVC layer: View. Member 1 · Create.
 *
 * @var list<array<string, mixed>> $categories
 * @var array<string, string>      $errors
 * @var array<string, mixed>       $user
 */

use App\Models\Listing;

$typeBlurbs = [
    'sell'   => ['For sale',  'Sold for a price'],
    'rent'   => ['For rent',  'Returned, for a fee'],
    'share'  => ['To borrow', 'Returned, free'],
    'donate' => ['Donation',  'Given away free'],
];

$selectedType = (string) old('listing_type', 'sell');
?>

<div class="page-head">
  <div>
    <h1>Post an item</h1>
    <p class="subtitle">Sell it, rent it out, lend it, or give it away — you choose.</p>
  </div>
  <a class="btn btn-ghost" href="<?= url('/listings') ?>">← My listings</a>
</div>

<?php if (isset($errors['form'])): ?>
  <div class="alert alert-error"><span><?= e($errors['form']) ?></span></div>
<?php endif; ?>

<form method="post" action="<?= url('/listings/create') ?>" enctype="multipart/form-data" novalidate>
  <?= csrf_field() ?>

  <div class="card mb-2">
    <div class="card-head"><h2>How are you offering it?</h2></div>

    <div class="radio-cards">
      <?php foreach ($typeBlurbs as $value => [$label, $blurb]): ?>
        <label class="radio-card">
          <input type="radio" name="listing_type" value="<?= e($value) ?>"
                 <?= $selectedType === $value ? 'checked' : '' ?>>
          <strong><?= e($label) ?></strong>
          <span><?= e($blurb) ?></span>
        </label>
      <?php endforeach; ?>
    </div>

    <?php if (isset($errors['listing_type'])): ?>
      <p class="field-error"><?= e($errors['listing_type']) ?></p>
    <?php endif; ?>
  </div>

  <div class="card mb-2">
    <div class="card-head"><h2>About the item</h2></div>

    <div class="form-group">
      <label for="title">Title</label>
      <input type="text" id="title" name="title" value="<?= e(old('title')) ?>"
             class="<?= isset($errors['title']) ? 'has-error' : '' ?>"
             placeholder="Bosch rotary hammer drill" required>
      <?php if (isset($errors['title'])): ?>
        <p class="field-error"><?= e($errors['title']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="description">Description</label>
      <textarea id="description" name="description"
                class="<?= isset($errors['description']) ? 'has-error' : '' ?>"
                placeholder="What is it, what condition is it in, what is included, and anything the other person should know."
                required><?= e(old('description')) ?></textarea>
      <?php if (isset($errors['description'])): ?>
        <p class="field-error"><?= e($errors['description']) ?></p>
      <?php else: ?>
        <p class="field-hint">Be honest about faults — it saves a dispute later.</p>
      <?php endif; ?>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id" class="<?= isset($errors['category_id']) ? 'has-error' : '' ?>">
          <option value="">Choose a category…</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?= (int) $category['category_id'] ?>"
                    <?= (string) old('category_id') === (string) $category['category_id'] ? 'selected' : '' ?>>
              <?= e($category['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['category_id'])): ?>
          <p class="field-error"><?= e($errors['category_id']) ?></p>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="item_condition">Condition</label>
        <select id="item_condition" name="item_condition">
          <?php foreach (Listing::CONDITIONS as $c): ?>
            <option value="<?= e($c) ?>" <?= (string) old('item_condition', 'good') === $c ? 'selected' : '' ?>>
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
               value="<?= e(old('price')) ?>"
               class="<?= isset($errors['price']) ? 'has-error' : '' ?>">
        <?php if (isset($errors['price'])): ?>
          <p class="field-error"><?= e($errors['price']) ?></p>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="location">Location</label>
        <input type="text" id="location" name="location"
               value="<?= e(old('location', $user['city'] ?? '')) ?>" placeholder="Colombo">
        <p class="field-hint">Where the item can be collected.</p>
      </div>
    </div>
  </div>

  <div class="card mb-2">
    <div class="card-head"><h2>Photos <span class="muted small" style="font-weight:400;">optional</span></h2></div>

    <div class="form-group">
      <label for="images">Upload up to 5 images</label>
      <input type="file" id="images" name="images[]" accept="image/*" multiple data-preview-input>
      <p class="field-hint">JPG, PNG, GIF or WEBP, up to 2 MB each. The first becomes the main photo.</p>
    </div>

    <div id="image-preview" class="flex flex-wrap gap-sm"></div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-lg">Post item</button>
    <a class="btn btn-ghost" href="<?= url('/listings') ?>">Cancel</a>
  </div>
</form>
