<?php
/**
 * NexUse — the "Donate to NexUse" dialog, opened from the footer.
 *
 * MVC layer: View partial. Rendered once by the layout, so it exists on every page.
 *
 * Works with JavaScript off: the footer button is a link to #donate, and CSS
 * :target shows this overlay. app.js upgrades it to a proper dialog — Escape
 * closes it, focus stays inside while it is open and returns afterwards, the
 * page behind stops scrolling, and each detail gets a Copy button.
 */

$donation = donation_details();

$rows = [
    'Account name'   => $donation['account_name'],
    'Account number' => $donation['account_number'],
    'Bank'           => $donation['bank'],
    'Branch'         => $donation['branch'],
    'SWIFT / BIC'    => $donation['swift'],
    'Reference'      => $donation['reference'],
];
?>
<div class="modal donate-modal" id="donate" role="dialog" aria-modal="true" aria-labelledby="donate-title">
  <a class="modal-backdrop" href="#" data-modal-close tabindex="-1" aria-hidden="true"></a>

  <div class="modal-panel donate-panel">
    <a class="modal-close" href="#" data-modal-close aria-label="Close">×</a>

    <div class="donate-icon" aria-hidden="true">♥</div>
    <h2 id="donate-title">Support NexUse</h2>
    <p class="donate-lead">
      NexUse is free to use — no listing fees and no commission on any exchange. Hosting and
      upkeep are paid for entirely by donations from the people who use it. Any amount helps.
    </p>

    <dl class="donate-details">
      <?php foreach ($rows as $label => $value): ?>
        <div class="donate-row">
          <dt><?= e($label) ?></dt>
          <dd><span data-copy-value><?= e($value) ?></span></dd>
        </div>
      <?php endforeach; ?>
    </dl>

    <p class="donate-foot">
      Please add the reference so your transfer can be matched. Donations support the
      platform itself — they are not payment for an item, which you still arrange directly
      with the other member. Questions? <a href="<?= url('/contact') ?>">Contact us</a>.
    </p>

    <a class="btn btn-secondary" href="#" data-modal-close>Close</a>
  </div>
</div>
