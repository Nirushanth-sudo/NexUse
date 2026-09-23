<?php
/**
 * NexUse — contact page.
 *
 * MVC layer: View. The details here are the ones repeated in the site footer.
 */
?>

<div class="page-head">
  <div>
    <h1>Contact us</h1>
    <p class="subtitle">Who to reach, and which route gets you an answer fastest.</p>
  </div>
</div>

<div class="alert alert-info">
  <span>
    <strong>Having trouble with another member?</strong> Don't email — use
    <a href="<?= url('/complaints/report') ?>">Report a problem</a>. It reaches an
    administrator with the listing and the other member already attached.
  </span>
</div>

<div class="grid grid-2 mb-3">
  <div class="card">
    <h3>General enquiries</h3>
    <p class="mb-1"><a href="mailto:hello@nexuse.lk">hello@nexuse.lk</a></p>
    <p class="small muted mb-0">Questions about how the platform works.</p>
  </div>
  <div class="card">
    <h3>Support</h3>
    <p class="mb-1"><a href="mailto:support@nexuse.lk">support@nexuse.lk</a></p>
    <p class="small muted mb-0">Account problems and sign-in trouble.</p>
  </div>
  <div class="card">
    <h3>Privacy</h3>
    <p class="mb-1"><a href="mailto:privacy@nexuse.lk">privacy@nexuse.lk</a></p>
    <p class="small muted mb-0">Your data, and requests to delete an account.</p>
  </div>
  <div class="card">
    <h3>Legal</h3>
    <p class="mb-1"><a href="mailto:legal@nexuse.lk">legal@nexuse.lk</a></p>
    <p class="small muted mb-0">Terms of use and content takedown requests.</p>
  </div>
</div>

<div class="card mb-3">
  <div class="card-head"><h2>Where we are</h2></div>
  <ul class="spec-list">
    <li><span class="k">Address</span>
        <span class="v">NexUse, 214 Union Place, Colombo 02, Sri Lanka</span></li>
    <li><span class="k">Telephone</span>
        <span class="v"><a href="tel:+94112345678">+94 11 234 5678</a></span></li>
    <li><span class="k">Hours</span>
        <span class="v">Monday to Friday, 9.00am – 5.00pm</span></li>
    <li><span class="k">Response time</span>
        <span class="v">Two working days</span></li>
  </ul>
</div>

<div class="card">
  <div class="card-head"><h2>About this project</h2></div>
  <p>
    NexUse is a second-year group project by <strong>CS-31</strong>, building a single
    platform for Sri Lanka's circular economy — selling, renting, sharing and donating in
    one place instead of four.
  </p>
  <p class="mb-0 small muted">
    It is an academic project rather than a registered business. The contact details above
    are for the project team.
  </p>
</div>
