<?php
/**
 * NexUse — browse and search listings.
 *
 * MVC layer: View. Member 1 · Read.
 *
 * @var list<array<string, mixed>>  $listings
 * @var list<array<string, mixed>>  $categories
 * @var array<string, mixed>        $filters
 * @var string                      $sort
 * @var int                         $total, $page, $pages
 * @var bool                        $hasFilters
 */

use App\Core\View;
use App\Models\Listing;

/** Rebuild the current query string with some values replaced. */
$browseUrl = static function (array $overrides = []) use ($filters, $sort): string {
    $query = array_filter([
        'q'         => $filters['keyword'],
        'type'      => $filters['type'],
        'category'  => $filters['category'],
        'condition' => $filters['condition'],
        'newness'   => $filters['newness'],
        'location'  => $filters['location'],
        'min_price' => $filters['min_price'],
        'max_price' => $filters['max_price'],
        'sort'      => $sort === 'newest' ? '' : $sort,
    ], static fn($v) => $v !== '' && $v !== null);

    $query = array_filter(array_merge($query, $overrides), static fn($v) => $v !== '' && $v !== null);

    return url('/browse' . (empty($query) ? '' : '?' . http_build_query($query)));
};
?>

<div class="page-head">
  <div>
    <h1>Browse items</h1>
    <p class="subtitle">Everything currently offered to sell, rent, borrow or receive.</p>
  </div>
  <?php if (is_logged_in()): ?>
    <a class="btn" href="<?= url('/listings/create') ?>">+ Post an item</a>
  <?php endif; ?>
</div>

<div class="tabs">
  <a href="<?= $browseUrl(['type' => null, 'page' => null]) ?>" class="<?= $filters['type'] === '' ? 'active' : '' ?>">All items</a>
  <?php foreach (Listing::TYPES as $t): ?>
    <a href="<?= $browseUrl(['type' => $t, 'page' => null]) ?>" class="<?= $filters['type'] === $t ? 'active' : '' ?>">
      <?= e(listing_type_label($t)) ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="split">

  <?php /* filter-aside pins the filters in place while the items scroll (app.css §19). */ ?>
  <aside class="filter-aside">
    <form class="filter-bar" method="get" action="<?= url('/browse') ?>">
      <?php if ($filters['type'] !== ''): ?>
        <input type="hidden" name="type" value="<?= e($filters['type']) ?>">
      <?php endif; ?>

      <div class="form-group">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($filters['keyword']) ?>" placeholder="Laptop, drill, textbooks…">
      </div>

      <div class="form-group">
        <label for="category">Category</label>
        <select id="category" name="category">
          <option value="">All categories</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int) $c['category_id'] ?>"
                    <?= (int) $filters['category'] === (int) $c['category_id'] ? 'selected' : '' ?>>
              <?= e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="newness">New or used</label>
        <select id="newness" name="newness">
          <option value=""     <?= $filters['newness'] === ''     ? 'selected' : '' ?>>Either</option>
          <option value="new"  <?= $filters['newness'] === 'new'  ? 'selected' : '' ?>>New only</option>
          <option value="used" <?= $filters['newness'] === 'used' ? 'selected' : '' ?>>Used only</option>
        </select>
      </div>

      <div class="form-group">
        <label for="condition">Condition in detail</label>
        <select id="condition" name="condition">
          <option value="">Any condition</option>
          <?php foreach (Listing::CONDITIONS as $c): ?>
            <option value="<?= e($c) ?>" <?= $filters['condition'] === $c ? 'selected' : '' ?>>
              <?= e(condition_label($c)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php /* Guests do not see item locations, so they cannot search by one
               either — otherwise the filter would give away what the page hides. */ ?>
      <?php if (is_logged_in()): ?>
        <div class="form-group">
          <label for="location">Location</label>
          <input type="text" id="location" name="location" value="<?= e($filters['location']) ?>" placeholder="Colombo">
        </div>
      <?php else: ?>
        <div class="form-group">
          <label>Location</label>
          <p class="field-hint mb-0">
            🔒 <a href="<?= url('/login') ?>">Sign in</a> to see item locations and search by area.
          </p>
        </div>
      <?php endif; ?>

      <div class="form-row">
        <div class="form-group">
          <label for="min_price">Price from (Rs)</label>
          <input type="number" id="min_price" name="min_price" min="0" step="100"
                 value="<?= e($filters['min_price']) ?>" placeholder="Any">
        </div>
        <div class="form-group">
          <label for="max_price">Price up to (Rs)</label>
          <input type="number" id="max_price" name="max_price" min="0" step="100"
                 value="<?= e($filters['max_price']) ?>" placeholder="Any">
        </div>
      </div>

      <div class="btn-row">
        <button type="submit" class="btn btn-sm">Apply filters</button>
        <?php if ($hasFilters): ?>
          <a class="btn btn-ghost btn-sm" href="<?= url('/browse') ?>">Clear</a>
        <?php endif; ?>
      </div>
    </form>
  </aside>

  <div>
    <?php
      /* One chip per applied filter, each removable on its own. Built here in
         the view because it is purely a presentation of $filters. */
      $categoryNames = [];
      foreach ($categories as $c) {
          $categoryNames[(int) $c['category_id']] = (string) $c['name'];
      }

      $chips = [];
      if ($filters['keyword'] !== '')   { $chips[] = ['q',         '“' . $filters['keyword'] . '”']; }
      if ($filters['type'] !== '')      { $chips[] = ['type',      listing_type_label($filters['type'])]; }
      if (!empty($filters['category'])) { $chips[] = ['category',  $categoryNames[(int) $filters['category']] ?? 'Category']; }
      if ($filters['newness'] !== '')   { $chips[] = ['newness',   ucfirst($filters['newness']) . ' only']; }
      if ($filters['condition'] !== '') { $chips[] = ['condition', condition_label($filters['condition'])]; }
      if ($filters['location'] !== '')  { $chips[] = ['location',  'In ' . $filters['location']]; }
      if ($filters['min_price'] !== '') { $chips[] = ['min_price', 'From Rs ' . number_format((float) $filters['min_price'])]; }
      if ($filters['max_price'] !== '') { $chips[] = ['max_price', 'Up to Rs ' . number_format((float) $filters['max_price'])]; }
    ?>

    <?php if (!empty($chips)): ?>
      <div class="filter-chips">
        <span class="chips-label">Filtering by</span>
        <?php foreach ($chips as [$key, $label]): ?>
          <a class="chip" href="<?= $browseUrl([$key => null, 'page' => null]) ?>">
            <?= e($label) ?><span class="chip-x" aria-hidden="true">×</span>
            <span class="sr-only">Remove this filter</span>
          </a>
        <?php endforeach; ?>
        <a class="chip chip-clear" href="<?= url('/browse') ?>">Clear all</a>
      </div>
    <?php endif; ?>

    <div class="flex-between flex-wrap mb-2">
      <p class="result-count mb-0">
        <?= $total === 0 ? 'No items found' : number_format($total) . ' item' . ($total === 1 ? '' : 's') ?>
        <?= $hasFilters ? ' matching your filters' : '' ?>
      </p>

      <form method="get" action="<?= url('/browse') ?>" class="flex gap-sm">
        <?php foreach (['q' => $filters['keyword'], 'type' => $filters['type'],
                        'category' => $filters['category'], 'condition' => $filters['condition'],
                        'newness' => $filters['newness'], 'location' => $filters['location'],
                        'min_price' => $filters['min_price'],
                        'max_price' => $filters['max_price']] as $key => $value): ?>
          <?php if ($value !== '' && $value !== null): ?>
            <input type="hidden" name="<?= e($key) ?>" value="<?= e((string) $value) ?>">
          <?php endif; ?>
        <?php endforeach; ?>

        <label for="sort" class="small mb-0" style="white-space:nowrap;">Sort by</label>
        <select id="sort" name="sort" data-auto-submit style="width:auto;">
          <?php if ($filters['keyword'] !== ''): ?>
            <option value="relevance" <?= $sort === 'relevance' ? 'selected' : '' ?>>Best match</option>
          <?php endif; ?>
          <option value="newest"     <?= $sort === 'newest' ? 'selected' : '' ?>>Newest first</option>
          <option value="oldest"     <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest first</option>
          <option value="price_low"  <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: low to high</option>
          <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: high to low</option>
          <option value="title"      <?= $sort === 'title' ? 'selected' : '' ?>>Title A–Z</option>
        </select>
      </form>
    </div>

    <?php if (empty($listings)): ?>
      <div class="empty">
        <div class="icon">🔍</div>
        <h3>Nothing matches that</h3>
        <p>Try a broader search, or clear the filters to see everything available.</p>
        <a class="btn btn-secondary" href="<?= url('/browse') ?>">Clear filters</a>
      </div>
    <?php else: ?>
      <div class="listing-grid">
        <?php foreach ($listings as $item): ?>
          <?php View::partial('partials.listing_card', ['item' => $item]); ?>
        <?php endforeach; ?>
      </div>

      <?php if ($pages > 1): ?>
        <nav class="pagination">
          <?php if ($page > 1): ?>
            <a href="<?= $browseUrl(['page' => $page - 1]) ?>">← Previous</a>
          <?php else: ?>
            <span class="disabled">← Previous</span>
          <?php endif; ?>

          <?php for ($p = 1; $p <= $pages; $p++): ?>
            <?php if ($p === $page): ?>
              <span class="current"><?= $p ?></span>
            <?php else: ?>
              <a href="<?= $browseUrl(['page' => $p]) ?>"><?= $p ?></a>
            <?php endif; ?>
          <?php endfor; ?>

          <?php if ($page < $pages): ?>
            <a href="<?= $browseUrl(['page' => $page + 1]) ?>">Next →</a>
          <?php else: ?>
            <span class="disabled">Next →</span>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>

</div>
