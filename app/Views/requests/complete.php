<?php
/**
 * NexUse — complete an exchange / confirm a return.
 *
 * MVC layer: View. Member 2 · Update.
 *
 * @var array<string, mixed>  $request
 * @var bool                  $returnable
 * @var array<string, string> $errors
 */

use App\Models\ItemRequest;

$id = (int) $request['request_id'];
?>

<div class="card">
  <div class="card-head">
    <h1 style="font-size:20px;margin:0;">
      <?= $returnable ? 'Confirm the return' : 'Complete the exchange' ?>
    </h1>
  </div>

  <p class="mb-2">
    <strong><?= e($request['listing_title']) ?></strong> —
    <?= e(request_type_label((string) $request['request_type'])) ?> with
    <?= e($request['requester_name']) ?>.
  </p>

  <?php if ($returnable && !empty($request['return_date'])): ?>
    <?php
    $due  = (string) $request['return_date'];
    $late = strtotime($due) < strtotime(date('Y-m-d'));
    ?>
    <div class="alert alert-<?= $late ? 'warning' : 'info' ?>">
      <span>
        Agreed return date was <strong><?= e(short_date($due)) ?></strong><?php
        if ($late) {
            echo ' — that is ' . days_between($due, date('Y-m-d')) . ' day(s) ago.';
        } else {
            echo '.';
        }
        ?>
      </span>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= url('/requests/' . $id . '/return') ?>" novalidate>
    <?= csrf_field() ?>

    <?php if ($returnable): ?>
      <div class="form-group">
        <label for="actual_return_date">Date it came back</label>
        <input type="date" id="actual_return_date" name="actual_return_date"
               value="<?= e(old('actual_return_date', date('Y-m-d'))) ?>"
               class="<?= isset($errors['actual_return_date']) ? 'has-error' : '' ?>" required>
        <?php if (isset($errors['actual_return_date'])): ?>
          <p class="field-error"><?= e($errors['actual_return_date']) ?></p>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="return_condition">What condition was it in?</label>
        <select id="return_condition" name="return_condition"
                class="<?= isset($errors['return_condition']) ? 'has-error' : '' ?>">
          <?php foreach (ItemRequest::RETURN_CONDITIONS as $c): ?>
            <option value="<?= e($c) ?>" <?= (string) old('return_condition', 'as_given') === $c ? 'selected' : '' ?>>
              <?= e(return_condition_label($c)) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['return_condition'])): ?>
          <p class="field-error"><?= e($errors['return_condition']) ?></p>
        <?php else: ?>
          <p class="field-hint">
            Recorded on the exchange. NexUse does not enforce compensation — if there is
            a problem, report it and an administrator will see it.
          </p>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="alert alert-info">
        <span>
          This marks the item as handed over for good. The listing will be closed and
          both of you can leave a review.
        </span>
      </div>
    <?php endif; ?>

    <div class="form-group">
      <label for="owner_note">Note <span class="optional">(optional)</span></label>
      <textarea id="owner_note" name="owner_note" style="min-height:80px;"
                placeholder="Anything worth recording about how it went."><?= e(old('owner_note')) ?></textarea>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-success">
        <?= $returnable ? 'Confirm return and complete' : 'Mark completed' ?>
      </button>
      <a class="btn btn-ghost" href="<?= url('/requests/' . $id) ?>">Cancel</a>
    </div>
  </form>
</div>
