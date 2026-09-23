<?php
/**
 * NexUse — home page.
 *
 * MVC layer: View.
 *
 * @var array<string, int>                        $stats
 * @var array<string, list<array<string, mixed>>> $sections
 */

use App\Core\View;

$sectionMeta = [
    'sell'   => ['For sale',  'Second-hand items looking for a new owner.'],
    'rent'   => ['For rent',  'Borrow it for a few days instead of buying it.'],
    'share'  => ['To borrow', 'Free to borrow, returned when you are done.'],
    'donate' => ['Donations', 'Given away free to whoever needs them.'],
];
?>

<div class="hero">
 <div class="hero-copy">
  <h1>The things you stopped using are exactly what someone else needs.</h1>
  <p>
    NexUse is one place to sell, rent, lend or donate what is sitting idle in your home —
    instead of four different apps, or a cupboard nobody opens.
  </p>
  <?php /* The main way in: search across every exchange type from the front page. */ ?>
  <form class="hero-search" method="get" action="<?= url('/browse') ?>" role="search">
    <label class="sr-only" for="hero-q">What are you looking for?</label>
    <input type="search" id="hero-q" name="q" placeholder="What are you looking for? Laptop, drill, textbooks…">

    <label class="sr-only" for="hero-type">Exchange type</label>
    <select id="hero-type" name="type">
      <option value="">All types</option>
      <?php foreach (['sell', 'rent', 'share', 'donate'] as $t): ?>
        <option value="<?= e($t) ?>"><?= e(listing_type_label($t)) ?></option>
      <?php endforeach; ?>
    </select>

    <button type="submit" class="btn">Search</button>
  </form>

  <p class="hero-suggest">
    Popular searches:
    <a href="<?= url('/browse?q=laptop') ?>">laptop</a> ·
    <a href="<?= url('/browse?q=bicycle') ?>">bicycle</a> ·
    <a href="<?= url('/browse?q=textbooks') ?>">textbooks</a> ·
    <a href="<?= url('/browse?type=donate') ?>">free donations</a>
  </p>

  <div class="btn-row">
    <a class="btn btn-lg" href="<?= url('/browse') ?>">Browse items</a>
    <?php if (is_logged_in()): ?>
      <a class="btn btn-lg btn-secondary" href="<?= url('/listings/create') ?>">Post an item</a>
    <?php else: ?>
      <a class="btn btn-lg btn-secondary" href="<?= url('/register') ?>">Create an account</a>
    <?php endif; ?>
  </div>
 </div>

  <?php /* The logo, filling what was empty space on the right. Decorative — the
           header already carries the brand name, so screen readers skip it. */ ?>
  <div class="hero-art" aria-hidden="true">
    <svg class="hero-mark" viewBox="0 0 32 32">
      <path d="M4 6h4l3.2 14.5h13.2" fill="none" stroke="#2563C9" stroke-width="2.6"
            stroke-linecap="round" stroke-linejoin="round"/>
      <path d="M22.5 8.2c1.9-1.9 5-1.9 6.9 0 1.9 1.9 1.9 5 0 6.9L23 21.5l-6.4-6.4c-1.9-1.9-1.9-5 0-6.9 1.9-1.9 5-1.9 6.9 0z"
            fill="#12945A"/>
      <circle cx="13" cy="26" r="2.1" fill="#1D4FA5"/>
      <circle cx="22" cy="26" r="2.1" fill="#1D4FA5"/>
    </svg>
    <span class="hero-wordmark"><span class="nex">Nex</span><span class="use">Use</span></span>
    <span class="hero-strap">Sell · Rent · Share · Donate</span>
  </div>
</div>

<div class="stat-grid">
  <div class="stat accent-blue">
    <div class="label">Items available</div>
    <div class="value"><?= number_format($stats['listings']) ?></div>
    <div class="foot">ready to sell, rent, borrow or receive</div>
  </div>
  <div class="stat accent-green">
    <div class="label">Exchanges completed</div>
    <div class="value"><?= number_format($stats['exchanges']) ?></div>
    <div class="foot">items that found a second use</div>
  </div>
  <div class="stat accent-violet">
    <div class="label">Members</div>
    <div class="value"><?= number_format($stats['members']) ?></div>
    <div class="foot">across Sri Lanka</div>
  </div>
  <div class="stat accent-amber">
    <div class="label">Listed as donations</div>
    <div class="value"><?= number_format($stats['donations']) ?></div>
    <div class="foot">given away at no cost</div>
  </div>
</div>

<?php foreach ($sections as $type => $items): ?>
  <?php [$heading, $blurb] = $sectionMeta[$type]; ?>
  <section class="section">
    <div class="section-head">
      <div>
        <h2><?= e($heading) ?></h2>
        <p class="muted small mb-0"><?= e($blurb) ?></p>
      </div>
      <a href="<?= url('/browse?type=' . $type) ?>">See all <?= e(strtolower($heading)) ?> →</a>
    </div>

    <div class="listing-grid">
      <?php foreach ($items as $item): ?>
        <?php View::partial('partials.listing_card', ['item' => $item]); ?>
      <?php endforeach; ?>
    </div>
  </section>
<?php endforeach; ?>

<?php if (empty($sections)): ?>
  <div class="empty">
    <div class="icon">📦</div>
    <h3>Nothing listed yet</h3>
    <p>Be the first to post something you no longer use.</p>
    <a class="btn" href="<?= is_logged_in() ? url('/listings/create') : url('/register') ?>">Post an item</a>
  </div>
<?php endif; ?>

<section class="section">
  <div class="card">
    <div class="card-head"><h2>Four ways to pass something on</h2></div>
    <div class="grid grid-4">
      <div>
        <span class="badge badge-sell mb-1">For sale</span>
        <p class="small muted">You list it, a buyer requests it, you agree the price and hand it over. Ownership transfers permanently.</p>
      </div>
      <div>
        <span class="badge badge-rent mb-1">For rent</span>
        <p class="small muted">Temporary use for a fee. You set the dates, the renter returns it, and you confirm its condition.</p>
      </div>
      <div>
        <span class="badge badge-share mb-1">To borrow</span>
        <p class="small muted">The same as renting, without the money. Lend a ladder to a neighbour and get it back.</p>
      </div>
      <div>
        <span class="badge badge-donate mb-1">Donation</span>
        <p class="small muted">Given away for good, at no cost, to someone who needs it more than your storage cupboard does.</p>
      </div>
    </div>
  </div>
</section>
