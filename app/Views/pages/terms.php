<?php
/**
 * NexUse — terms and conditions.
 *
 * MVC layer: View.
 *
 * The scope and out-of-scope statements here mirror §3 of the project proposal,
 * so the legal page and the specification say the same thing.
 *
 * @var string $lastUpdated
 */
?>

<div class="page-head">
  <div>
    <h1>Terms and Conditions</h1>
    <p class="subtitle">Last updated <?= e($lastUpdated) ?> · Please read these before using NexUse.</p>
  </div>
</div>

<div class="alert alert-info">
  <span>
    <strong>The short version.</strong> NexUse introduces people who have things to
    people who need them. You deal with each other directly — we do not take payment,
    arrange delivery, or guarantee anybody's behaviour.
  </span>
</div>

<div class="card legal">

  <h2>1. About these terms</h2>
  <p>
    NexUse ("the platform", "we") is a web application that lets members sell, rent,
    lend and donate items to one another in Sri Lanka. By creating an account or using
    the platform you agree to these terms. If you do not agree, please do not use NexUse.
  </p>
  <p>
    NexUse is an academic project built by student group CS-31. It is not a registered
    commercial entity, and it is offered without warranty.
  </p>

  <h2>2. Eligibility and your account</h2>
  <ul>
    <li>You must be at least 18, or have the consent of a parent or guardian.</li>
    <li>You must give an accurate name and a working email address.</li>
    <li>One person, one account. You are responsible for everything done under yours.</li>
    <li>Keep your password to yourself. Tell us immediately if you believe someone else
        has access to your account.</li>
    <li>An administrator may suspend or remove an account that breaks these terms.</li>
  </ul>

  <h2>3. The four kinds of exchange</h2>
  <p>Every listing on NexUse is one of four types, and the type decides what is promised:</p>

  <div class="table-wrap mb-2">
    <table>
      <thead><tr><th>Type</th><th>Ownership</th><th>Payment</th><th>Returned?</th></tr></thead>
      <tbody>
        <tr><td><span class="badge badge-sell">For sale</span></td><td>Transfers permanently</td><td>Yes</td><td>No</td></tr>
        <tr><td><span class="badge badge-rent">For rent</span></td><td>Stays with the owner</td><td>Yes</td><td>Yes, by the agreed date</td></tr>
        <tr><td><span class="badge badge-share">To borrow</span></td><td>Stays with the owner</td><td>No</td><td>Yes, by the agreed date</td></tr>
        <tr><td><span class="badge badge-donate">Donation</span></td><td>Transfers permanently</td><td>No</td><td>No</td></tr>
      </tbody>
    </table>
  </div>

  <p>
    Posting a listing is an invitation to ask, not a binding offer. Sending a request is
    not a binding contract either. An agreement exists only once the owner accepts a
    request, and even then it is an agreement between the two of you — not with NexUse.
  </p>

  <h2>4. What NexUse does not do</h2>
  <p>These limits are deliberate and are part of what you are agreeing to:</p>
  <ul>
    <li><strong>No payments.</strong> We do not process, hold or escrow money, and we
        issue no refunds. Money changes hands directly between members, outside the
        platform, by whatever method you agree.</li>
    <li><strong>No delivery.</strong> We do not transport items, employ couriers, or
        track deliveries. Collection and handover are arranged by you.</li>
    <li><strong>No verification of items.</strong> We do not inspect, test, value or
        authenticate anything listed.</li>
    <li><strong>No mediation.</strong> We record disputes and pass them to an
        administrator. We do not adjudicate, enforce compensation, or take sides.</li>
    <li><strong>No insurance.</strong> Loss, theft or damage to an item is a matter
        between the members involved.</li>
  </ul>

  <h2>5. Your responsibilities as a lister</h2>
  <ul>
    <li>Only list items you actually own and are legally entitled to pass on.</li>
    <li>Describe items honestly, including faults. Photographs should be of the actual
        item.</li>
    <li>State the price, rental rate and any conditions clearly and up front.</li>
    <li>Respond to requests within a reasonable time, and decline rather than ignore.</li>
    <li>Keep your listing status current, and remove it once the item is gone.</li>
  </ul>

  <h2>6. Your responsibilities as a requester</h2>
  <ul>
    <li>Only request items you genuinely intend to take.</li>
    <li>For rentals and borrowing, return the item by the agreed date, in the condition
        you received it, allowing for fair wear.</li>
    <li>Tell the owner promptly if an item is damaged, lost, or will be late.</li>
    <li>Withdraw a request you no longer need, so the item goes back to someone else.</li>
  </ul>

  <h2>7. Items that may not be listed</h2>
  <p>You may not list anything that is illegal to own or transfer in Sri Lanka, or:</p>
  <ul>
    <li>Weapons, ammunition, explosives, or anything designed to injure.</li>
    <li>Drugs, controlled substances, prescription medicines, alcohol or tobacco.</li>
    <li>Live animals.</li>
    <li>Counterfeit, stolen, or recalled goods.</li>
    <li>Human remains, bodily fluids or organs.</li>
    <li>Hazardous or perishable material that cannot be transferred safely.</li>
    <li>Adult material, or anything that sexualises a minor.</li>
    <li>Personal data, account credentials, or anything whose transfer would breach
        somebody else's privacy.</li>
    <li>Services, employment, currency or financial instruments — NexUse is for items.</li>
  </ul>

  <h2>8. Behaviour</h2>
  <p>When using listings, requests, messages and reviews, you agree not to:</p>
  <ul>
    <li>Harass, threaten, defame or discriminate against another member.</li>
    <li>Post reviews that are dishonest, or that you were paid or pressured to write.</li>
    <li>Use the messaging feature to advertise, spam, or solicit off-platform schemes.</li>
    <li>Impersonate somebody else, or misrepresent your connection to an item.</li>
    <li>Attempt to gain unauthorised access to the platform, other accounts, or data.</li>
    <li>Scrape, automate or overload the service.</li>
  </ul>

  <h2>9. Reviews and reputation</h2>
  <p>
    Reviews may only be left by the two parties to a completed exchange, and are shown
    publicly on member profiles. Reviews are the opinion of the member who wrote them,
    not of NexUse. An administrator may remove a review that is abusive, defamatory, or
    plainly false, but is not obliged to police accuracy.
  </p>

  <h2>10. Messages</h2>
  <p>
    Messages between members are private to those two people, except that an
    administrator may read a thread when investigating a report. Do not send payment
    details, passwords, or identity documents through messages.
  </p>

  <h2>11. Content you post</h2>
  <p>
    You keep ownership of your listings, photographs, messages and reviews. By posting
    them you give NexUse permission to store and display them for the purpose of running
    the platform. You confirm you have the right to post what you upload. Content that
    breaks these terms may be removed without notice.
  </p>

  <h2>12. Suspension and termination</h2>
  <p>
    You may stop using NexUse at any time. We may suspend or delete an account that
    breaches these terms, is the subject of repeated upheld complaints, or is being used
    to defraud other members. Deleting an account removes its listings, requests, reviews
    and messages.
  </p>

  <h2>13. Liability</h2>
  <p>
    NexUse is provided "as is", without warranty of any kind. To the fullest extent
    permitted by law, we are not liable for any loss, damage, injury, cost or dispute
    arising from an exchange arranged through the platform, from an item's condition or
    legality, or from another member's conduct. Your dealings with other members are at
    your own risk.
  </p>

  <h2>14. Governing law</h2>
  <p>
    These terms are governed by the laws of the Democratic Socialist Republic of Sri
    Lanka, and the courts of Sri Lanka have exclusive jurisdiction over any dispute
    arising from them.
  </p>

  <h2>15. Changes to these terms</h2>
  <p>
    We may update these terms as the platform develops. The date at the top shows when
    they last changed. Continuing to use NexUse after a change means you accept the
    revised terms.
  </p>

  <h2>16. Contact</h2>
  <p>
    Questions about these terms go to <a href="mailto:legal@nexuse.lk">legal@nexuse.lk</a>,
    or see the <a href="<?= url('/contact') ?>">contact page</a>. To report a problem with
    another member, use <a href="<?= url('/complaints/report') ?>">Report a problem</a>,
    which reaches an administrator directly.
  </p>

</div>

<p class="small muted center mt-3">
  See also the <a href="<?= url('/privacy') ?>">Privacy Notice</a> and
  <a href="<?= url('/about') ?>">How it works</a>.
</p>
