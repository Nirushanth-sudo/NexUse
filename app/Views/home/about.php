<?php
/**
 * NexUse — how it works.
 *
 * MVC layer: View.
 */
?>

<div class="page-head">
  <div>
    <h1>How NexUse works</h1>
    <p class="subtitle">Four ways to pass something on, one process for all of them.</p>
  </div>
</div>

<div class="card mb-3">
  <div class="card-head"><h2>The process</h2></div>

  <ol style="padding-left:20px;color:var(--ink-2);">
    <li class="mb-1"><strong>Someone lists an item.</strong> They choose whether it is for sale, for rent, free to borrow, or a donation, and add photos and a description.</li>
    <li class="mb-1"><strong>Someone else sends a request.</strong> Browse or search, open the item, and ask for it — with a message and, for rentals and borrowing, the dates you need it.</li>
    <li class="mb-1"><strong>The owner accepts or rejects.</strong> Nothing is automatic. The owner decides, and both people are notified either way.</li>
    <li class="mb-1"><strong>You arrange the handover yourselves.</strong> NexUse does not handle payment or delivery — you agree that directly, which keeps the platform simple and free.</li>
    <li class="mb-1"><strong>Returnable items come back.</strong> For rentals and borrowing, the owner marks the item returned and records its condition.</li>
    <li><strong>You rate each other.</strong> Once an exchange is complete, both sides can leave a review, which builds the reputation everyone else relies on.</li>
  </ol>
</div>

<div class="grid grid-2 mb-3">
  <div class="card">
    <span class="badge badge-sell mb-1">For sale</span>
    <h3>Reselling</h3>
    <p class="muted small">Permanent transfer of ownership, with payment. You list a price, agree it with the buyer, and hand the item over. Payment happens between the two of you.</p>
  </div>
  <div class="card">
    <span class="badge badge-rent mb-1">For rent</span>
    <h3>Renting</h3>
    <p class="muted small">Temporary use, with payment. You set a daily rate and the rental period. The item must come back, and you confirm what condition it arrives in.</p>
  </div>
  <div class="card">
    <span class="badge badge-share mb-1">To borrow</span>
    <h3>Sharing</h3>
    <p class="muted small">Temporary use, at no cost. The same tracking as a rental — start date, return date, condition on return — without any money changing hands.</p>
  </div>
  <div class="card">
    <span class="badge badge-donate mb-1">Donation</span>
    <h3>Donating</h3>
    <p class="muted small">Permanent transfer, at no cost. You give the item away for good to whoever asks and you choose to accept.</p>
  </div>
</div>

<div class="card">
  <div class="card-head"><h2>What NexUse does not do</h2></div>
  <ul style="padding-left:20px;color:var(--ink-2);">
    <li>It does not take payments, hold money in escrow, or issue refunds.</li>
    <li>It does not arrange delivery or take responsibility for transporting items.</li>
    <li>It records disputes and passes them to an administrator, but does not mediate or enforce outcomes.</li>
    <li>It does not provide insurance or enforce legal penalties. It is a way for two people to find each other.</li>
  </ul>
</div>

<?php if (!is_logged_in()): ?>
  <div class="empty mt-3">
    <h3>Ready to start?</h3>
    <p>Create an account to post items and send requests.</p>
    <div class="btn-row" style="justify-content:center;">
      <a class="btn" href="<?= url('/register') ?>">Create account</a>
      <a class="btn btn-secondary" href="<?= url('/browse') ?>">Just browse first</a>
    </div>
  </div>
<?php endif; ?>
