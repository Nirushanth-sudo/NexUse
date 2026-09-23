<?php
/**
 * NexUse — one request in full.
 *
 * MVC layer: View. Member 2 · Read (detail) and the entry point for Update/Delete.
 *
 * @var array<string, mixed>      $request
 * @var bool                      $isOwner
 * @var bool                      $returnable
 * @var array<string, mixed>|null $myReview
 * @var array<string, string>     $errors
 */

$id     = (int) $request['request_id'];
$status = (string) $request['status'];

$otherName  = $isOwner ? $request['requester_name'] : $request['owner_name'];
$otherId    = (int) ($isOwner ? $request['requester_id'] : $request['owner_id']);
$otherEmail = $isOwner ? $request['requester_email'] : $request['owner_email'];
$otherPhone = $isOwner ? $request['requester_phone'] : $request['owner_phone'];

// Contact details are only exchanged once the owner has accepted.
$sharedContact = in_array($status, ['accepted', 'completed'], true);
?>

<nav class="breadcrumb">
  <a href="<?= url($isOwner ? '/requests/received' : '/requests/sent') ?>">
    <?= $isOwner ? 'Requests received' : 'Requests I sent' ?>
  </a> ›
  <span>#<?= $id ?></span>
</nav>

<div class="page-head">
  <div>
    <h1><?= e($request['listing_title']) ?></h1>
    <p class="subtitle">
      <?= e(request_type_label((string) $request['request_type'])) ?> request ·
      sent <?= e(time_ago((string) $request['created_at'])) ?>
    </p>
  </div>
  <span class="badge badge-<?= e($status) ?>" style="font-size:13px;padding:5px 12px;">
    <?= e($status === 'rejected' ? 'Declined' : ucfirst($status)) ?>
  </span>
</div>

<div class="card mb-2">
  <div class="card-head"><h2>Details</h2></div>

  <ul class="spec-list">
    <li>
      <span class="k">Item</span>
      <span class="v"><a href="<?= url('/listings/' . (int) $request['listing_id']) ?>"><?= e($request['listing_title']) ?></a></span>
    </li>
    <li>
      <span class="k"><?= $isOwner ? 'Requested by' : 'Owner' ?></span>
      <span class="v"><a href="<?= url('/users/' . $otherId) ?>"><?= e($otherName) ?></a></span>
    </li>
    <li>
      <span class="k">Type</span>
      <span class="v"><?= e(request_type_label((string) $request['request_type'])) ?></span>
    </li>
    <?php if (in_array($request['listing_type'], ['sell', 'rent'], true)): ?>
      <li>
        <span class="k"><?= $request['listing_type'] === 'rent' ? 'Rate' : 'Asking price' ?></span>
        <span class="v">
          <?= e(money($request['listing_price'])) ?><?= $request['listing_type'] === 'rent' ? ' per day' : '' ?>
        </span>
      </li>
    <?php endif; ?>
    <?php if ($returnable && !empty($request['start_date'])): ?>
      <li>
        <span class="k">Period</span>
        <span class="v">
          <?= e(short_date((string) $request['start_date'])) ?>
          → <?= e(short_date((string) $request['return_date'])) ?>
          (<?= days_between((string) $request['start_date'], (string) $request['return_date']) ?> days)
        </span>
      </li>
    <?php endif; ?>
    <?php if (!empty($request['actual_return_date'])): ?>
      <li>
        <span class="k">Returned on</span>
        <span class="v"><?= e(short_date((string) $request['actual_return_date'])) ?></span>
      </li>
      <li>
        <span class="k">Condition on return</span>
        <span class="v"><?= e(return_condition_label((string) $request['return_condition'])) ?></span>
      </li>
    <?php endif; ?>
  </ul>

  <?php if (!empty($request['message'])): ?>
    <h3 class="mt-3" style="font-size:14px;">Message from <?= e(explode(' ', (string) $request['requester_name'])[0]) ?></h3>
    <p style="white-space:pre-line;color:var(--ink-2);"><?= e($request['message']) ?></p>
  <?php endif; ?>

  <?php if (!empty($request['owner_note'])): ?>
    <h3 class="mt-3" style="font-size:14px;">Note from <?= e(explode(' ', (string) $request['owner_name'])[0]) ?></h3>
    <p style="white-space:pre-line;color:var(--ink-2);"><?= e($request['owner_note']) ?></p>
  <?php endif; ?>
</div>

<?php if ($sharedContact): ?>
  <div class="card mb-2">
    <div class="card-head"><h2>Arrange the handover</h2></div>
    <p class="small muted mb-2">
      NexUse does not handle payment or delivery. Contact each other directly to agree
      a time and place.
    </p>
    <ul class="spec-list">
      <li><span class="k">Name</span><span class="v"><?= e($otherName) ?></span></li>
      <li><span class="k">Email</span><span class="v"><a href="mailto:<?= e($otherEmail) ?>"><?= e($otherEmail) ?></a></span></li>
      <li><span class="k">Phone</span><span class="v"><?= $otherPhone ? e($otherPhone) : '—' ?></span></li>
    </ul>
  </div>
<?php endif; ?>

<?php /* ---- The owner answers a pending request ---- */ ?>
<?php if ($isOwner && $status === 'pending'): ?>
  <div class="card mb-2">
    <div class="card-head"><h2>Your decision</h2></div>

    <form method="post" action="<?= url('/requests/' . $id . '/respond') ?>">
      <?= csrf_field() ?>

      <?php if ($returnable): ?>
        <p class="small muted mb-2">You can adjust the dates before accepting.</p>
        <div class="form-row">
          <div class="form-group">
            <label for="start_date">From</label>
            <input type="date" id="start_date" name="start_date"
                   value="<?= e((string) $request['start_date']) ?>">
          </div>
          <div class="form-group">
            <label for="return_date">Returning on</label>
            <input type="date" id="return_date" name="return_date"
                   value="<?= e((string) $request['return_date']) ?>">
          </div>
        </div>
      <?php endif; ?>

      <div class="form-group">
        <label for="owner_note">Note back <span class="optional">(optional)</span></label>
        <textarea id="owner_note" name="owner_note" style="min-height:80px;"
                  placeholder="When and where they can collect it, or why you are declining."></textarea>
      </div>

      <div class="form-actions">
        <button type="submit" name="decision" value="accept" class="btn btn-success">Accept request</button>
        <button type="submit" name="decision" value="reject" class="btn btn-secondary"
                data-confirm="Decline this request?">Decline</button>
      </div>
    </form>
  </div>
<?php endif; ?>

<?php /* ---- The owner completes an accepted request ---- */ ?>
<?php if ($isOwner && $status === 'accepted'): ?>
  <div class="card mb-2">
    <div class="card-head"><h2><?= $returnable ? 'Has it come back?' : 'Is it handed over?' ?></h2></div>
    <p class="small muted mb-2">
      <?= $returnable
          ? 'Once the item is returned, record its condition to close the exchange.'
          : 'Once the item has changed hands, mark the exchange complete.' ?>
      Both of you can leave a review afterwards.
    </p>
    <a class="btn btn-success" href="<?= url('/requests/' . $id . '/return') ?>">
      <?= $returnable ? 'Confirm return' : 'Mark completed' ?>
    </a>
  </div>
<?php endif; ?>

<?php /* ---- The requester withdraws ---- */ ?>
<?php if (!$isOwner && in_array($status, ['pending', 'accepted'], true)): ?>
  <div class="card mb-2">
    <div class="card-head"><h2>Changed your mind?</h2></div>
    <p class="small muted mb-2">
      Withdrawing tells the owner you no longer need the item
      <?= $status === 'accepted' ? ' and puts it back on the market.' : '.' ?>
    </p>
    <form method="post" action="<?= url('/requests/' . $id . '/withdraw') ?>">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-secondary"
              data-confirm="Withdraw this request?">Withdraw request</button>
    </form>
  </div>
<?php endif; ?>

<?php /* ---- Review, once completed ---- */ ?>
<?php if ($status === 'completed'): ?>
  <div class="card">
    <div class="card-head"><h2>Review</h2></div>
    <?php if ($myReview !== null): ?>
      <p class="mb-1">
        You rated <?= e($otherName) ?>
        <span class="rating-stars"><?= e(stars((int) $myReview['rating'])) ?></span>
      </p>
      <?php if (!empty($myReview['comment'])): ?>
        <p class="small muted" style="white-space:pre-line;"><?= e($myReview['comment']) ?></p>
      <?php endif; ?>
      <div class="btn-row mt-2">
        <a class="btn btn-secondary btn-sm" href="<?= url('/reviews/' . (int) $myReview['review_id'] . '/edit') ?>">Edit review</a>
      </div>
    <?php else: ?>
      <p class="small muted mb-2">
        This exchange is complete. Leaving a review helps everyone else judge who to deal with.
      </p>
      <a class="btn" href="<?= url('/reviews/write/' . $id) ?>">Review <?= e($otherName) ?></a>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if (in_array($status, ['accepted', 'completed'], true)): ?>
  <p class="small muted center mt-3">
    Something went wrong with this exchange?
    <a href="<?= url('/complaints/report?user=' . $otherId . '&listing=' . (int) $request['listing_id']) ?>">
      Report a problem
    </a>
  </p>
<?php endif; ?>
