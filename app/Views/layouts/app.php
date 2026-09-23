<?php
/**
 * NexUse — site layout.
 *
 * MVC layer: View. Wraps every page rendered through View::render().
 * Receives $content (the rendered page) plus whatever the controller passed.
 *
 * @var string      $content
 * @var string|null $pageTitle
 * @var string|null $navActive
 * @var bool|null   $narrow
 * @var bool|null   $formWidth
 * @var bool|null   $hideFooter Sign in and create account drop the footer entirely
 */

use App\Core\Session;
use App\Core\View;
use App\Models\Conversation;
use App\Models\Notification;

$pageTitle = $pageTitle ?? 'NexUse';
$navActive = $navActive ?? '';
$user      = auth_user();
$unread    = $user !== null ? Notification::unreadCount((int) $user['user_id']) : 0;
$recent    = $user !== null ? Notification::recentForUser((int) $user['user_id']) : [];
$unreadMsg = $user !== null ? Conversation::unreadTotalFor((int) $user['user_id']) : 0;

$containerClass = 'container';
if (!empty($narrow))    { $containerClass .= ' container-narrow'; }
if (!empty($formWidth)) { $containerClass .= ' container-form'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · NexUse</title>
<meta name="description" content="NexUse — sell, rent, share and donate the things you no longer use.">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><text y='26' font-size='26'>♻️</text></svg>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<script src="<?= asset('js/app.js') ?>" defer></script>
</head>
<body>

<header class="site-header">
  <div class="container">

    <a class="brand" href="<?= url('/') ?>">
      <svg class="brand-mark" viewBox="0 0 32 32" aria-hidden="true">
        <path d="M4 6h4l3.2 14.5h13.2" fill="none" stroke="#2563C9" stroke-width="2.6"
              stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M22.5 8.2c1.9-1.9 5-1.9 6.9 0 1.9 1.9 1.9 5 0 6.9L23 21.5l-6.4-6.4c-1.9-1.9-1.9-5 0-6.9 1.9-1.9 5-1.9 6.9 0z"
              fill="#12945A"/>
        <circle cx="13" cy="26" r="2.1" fill="#1D4FA5"/>
        <circle cx="22" cy="26" r="2.1" fill="#1D4FA5"/>
      </svg>
      <span><span class="nex">Nex</span><span class="use">Use</span></span>
    </a>

    <button class="nav-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">☰</button>

    <nav class="main-nav">
      <a href="<?= url('/browse') ?>" class="<?= $navActive === 'browse' ? 'active' : '' ?>">Browse</a>
      <?php if ($user !== null): ?>
        <a href="<?= url('/listings') ?>"      class="<?= $navActive === 'listings' ? 'active' : '' ?>">My Listings</a>
        <a href="<?= url('/requests/received') ?>" class="<?= $navActive === 'requests' ? 'active' : '' ?>">Requests</a>
        <a href="<?= url('/messages') ?>" class="<?= $navActive === 'messages' ? 'active' : '' ?>">
          Messages<?php if ($unreadMsg > 0): ?><span class="nav-badge"><?= $unreadMsg > 9 ? '9+' : $unreadMsg ?></span><?php endif; ?>
        </a>
        <a href="<?= url('/reviews') ?>"       class="<?= $navActive === 'reviews' ? 'active' : '' ?>">Reviews</a>
        <a href="<?= url('/complaints') ?>"    class="<?= $navActive === 'complaints' ? 'active' : '' ?>">Complaints</a>
        <?php if ($user['role'] === 'admin'): ?>
          <a href="<?= url('/admin') ?>" class="<?= $navActive === 'admin' ? 'active' : '' ?>">Admin</a>
        <?php endif; ?>
      <?php else: ?>
        <a href="<?= url('/about') ?>" class="<?= $navActive === 'about' ? 'active' : '' ?>">How it works</a>
      <?php endif; ?>
    </nav>

    <div class="header-actions">
      <?php if ($user !== null): ?>

        <a class="btn btn-sm hide-sm" href="<?= url('/listings/create') ?>">+ Post an item</a>

        <?php /* A link to #notifications, so it opens without JavaScript too — see the partial. */ ?>
        <a class="notif-toggle" href="#notifications" data-modal-open="notifications">
          Notification
          <?php if ($unread > 0): ?>
            <span class="nav-badge"><?= $unread > 99 ? '99+' : $unread ?><span class="sr-only"> unread</span></span>
          <?php endif; ?>
        </a>

        <div class="account">
          <button class="account-toggle" type="button" data-dropdown="account-menu" aria-haspopup="true">
            <?= avatar($user) ?>
            <span class="hide-sm"><?= e(explode(' ', (string) $user['name'])[0]) ?></span>
          </button>

          <div class="menu" id="account-menu">
            <div class="menu-head">
              <strong><?= e($user['name']) ?></strong>
              <span><?= e($user['email']) ?></span>
            </div>
            <a href="<?= url('/profile') ?>">My profile</a>
            <a href="<?= url('/listings') ?>">My listings</a>
            <a href="<?= url('/requests/sent') ?>">Requests I sent</a>
            <a href="<?= url('/requests/received') ?>">Requests received</a>
            <a href="<?= url('/messages') ?>">Messages<?= $unreadMsg > 0 ? ' (' . $unreadMsg . ')' : '' ?></a>
            <a href="<?= url('/reviews') ?>">My reviews</a>
            <a href="<?= url('/complaints') ?>">My complaints</a>
            <a href="<?= url('/notifications') ?>">Notifications</a>
            <?php if ($user['role'] === 'admin'): ?>
              <div class="menu-sep"></div>
              <a href="<?= url('/admin') ?>">Admin dashboard</a>
            <?php endif; ?>
            <div class="menu-sep"></div>
            <a href="<?= url('/profile/edit') ?>">Edit profile</a>
            <form method="post" action="<?= url('/logout') ?>">
              <?= csrf_field() ?>
              <button type="submit">Sign out</button>
            </form>
          </div>
        </div>

      <?php else: ?>
        <a class="btn btn-secondary btn-sm" href="<?= url('/login') ?>">Sign in</a>
        <a class="btn btn-sm" href="<?= url('/register') ?>">Create account</a>
      <?php endif; ?>
    </div>

  </div>
</header>

<main class="page">
  <div class="<?= e($containerClass) ?>">
    <?php foreach (Session::takeFlash() as $message): ?>
      <div class="alert alert-<?= e($message['type']) ?>">
        <span><?= e($message['message']) ?></span>
      </div>
    <?php endforeach; ?>

    <?= $content ?>
  </div>
</main>

<?php /* Sign in and create account are single-task pages: no footer, so nothing
         leads away from the form. */ ?>
<?php if (empty($hideFooter)): ?>
<footer class="site-footer">
  <div class="container">

    <?php /* A link to #donate, so it opens without JavaScript too — see the partial. */ ?>
    <div class="donate-band">
      <div>
        <strong>NexUse runs on donations.</strong>
        <span>No fees, no commission, no adverts. If it has helped you, help keep it free.</span>
      </div>
      <a class="btn btn-donate" href="#donate" data-modal-open="donate">♥ Donate to NexUse</a>
    </div>

    <div class="footer-grid">

      <div class="footer-brand">
        <a class="brand" href="<?= url('/') ?>">
          <svg class="brand-mark" viewBox="0 0 32 32" aria-hidden="true">
            <path d="M4 6h4l3.2 14.5h13.2" fill="none" stroke="#2563C9" stroke-width="2.6"
                  stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M22.5 8.2c1.9-1.9 5-1.9 6.9 0 1.9 1.9 1.9 5 0 6.9L23 21.5l-6.4-6.4c-1.9-1.9-1.9-5 0-6.9 1.9-1.9 5-1.9 6.9 0z"
                  fill="#12945A"/>
            <circle cx="13" cy="26" r="2.1" fill="#1D4FA5"/>
            <circle cx="22" cy="26" r="2.1" fill="#1D4FA5"/>
          </svg>
          <span><span class="nex">Nex</span><span class="use">Use</span></span>
        </a>
        <p class="footer-tagline">
          Sell, rent, share and donate the things you no longer use — one platform for
          Sri Lanka's circular economy.
        </p>
        <p class="footer-note">
          NexUse never handles payments or delivery. You arrange those directly.
        </p>
      </div>

      <div class="footer-col">
        <h4>Explore</h4>
        <a href="<?= url('/browse') ?>">Browse all items</a>
        <a href="<?= url('/browse?type=sell') ?>">For sale</a>
        <a href="<?= url('/browse?type=rent') ?>">For rent</a>
        <a href="<?= url('/browse?type=share') ?>">To borrow</a>
        <a href="<?= url('/browse?type=donate') ?>">Donations</a>
      </div>

      <div class="footer-col">
        <h4>Your account</h4>
        <?php if ($user !== null): ?>
          <a href="<?= url('/listings/create') ?>">Post an item</a>
          <a href="<?= url('/listings') ?>">My listings</a>
          <a href="<?= url('/requests/sent') ?>">My requests</a>
          <a href="<?= url('/messages') ?>">Messages</a>
          <a href="<?= url('/profile') ?>">My profile</a>
        <?php else: ?>
          <a href="<?= url('/register') ?>">Create an account</a>
          <a href="<?= url('/login') ?>">Sign in</a>
          <a href="<?= url('/about') ?>">How it works</a>
        <?php endif; ?>
      </div>

      <div class="footer-col">
        <h4>Help &amp; legal</h4>
        <a href="<?= url('/about') ?>">How it works</a>
        <a href="<?= url('/contact') ?>">Contact us</a>
        <a href="<?= url('/complaints/report') ?>">Report a problem</a>
        <a href="<?= url('/terms') ?>">Terms &amp; conditions</a>
        <a href="<?= url('/privacy') ?>">Privacy notice</a>
      </div>

      <div class="footer-col footer-contact">
        <h4>Contact</h4>
        <address>
          <span class="contact-line">
            <span aria-hidden="true">📍</span>
            214 Union Place,<br>Colombo 02, Sri Lanka
          </span>
          <span class="contact-line">
            <span aria-hidden="true">✉️</span>
            <a href="mailto:hello@nexuse.lk">hello@nexuse.lk</a>
          </span>
          <span class="contact-line">
            <span aria-hidden="true">📞</span>
            <a href="tel:+94112345678">+94 11 234 5678</a>
          </span>
          <span class="contact-line">
            <span aria-hidden="true">🕘</span>
            Mon–Fri, 9.00am – 5.00pm
          </span>
        </address>
      </div>

    </div>

    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> NexUse</span>
      <span class="footer-bottom-links">
        <a href="<?= url('/terms') ?>">Terms</a>
        <a href="<?= url('/privacy') ?>">Privacy</a>
        <a href="<?= url('/contact') ?>">Contact</a>
      </span>
    </div>

  </div>
</footer>

<?php View::partial('partials.donate_modal'); ?>
<?php endif; ?>

<?php if ($user !== null): ?>
  <?php View::partial('partials.notif_modal', ['recent' => $recent, 'unread' => $unread]); ?>
<?php endif; ?>

<?php Session::forgetOld(); ?>
</body>
</html>
