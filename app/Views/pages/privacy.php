<?php
/**
 * NexUse — privacy notice.
 *
 * MVC layer: View. Describes exactly what the eleven tables actually store,
 * which keeps this page honest rather than boilerplate.
 *
 * @var string $lastUpdated
 */
?>

<div class="page-head">
  <div>
    <h1>Privacy Notice</h1>
    <p class="subtitle">Last updated <?= e($lastUpdated) ?> · What NexUse stores, and why.</p>
  </div>
</div>

<div class="card legal">

  <h2>1. What we collect</h2>
  <p>Only what the platform needs to work:</p>

  <div class="table-wrap mb-2">
    <table>
      <thead><tr><th>Data</th><th>Why we hold it</th></tr></thead>
      <tbody>
        <tr><td>Name and email address</td><td>To identify your account and sign you in</td></tr>
        <tr><td>Password</td><td>Stored only as a bcrypt hash — never as text, and unreadable even to us</td></tr>
        <tr><td>Phone number, city, address</td><td>Optional. Shared with the other party only after a request is accepted</td></tr>
        <tr><td>Profile description and photo</td><td>Optional, and shown publicly if you add them</td></tr>
        <tr><td>Listings and their photographs</td><td>Shown publicly — that is the point of a listing</td></tr>
        <tr><td>Requests, rental dates, return condition</td><td>To track an exchange through to completion</td></tr>
        <tr><td>Messages</td><td>To deliver your conversation with the other member</td></tr>
        <tr><td>Reviews and ratings</td><td>Shown publicly on profiles, so members can judge who to deal with</td></tr>
        <tr><td>Complaints</td><td>Read by an administrator when investigating</td></tr>
      </tbody>
    </table>
  </div>

  <p>
    We do not collect payment card details, bank details or national identity numbers,
    because NexUse never handles money.
  </p>

  <h2>2. Who can see what</h2>
  <ul>
    <li><strong>Public</strong> — your name, city, profile description, listings, average
        rating and the reviews written about you.</li>
    <li><strong>The other party only</strong> — your email address and phone number, and
        only once a request has been accepted. Before that, neither of you sees the
        other's contact details.</li>
    <li><strong>Private to you</strong> — your password hash, your notifications, and the
        requests you have sent.</li>
    <li><strong>Administrators</strong> — may view accounts, listings, requests and, when
        investigating a complaint, the messages relevant to it.</li>
  </ul>

  <h2>3. Your email address</h2>
  <p>
    Your email address is your sign-in name. It is shown to the other party only once a
    request has been accepted. We do not sell it, and we do not send marketing.
  </p>

  <h2>4. Cookies</h2>
  <p>
    NexUse sets one cookie: the PHP session cookie that keeps you signed in. It is
    marked HttpOnly and SameSite, contains only a session identifier, and disappears when
    you sign out. There is no advertising or analytics tracking on this platform.
  </p>

  <h2>5. How long we keep things</h2>
  <ul>
    <li>Account data — until you ask for the account to be deleted.</li>
    <li>Listings, requests, reviews and messages — deleted with the account they belong to.</li>
  </ul>

  <h2>6. Your rights</h2>
  <p>
    You can view and correct your details on your <a href="<?= url('/profile/edit') ?>">profile
    page</a>, and change your password at any time. To have your account and everything in
    it deleted, contact an administrator. Reviews you wrote about other people may be
    retained in anonymised form so that their ratings remain meaningful.
  </p>

  <h2>7. Security</h2>
  <ul>
    <li>Passwords are hashed with bcrypt.</li>
    <li>Every database query uses prepared statements, so submitted text cannot alter it.</li>
    <li>Every form carries a CSRF token, so another site cannot act as you.</li>
    <li>Uploaded files are checked to be genuine images and stored under random names.</li>
    <li>Only the public folder is reachable from the web; application code and database
        credentials sit outside it.</li>
  </ul>
  <p>
    No system is perfectly secure. NexUse is a student project and has not undergone an
    independent security audit — please do not store anything on it you would be harmed
    by losing.
  </p>

  <h2>8. Contact</h2>
  <p>
    Privacy questions go to <a href="mailto:privacy@nexuse.lk">privacy@nexuse.lk</a>, or
    see the <a href="<?= url('/contact') ?>">contact page</a>.
  </p>

</div>

<p class="small muted center mt-3">
  See also the <a href="<?= url('/terms') ?>">Terms and Conditions</a>.
</p>
