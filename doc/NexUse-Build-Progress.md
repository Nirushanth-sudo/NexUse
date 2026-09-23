# NexUse — Build Progress

*Status of the implementation described in `NexUse-Build-Plan.md`.
Last updated: 17 September 2026 — feature round 6 built and verified (demo accounts moved
to a text file, OTP removed, donation warning and footer credit removed, admin menu on
every admin page, notifications as a centred pop-up).*

---

## Summary

| | |
|---|---|
| **Architecture** | MVC (Model–View–Controller), hand-rolled PHP, no framework |
| **Phases** | 0–7 complete, plus feature rounds 2 to 6 |
| **PHP files** | 87 — 10 Core, 10 Models, 14 Controllers, 46 Views, 1 Helper, 6 entry/config |
| **Routes** | 54, all in `routes.php` |
| **PHP syntax check** | ✅ All 87 files pass `php -l` |
| **Database** | ✅ Live — 10 tables, seeded (6 users, 20 listings, 12 requests, 2 chats). Round 6 dropped `email_otps` and `users.email_verified_at` |
| **Authentication** | ✅ **Criterion 1 verified** — register, login, logout, guards |
| **CRUD operations** | ✅ **Criterion 3 verified** — all 16 operations exercised |
| **Cross-module chain** | ✅ Request → notification → reserve → complete → review |
| **Navigable UI** | 🔄 Routes respond correctly; manual click-through still advised |
| **Feature round 2** | ✅ Five features built and verified — see below |
| **Feature round 3** | ✅ Seven changes built and verified, including one real bug fix |
| **Feature round 4** | ✅ Six changes built and verified in headless Chrome, including one bug caught on a screenshot |
| **Feature round 5** | ✅ Full width on every page; 33 pages measured at desktop and phone width; two older bugs found and fixed |
| **Feature round 6** | ✅ Six changes; 27 HTTP checks and headless-Chrome checks of the new pop-up and admin pages all pass |

### Verification actually performed

Run against a live database with the dev server up. Every result below was observed, not
assumed.

**Authentication — criterion 1**

| Check | Result |
|---|---|
| Register a brand-new account | ✅ 302 → home, profile shows the new name |
| Log in as admin | ✅ 302 → `/admin` |
| Log in as member | ✅ 302 → home |
| Wrong password | ✅ rejected, back to `/login` |
| Member requesting `/admin` | ✅ 302 → home, refused |
| Signed-out on 7 protected routes | ✅ all 302 → `/login` |
| POST with no CSRF token | ✅ 419 refused |

**CRUD — criterion 3**

| Member | Entity | C | R | U | D |
|---|---|---|---|---|---|
| 1 | `listings` | ✅ created id 21 | ✅ read back | ✅ title/price/condition changed | ✅ deleted, then 404 |
| 2 | `requests` | ✅ request sent | ✅ listed and detailed | ✅ accepted, then completed | ✅ withdrawn |
| 3 | `reviews` | ✅ 5-star review written | ✅ shows on public profile | ✅ edited to 4 stars | ✅ deleted |
| 4 | `notifications` | ✅ raised by the request | ✅ listed with unread count | ✅ mark read / mark all read | ✅ dismissed |

Supporting sets also exercised: complaints (create/edit/delete), admin categories
(create/rename/delete), admin complaint resolution, admin broadcast.

**Integration** — one chain, run start to finish: Dilan requested Nimali's teak desk →
Nimali received a notification → she accepted → **listing flipped to `reserved`
automatically** → she completed the exchange → Dilan's review unlocked → the review
appeared on Nimali's public profile.

**Public pages** — `/`, `/about`, `/browse`, `/login`, `/register`, `/listings/{id}`,
`/users/{id}` all 200. Home stat tiles and the admin dashboard render real seeded figures
(6 users, 20 listings, 12 requests, 6 reviews). Non-existent ids correctly 404.

> Superseded in feature round 3: `/users/{id}` is no longer public. It now redirects a
> signed-out visitor to `/login`, by design — see round 3, feature 5. Everything else in
> this list is unchanged.

The database was reset to clean seed state afterwards, so no test data remains.

---

## Feature round 2 — added 4 September 2026

Five features requested after the interim build was verified. All are live and tested,
and none of them broke criterion 1 or 3 — the regression checks are recorded below.

### 1. Terms and Conditions page · `/terms`

Sixteen sections written against the actual system rather than boilerplate: the four
exchange types and what each one promises, the prohibited-items list, and an explicit
"what NexUse does not do" section mirroring the out-of-scope statement in §3 of the
proposal. A **Privacy Notice** (`/privacy`) came with it, describing exactly what the
eleven tables store and who can see each field.

| File | Layer |
|---|---|
| `app/Controllers/PageController.php` | Controller |
| `app/Views/pages/terms.php`, `privacy.php`, `contact.php` | View |

### 2. Site footer with contact details and navigation

The old footer was a single line of links. It is now a five-column layout: brand and
tagline, Explore (the four exchange types), Your account (which changes for signed-in
members), Help & legal, and a Contact column with address, email, phone and opening
hours — plus a bottom bar with copyright and legal links. Collapses to two columns on
tablet and one on phone.

Rebuilt in `app/Views/layouts/app.php`; styling in section 17 of `app.css`.

### 3. Related items on a listing page · `/listings/{id}`

A "Related items" strip above the existing "More from this owner" section.

Ranked rather than filtered, so it is never empty while anything relevant exists:

| Signal | Score |
|---|---|
| Same category | 4 |
| Same exchange type | 2 |
| Same location | 1 |

Verified on listing 1 (Electronics / sell / Colombo): the Samsung TV scored 6
(Electronics + sell), the projector and camera body 4 (Electronics, different type), the
teak desk 3 (different category, but sell + Colombo). Reserved, completed and removed
listings are excluded, as is the listing being viewed.

Implemented as `Listing::related()` — one query, no N+1.

### 4. Member-to-member chat

Lets an interested member talk to whoever listed an item — seller, lender or donor —
before or after sending a formal request. The proposal listed in-app chat as the
differentiator against ikman.lk; this is that feature.

| File | Layer |
|---|---|
| `app/Models/Conversation.php`, `Message.php` | Model |
| `app/Controllers/MessageController.php` | Controller |
| `app/Views/messages/inbox.php`, `thread.php` | View |

- One thread per (listing, interested member), enforced by a unique key.
- Inbox showing the last message, relative time and an unread count per thread.
- Unread badge in the main navigation and the account menu.
- Sending raises a `message` notification for the other party.
- Opening a thread marks the other side's messages read.
- Chat bubbles with day separators and read receipts; opens scrolled to the newest line.

Deliberately a plain request/response form — no polling, no WebSockets — because the
proposal commits to vanilla PHP and JavaScript with no external libraries.

**Access control tested:** a third party requesting someone else's thread gets **403**;
an owner trying to message their own listing is redirected to their inbox.

### 5. Email verification by OTP

> *Removed in round 6, at the group's request. Registration signs a new member straight
> in again; `Mailer`, `EmailOtp`, the verification page, its two routes and the
> `email_otps` table are gone. This section is kept as a record of what was built.*

Registration no longer signs you straight in. It creates the account, issues a six-digit
code, and sends you to `/verify-email`. Only a correct code completes registration.

| File | Layer |
|---|---|
| `app/Core/Mailer.php` | Core service |
| `app/Models/EmailOtp.php` | Model |
| `AuthController::verifyEmail()`, `resendOtp()`, `dispatchOtp()` | Controller |
| `app/Views/auth/verify_email.php` | View |

Security properties, all verified:

| Property | Value |
|---|---|
| Code storage | bcrypt hash — the code itself is never stored |
| Expiry | 10 minutes |
| Wrong guesses | 5, then the code is dead |
| Resend cooldown | 60 seconds |
| Reuse | Single use; issuing a new code kills the old one |

An account that abandons verification is not locked out: signing in later returns it to
the verification screen with a fresh code.

> **On actually sending the email.** This machine has no mail server, and PHP here has no
> `openssl` extension, so it cannot open the TLS connection that Gmail, Outlook and every
> hosted SMTP relay require. Real delivery is impossible without installing an MTA or
> enabling openssl and using a relay.
>
> So `Mailer` has two drivers. `log` (the default here) writes the message to
> `storage/mail/` as a `.eml` file, and while `debug` is on the verification page also
> shows the code in a clearly-marked development panel. `mail` hands off to PHP's
> `mail()` for a server that has an MTA.
>
> **The verification logic is real either way** — generation, hashing, expiry, attempt
> limits and cooldown are the same code on both paths. Only the transport differs, and it
> changes with one line in `config/config.local.php`. Say this plainly in the demo rather
> than implying email is being delivered.

### Database changes

`database/migration_002_features.sql` — additive, safe to run on a populated database.

| Change | Purpose |
|---|---|
| `conversations`, `messages` tables | Chat |
| `email_otps` table | Verification codes |
| `users.email_verified_at` | When the address was confirmed |
| `notifications.type` gains `'message'` | Chat notifications |

Existing accounts were stamped verified by the migration, so nobody was locked out.
`schema.sql` and `seed.sql` were updated too, so a fresh install gets all eleven tables
and two demo conversations.

### Verification of round 2

| Check | Result |
|---|---|
| `/terms`, `/privacy`, `/contact` | ✅ all 200 |
| Footer contact details on a public page | ✅ address, email, phone, legal links present |
| Related items on `/listings/1` | ✅ 4 items, correctly ranked 6 / 4 / 4 / 3 |
| Chat: start thread, send, notify, reply, mark read | ✅ full cycle |
| Chat: outsider access | ✅ 403 |
| Chat: message your own listing | ✅ redirected, refused |
| OTP: register → not signed in, sent to `/verify-email` | ✅ |
| OTP: `.eml` written containing the code | ✅ |
| OTP: wrong code rejected, attempt counted | ✅ |
| OTP: correct code → verified and signed in | ✅ |
| OTP: resend cooldown | ✅ 60s enforced |
| OTP: stored only as a bcrypt hash, consumed after use | ✅ |
| **Regression:** seeded admin and member still sign in | ✅ criterion 1 intact |
| Syntax / routes / views | ✅ 88 files, 56 routes, 50 view refs — all resolve |

### A third bug, found while doing this

`.gitignore` still ignored `assets/uploads/*`, but the MVC refactor moved that folder to
`public/assets/uploads/`. Uploaded images would have been committed to the repository.
Fixed.

---

## Feature round 3 — added 4 September 2026

Seven changes requested after round 2. Six are new capability; **number 7 was a real
rendering bug**, and fixing it turned out to fix the admin dashboard as well.

This round added **no new files and no new routes** — 88 PHP files and 56 routes before
and after. Everything extends code that already existed, which is the clearest evidence
so far that the MVC split is doing its job.

### 1. Show what you typed in a password box

Every `input[type="password"]` in the system gets a **Show / Hide** button — sign in,
create account, change password, and the admin's create-user form. Four forms, no view
changes: the button is built in JavaScript at page load.

That is deliberate. If the button were written into the HTML it would sit there dead when
scripting is off; built in JavaScript it simply never appears. The control is also given
`tabindex="-1"` so tabbing through a form still goes straight to the next field, and the
caret is restored after each toggle so you can keep typing.

Implemented in `initPasswordToggles()` in `public/assets/js/app.js`, styled in section 18
of `app.css`.

### 2. Product searching options

Search was previously only reachable from the `/browse` sidebar. It is now:

| Where | What it does |
|---|---|
| **Site header** | A search box on every page, on every screen wider than 900px — *removed in round 4, as it did not fit the header* |
| **Home hero** | A large search box with an exchange-type selector, plus four popular-search links |
| **Browse sidebar** | The full filter set |

And the search itself is wider:

| Addition | Detail |
|---|---|
| Category name is searched | "electronics" now returns the five items in that category, not just the ones with the word in their text |
| Price **range** | `min_price` joins the existing `max_price` |
| New / used filter | See feature 3 |
| **Best match** sort | With a keyword, a title hit outranks a description-only hit |
| Oldest first | The counterpart to Newest first |
| **Filter chips** | Every applied filter shown as a removable chip, each dropping just that one |

Best match becomes the default sort as soon as a keyword is present, which is what a
search box is expected to do.

All of it is plain `GET`: results stay bookmarkable and shareable, and searching works
with JavaScript off.

### 3. New or used on sale listings

`item_condition` has five values, but a buyer's first question is binary. So a sale
listing now carries a plain **New** or **Used** flag — green on the card thumbnail,
beside the price on the detail page — with the precise wording (Like new, Good, Fair,
Poor) kept in the tooltip and the spec list.

The flag appears on **sale listings only**. A rental or a loan is used by definition, and
a badge there would be noise.

`condition_flag()` and `shows_condition_flag()` in `app/Helpers/functions.php` decide it;
the browse sidebar gained a matching **New only / Used only** filter.

> **One seed value changed.** No seeded listing was `item_condition = 'new'`, so the New
> badge and the "New only" filter could never be demonstrated. Listing 6 (the gas cooker)
> is now a boxed, unused wedding gift — entirely plausible on a reuse marketplace, and it
> gives the demo something to point at. The listing count is unchanged at 20.

### 4. Item locations are for signed-in members

A location turns a listing into a doorstep, so it is now behind the sign-in wall. Guests
see **Sign in to see** wherever a place name would appear: listing cards on the home page
and browse, the Location row on a listing, and the owner's city.

**The filter had to go too.** Hiding a field in the view is not enough on its own — a
guest who could still request `?location=Colombo` would learn every Colombo item from the
result set, which is exactly what the hiding was for. So `ListingController::browse()`
drops the location filter when nobody is signed in, and the sidebar replaces that field
with a sign-in prompt.

Verified both ways: as a guest, `?location=Colombo` returns the same 19 items as an
unfiltered browse; signed in, it correctly narrows to 5 (and Kandy to 4).

### 5. A member's profile page is for signed-in members

`/users/{id}` now opens with `Auth::requireLogin()`. A member's profile carries their
city, their whole catalogue and every review about them — enough to identify a real
person.

Guests are sent to sign in and returned afterwards, via the existing
`redirect_after_login` mechanism. The links a guest cannot follow were removed rather
than left to bounce: the owner's name on a listing is plain text for a guest, "View
profile" becomes "Sign in to view profile", and a short line explains what signing in
would show. Audited: **zero** `/users/` links are rendered to a signed-out visitor
anywhere on the site.

### 6. Profile pictures

`users.avatar_path` has been in `schema.sql` since Phase 0 and was never used. It is now
wired end to end — **no migration needed**.

- Upload from `/profile/edit`, with a live preview before saving.
- A tick-box to remove the picture and go back to initials.
- Validated by the same `Core\Upload::image()` that handles listing photos: 2 MB cap,
  `getimagesize()` check, extension allowlist, random filename, relative path stored.
- Replacing or removing a picture **deletes the old file**, so the uploads folder does not
  accumulate dead images.
- A new `avatar()` view helper renders the photo where there is one and the initials
  circle where there is not, so a member with no picture looks exactly as before.

The helper replaced all twelve hand-written avatar circles, and `avatar_path` was threaded
through the review, conversation and request queries that feed them — so the picture
follows a member into the header, both profile pages, listing pages, the message inbox and
thread, review lists, request tables and the admin user list.

### 7. The rating breakdown bars — a genuine bug

**Reported:** the breakdown showed no difference between a 5-star count and a 1-star
count. All five bars looked identically empty.

**Cause:** the bars are `<span>` elements, and `.bar-track` / `.bar-fill` set `height` and
`width` without setting `display`. Both properties are **ignored on an inline box**, so
the amber fill was painted at zero size and every row looked the same. The percentage in
the markup was correct all along — it was never being drawn.

**Fix:** `display: block` on both, plus `min-width: 3px` so a single review still draws
something visible. Two lines of CSS.

**It was not only the profile page.** `admin/dashboard.php` draws its listings-by-type and
requests-by-status charts with the same classes and the same `<span>` elements, so those
bars were blank too. One CSS fix repaired both screens.

**Also changed, while there:** the bars were scaled against the largest bucket, so two
reviews at 5 and 4 stars both filled 100% and looked identical. They are now a share of
the **total**, which is how a rating breakdown is normally read — 50% and 50% for that
case — with the count, the percentage and a "1 of 2 reviews gave 5 stars" tooltip on each
row.

### Verification of round 3

Run against the live database with the dev server up. Every result was observed.

| Check | Result |
|---|---|
| Password toggle attaches to every password box | OK — login 1, register 2, change-password 3, admin create-user 1 |
| Header search renders on public, member and admin pages | OK — 5/5 page types *(since removed — round 4)* |
| Home hero search + popular links | OK — present, `?q=` and `?type=` both work |
| Keyword matches a category name | OK — "Electronics" returns 5 items |
| Price range `min_price` + `max_price` | OK — 3 / 12 / 2 items for the three cases |
| **Best match** puts a title hit above a newer description hit | OK — proven with a temporary listing, then removed |
| New / used filter | OK — new 1, used 18 |
| New badge on the card and beside the price | OK — listing 6 New, listing 1 Used |
| Guest: locations hidden | OK — 12 prompts on browse, 15 on home, 0 place names |
| Guest: location filter removed **and ignored** | OK — `?location=Colombo` returns all 19 |
| Member: locations visible, filter works | OK — 12 names shown; Colombo 5, Kandy 4 |
| Guest: `/users/{id}` | OK — 302 to `/login` |
| Guest: no `/users/` links rendered anywhere | OK — 0 across 5 page types |
| Member: `/users/{id}` | OK — 200 |
| Avatar upload, stored, served, rendered | OK — 302, file on disk, `200 image/png` |
| Avatar shows on other members' pages | OK — listing page and message inbox |
| Avatar rejects a non-image | OK — "That file is not a readable image", old picture untouched |
| Avatar replace deletes the old file | OK — previous file gone from disk |
| Avatar remove returns to initials | OK — `avatar_path` NULL, file deleted, "NP" circle |
| Rating breakdown draws real widths | OK — 50% / 50% / 0 / 0 / 0 with counts and tooltips |
| Admin dashboard bars (same fix) | OK — 25% / 20% / 25% / 8% all drawing |
| **Regression:** 27 routes tested | OK — public 200, member 200, admin 200 |
| **Regression:** guards | OK — guest `/profile` 302, member `/admin` 302, bad id 404 |
| **Regression:** criterion 1, admin and member sign-in | OK — both 302 to the right place |
| Syntax | OK — 88 PHP files pass `php -l`; `app.js` passes `node --check` |
| Routes and views | OK — 56 routes resolve; 52 view references resolve |
| PHP warnings or notices in the server log | OK — none |

### Two things caught while doing this

**A query with more placeholders than parameters.** Adding `other_avatar` to
`ItemRequest::awaitingReviewBy()` introduced a sixth `?` into a statement that still bound
five values — which would have thrown on the "To review" tab. Found by counting
placeholders against parameters across every query in the file, and fixed. Worth
repeating in the report: when you add a column to a `CASE WHEN ... ?` expression, you add
a bind, not just a column.

**A duplicated column.** A scripted edit applied the same avatar column twice to
`Conversation::findDetailed()`, which MySQL would have rejected as a duplicate alias.
Caught by reading the file back after the edit rather than trusting that it applied
cleanly.

---

## Feature round 4 — added 13 September 2026

Six changes. **One new file** (`app/Views/partials/donate_modal.php`), no new routes, no
schema change — 89 PHP files, 56 routes, 11 tables.

This round was mostly about *layout*, which `curl` cannot check: it proves the markup is
there, not that a panel stays pinned or a dialog fits a phone. So everything below was
also verified in **headless Chrome** — see "How the layout was verified".

### 1. Header search removed

It did not fit the header. The form is gone from `layouts/app.php`, along with its CSS and
the phone-width rule that existed only to hide it. Searching still works from the home
page hero and the browse sidebar. The round-3 rows that describe the header search are
marked as superseded, and demo step 09 in the interim plan now starts from the home page.

### 2. Donations — a full-screen dialog from the footer

Every page's footer now opens with a band — **"NexUse runs on donations"** — and a green
**Donate to NexUse** button. It opens a full-screen overlay with the bank details:

| Shown | Detail |
|---|---|
| Account name, account number, bank, branch, SWIFT / BIC, reference | One row each, with a **Copy** button |
| Close | × button, a Close button, **Escape**, or a click on the dimmed backdrop |
| On a phone | The panel fills the screen edge to edge and the rows stack |

| File | Layer |
|---|---|
| `app/Views/partials/donate_modal.php` (new) | View partial, rendered once by the layout |
| `donation_details()` in `app/Helpers/functions.php` | View helper |
| `layouts/app.php` — footer band | View |
| `initDonateModal()`, `copyText()` in `app.js` | Presentation |
| `app.css` §19 | Presentation |
| `config/config.local.example.php` — documented `donation` block | Configuration |

> *Round 6 removed the "Sample details" warning from the dialog, as asked. The sample
> values themselves are unchanged — see outstanding item 8.*
>
> **The bank details are not filled in, deliberately.** They come from a `donation` block
> in `config/config.local.php`. Until an account number is set, the dialog shows obviously
> fake values (`0000 0000 0000`, `XXXXLKLX`) under a **"Sample details — do not transfer
> money"** warning, and while `debug` is on it also says where to set them. An invented
> account number that *looks* real, on a live donate screen, is how someone sends real
> money to nowhere. See the outstanding list.

**It works with JavaScript off.** The button is a link to `#donate` and CSS `:target`
shows the overlay. JavaScript upgrades it: focus moves to Close and is kept inside the
dialog, the page behind stops scrolling, focus returns to the button on close, the address
bar is left clean, and the Copy buttons appear (built in JavaScript, so there is no dead
button without it). It also adds a `js` class to `<html>` so the stylesheet stops relying
on `:target` — `history.replaceState()` does not clear `:target`, so without that the
dialog would stay on screen after closing.

### 3. The filters stay put while the items scroll

The filter panel is `position: sticky`, pinned 16px below the header. The items scroll
past it; the panel does not move. If the panel is ever taller than the window it scrolls
on its own, and `overscroll-behavior: contain` stops that spilling into the page.

A design note: the **page** still scrolls — the page title and type tabs move up out of
view, and the header stays. The alternative, a separate scroll box around the items, would
put a second scrollbar inside the page and trap the footer beneath it.

Below 960px the browse page is a single column, so the panel stacks above the items as
before; pinning it there would cover the items it filters.

### 4. "Free items are always included in the upper bound" removed

Only the sentence went. The behaviour is unchanged: a maximum price still includes free
items, because the rule in `Listing::buildFilters()` — `price IS NULL OR price <= ?` — is
untouched.

### 5. Full-width browse page

> *Superseded in round 5: every page is now full width, so the `wide` flag and the
> `.container-wide` class described here were removed.*

`ListingController::browse()` passes `'wide' => true`; the layout turns that into
`.container-wide` — no maximum width, a 24px gutter. At 1894px wide the grid now shows
**six** columns of items instead of four.

Only the browse page is wide. The header and footer keep their 1140px width, so on that
one page the logo sits further in than the content edge. That was a judgement call — see
the outstanding list.

### 6. "Notification" as text

The bell emoji is replaced by the word **Notification**, with the unread count as a red
badge beside it. The button's `aria-label` was removed so its accessible name matches the
visible text, and the badge carries a screen-reader-only "unread". The class was renamed
from `.bell` to `.notif-toggle`, and the old `.bell` and `.bell-count` rules deleted.

The dropdown's heading and the account-menu link still read "Notifications", plural.

> *Round 6 replaced the dropdown with a centred pop-up — see round 6, change 6.*

### A bug caught on a screenshot

The first screenshot showed the footer button's label as **grey text on green** — barely
legible. `.site-footer a` sets every footer link to muted grey, and at specificity (0,1,1)
it beat the bare `.btn-donate` (0,1,0). The markup and the `curl` checks were all correct;
only looking at it exposed this. Fixed by scoping the rule to `.site-footer .btn-donate`,
then confirmed: computed colour `rgb(255, 255, 255)` on `rgb(18, 148, 90)`.

### How the layout was verified

Headless Chrome, with the real pages loaded inside fixed-size iframes on a temporary
same-origin test page. The page scrolled them, clicked the buttons, pressed Escape, and
read back `getBoundingClientRect()` and computed styles. Both temporary test pages were
deleted from `public/` afterwards.

One false alarm worth recording: with three iframes on one test page, the "arrive on a
`#donate` link" case reported focus on `<body>`. Rerun alone in a single iframe, focus was
on the Close button at 50, 300 and 1000ms. Frames on one page compete for focus, so a
multi-frame harness is not a reliable way to test focus.

### Verification of round 4

| Check | Result |
|---|---|
| Header search markup, guest and member | OK — 0 |
| Header search CSS rules left | OK — 0 |
| Donate band and dialog present | OK — on `/`, `/browse`, `/terms`, `/login` |
| Dialog rows and warning while unconfigured | OK — 6 rows, "Sample details" warning shown |
| Click the footer button | OK — opens, focus on Close, no `#donate` left in the URL |
| Escape | OK — closes, page scroll unlocked, focus back on the button |
| Arrive on `/#donate` | OK — opens, focus on Close |
| Phone width (390px) | OK — panel 368px, no overflow, 6 Copy buttons |
| Footer button colour | OK — white on green, after the specificity fix |
| Filters pinned | OK — `sticky`; panel top 235px at rest, **78px after 300px of scroll** (62px header + 16px), while the items' top moved to −6px |
| Full width | OK — container starts at 0, right edge at the scrollbar, 6 grid columns |
| "Upper bound" hint | OK — absent |
| Notification text, member | OK — "Notification", badge 3 = 3 unread in the database, no emoji |
| **Regression:** 10 member routes | OK — all 200; guest `/users/3` still 302 |
| **Regression:** criterion 1 sign-in | OK — member 302 to `/` |
| Syntax | OK — 89 PHP files pass `php -l`; `app.js` passes `node --check`; CSS braces balanced |
| Routes and views | OK — 56 routes, 0 broken; 51 literal view references, 0 missing |
| PHP warnings, notices or errors in rendered output | OK — none across 23 pages as guest, member and admin (with `debug` on, PHP prints them into the page) |

`config/config.local.php`, which holds the database password, was not touched — its
modification date is still 4 September.

---

## Feature round 5 — added 13 September 2026

**Asked for:** remove the gaps at the left and right — on every page, the header and the
footer, not only the browse page.

No new files, no new routes, no schema change — still 89 PHP files, 56 routes, 11 tables.

### What changed

| Before | After |
|---|---|
| `.container` capped at 1,140px — the header, the footer and most pages | No cap. A 24px gutter, 16px on a phone |
| 16 pages capped at 760px by a `narrow` flag | 13 of them now full width; 3 forms keep it |
| A browse-only `wide` flag and `.container-wide` class | Removed — every page now does what browse did |

**Full width now:** home, browse, a listing, my listings, requests sent and received, a
request, messages and a conversation, notifications, reviews, complaints, my profile and
public profiles, about, terms, privacy, contact, and every admin page.

**The one judgement call — forms keep a readable width**, centred inside the full-width page:

| Width | Pages |
|---|---|
| 760px | Post an item, edit a listing, request an item |
| 520px | Sign in, create account, verify email, edit profile, change password, report or edit a complaint, write or edit a review, complete an exchange, delete a listing, admin add or edit a user |

A text box 1,850px wide is hard to use. If you want these wide as well, it is one CSS line —
see the outstanding list.

### What full width broke, and how each was fixed

Found by looking at screenshots of the pages, not by reading the code.

| Problem at full width | Fix |
|---|---|
| **The footer's donation band, link columns and bottom bar sat side by side** | `.site-footer .container` was still a flex row from the original one-line footer. At 1,140px its contents happened to wrap onto separate lines; with no cap they did not. Now `display: block`, so they stack |
| **Home: the hero's search box floated mid-page**, away from the text above it | Aligned left with the hero text |
| **My profile and the admin complaint page: each value sat 1,800px from its label** | Every `.spec-list` is now a label column and a value column. The label column is `clamp(90px, 30%, 200px)`, so it also narrows on a phone |
| **A listing's photo area grew to about 970 × 730px** | Height capped at `min(560px, 70vh)`, and the photo shown whole (`object-fit: contain`) rather than cropped. It also needed an explicit `width: 100%`: without it, the height cap was carried over into a width cap through the 4:3 aspect ratio, and the box shrank to 746px |

Still visible, and deliberately left alone: each home-page section shows four items, so on a
wide screen the row of cards ends partway across. How many items the front page features is
a content choice, so it is on the outstanding list rather than changed.

### Two older bugs the new checks exposed

Neither was caused by the width change — on a phone the page was already the width of the
screen — but both were found while checking it.

1. **Zero-count rating bars drew a dashed box.** On a member's rating breakdown, a star
   level with no reviews showed a 50px dashed amber bar. The bar's class was `empty`, which
   is also the site's empty-state box — 52px of padding and a dashed border. Round 3 checked
   the bar's width in the markup (`0%`), which was correct, but never looked at it. Renamed
   to `is-zero`.
2. **Admin pages scrolled sideways on a phone.** At 375px, `/admin`, `/admin/users` and
   `/admin/complaints` were 534px, 796px and 597px wide. Grid items default to
   `min-width: auto`, so the table held the single `.split` column open and the table's own
   scrollbar never came into play. `.split > * { min-width: 0 }` lets the column shrink, and
   the table scrolls inside its `.table-wrap`. `/admin/broadcast` was also 1px too wide,
   because its three audience cards were forced three-across by an inline style; they now
   use `.radio-cards-3`, which stacks on a phone.

### How it was verified

Three temporary same-origin pages in `public/`, all deleted afterwards:

- a **measuring page** that signed in as a guest, a member and an administrator, loaded 33
  pages into an iframe, and recorded where the header, content and footer start and end,
  and whether the page scrolls sideways;
- a **screenshot page** that could sign in first, so member and admin pages could be seen;
- an **overflow finder** that listed every element sticking out past the right edge.

| Check | Result |
|---|---|
| 33 pages at 1,894px — header and footer edge to edge | OK — 33 of 33 |
| 33 pages at 1,894px — content | OK — 25 full width; the other 8 are all form pages, centred as intended |
| 33 pages at 1,894px — sideways scroll | OK — 0 |
| 33 pages at 390px — sideways scroll | OK — 0 (4 before the `.split` fix) |
| Desktop screenshots — home, browse, a listing, terms, sign in, a conversation, post an item, my profile, a public profile, notifications, requests, my listings, reviews, about, admin dashboard, an admin complaint | Looked at each; the problems above were fixed and re-shot |
| Phone screenshots — home, my profile, admin users, broadcast | OK |
| Browse after the `.split` fix | OK — filter panel pinned, 6 columns, unchanged |
| Zero-count rating bars | OK — grey track only |
| Syntax | OK — 89 PHP files pass `php -l`; `app.js` passes `node --check`; CSS braces balanced |
| Routes and views | OK — 56 routes, 0 broken; 51 view references, 0 missing |

`config/config.local.php`, which holds the database password, was not touched.

---

## Feature round 6 — added 17 September 2026

Six changes.

| | Before | After |
|---|---|---|
| PHP files | 89 | **87** — `Mailer`, `EmailOtp` and `auth/verify_email.php` removed; `partials/notif_modal.php` added |
| Routes | 56 | **54** — `/verify-email` and `/verify-email/resend` removed |
| Tables | 11 | **10** — `email_otps` dropped, and `users.email_verified_at` with it |

### 1. Demo accounts moved to a text file

The "Demo accounts" box under the sign-in form, which printed three email addresses and
the shared password, is gone. The six seeded accounts and their password now live in
**`demo-accounts.txt`** in the project root.

The file sits outside `public/`, so the web server cannot serve it — requesting
`/demo-accounts.txt` returns 404. The README and this document now point to the file
rather than repeating the list.

### 2. Donation pop-up warning removed

The amber box — *"Sample details — do not transfer money. The real bank account has not
been set up yet. Add a donation block to config/config.local.php"* — is removed from
`partials/donate_modal.php`. Nothing else in the dialog changed: the six rows, the Copy
buttons and the closing text are as before.

> **What this means for the demo.** Until a real `donation` block is added to
> `config/config.local.php`, the dialog still shows the sample values (`0000 0000 0000`,
> `XXXXLKLX`) — but now with nothing on screen saying they are samples. Either fill in the
> real details before anyone sees it, or say out loud that they are placeholders. See
> outstanding item 8.

### 3. OTP system removed

Registering creates the account, **signs the member straight in**, raises the "Welcome to
NexUse" notification and lands on the home page — the flow before round 2.

| Removed | Layer |
|---|---|
| `app/Models/EmailOtp.php` | Model |
| `app/Core/Mailer.php` — it existed only to send the code | Core service |
| `AuthController::verifyEmail()`, `resendOtp()`, `dispatchOtp()`, and the "not verified yet" check in `login()` | Controller |
| `User::isVerified()`, `User::markVerified()` | Model |
| `app/Views/auth/verify_email.php` | View |
| `/verify-email`, `/verify-email/resend` | Routes |
| `initOtpInput()` in `app.js`; `.otp-input`, `.otp-reveal` in `app.css` | Presentation |
| `storage/mail/` and its `.gitignore` lines; the `mail_driver` block in the example config | Configuration |
| `email_otps` table, `users.email_verified_at` column | Database |

**Database.** `schema.sql` and `seed.sql` no longer create or fill either. For a database
that already has them, **`database/migration_003_remove_otp.sql`** drops both, and is safe
to run twice. It has already been applied to this machine's database.

The privacy notice (the verification-codes row, its section 3 and its retention and
security lines), the terms (the "you must verify that address" clause) and the contact page
("verification codes") were reworded so they no longer describe a step that does not exist.

### 4. "CS-31 Second-Year Group Project" removed from the footer

The footer's bottom line now reads just **© 2026 NexUse**. The layout has the only footer,
so this covers every page. The words "CS-31" still appear in the body text of the terms
and contact pages, which say who built the project — those are not footers, so they were
left.

### 5. The admin menu on every admin page

The left-hand Administration menu was only on the dashboard, users and complaints lists.
It is now on all eight admin pages:

| Page | Before | After |
|---|---|---|
| Categories | No menu | Menu, "Categories" highlighted |
| Broadcast | No menu | Menu, "Broadcast" highlighted; the form is capped at 640px beside it |
| A single complaint | No menu | Menu, "Complaints" highlighted |
| Add a user, edit a user | No menu, form 520px centred | Menu, "Users" highlighted; form 640px beside it |

The three form pages use a new `.admin-form` class (640px), so a text box does not stretch
across the whole screen. `AdminUserController` stopped passing `formWidth` for its two
forms, because a 520px page cannot hold a menu and a form side by side. On a phone the
menu stacks above the page, as it already did on the dashboard.

### 6. Notifications open as a centred pop-up

"Notification" in the header no longer drops a small menu from the top right. It opens a
**centred pop-up over a dimmed page**, the same way the donation dialog does:

| Part | What it shows |
|---|---|
| Head | "Notifications", the unread count, **Mark all read** |
| Body | The recent notifications, unread ones tinted; scrolls on its own if there are many |
| Foot | **View all notifications** and **Close** |
| Closing | ×, Close, **Escape**, or a click on the backdrop; focus returns to the button |
| On a phone | Fills the screen edge to edge |

| File | Layer |
|---|---|
| `app/Views/partials/notif_modal.php` (new) | View partial, rendered once by the layout for signed-in members |
| `layouts/app.php` — the button is now a link to `#notifications` | View |
| `app.js` — `initDonateModal()` generalised into `initModals()` + `initCopyButtons()` | Presentation |
| `app.css` — dialog classes renamed `.modal`, `.modal-panel`…; §20 for the pop-up | Presentation |

**One dialog script for both.** Rather than copy the donation dialog's JavaScript, it was
made generic: any `.modal` with an id is opened by a `data-modal-open="id"` button. The
donation dialog was moved onto the same classes, so both behave identically and a future
dialog needs no new script. Like donations, it works with JavaScript off — the button is
a link to `#notifications`, and CSS `:target` shows it.

### 7. No footer on the sign-in and create-account pages

Both are single-task pages, so nothing on them should lead away from the form. The
controller passes `'hideFooter' => true` and the layout skips the whole footer — the
donation band, the four link columns, the contact block and the bottom line. The donation
dialog is skipped with it, since its only trigger is the footer button.

Every other page is unchanged. The header stays on both pages, so "Browse", "How it
works", Sign in and Create account are still reachable.

### 8. The logo on the right of the home page hero

The hero's right-hand side was empty space once the page went full width. It now carries
the NexUse logo — the same cart-and-heart mark as the header, at `clamp(150px, 15vw,
240px)`, with the wordmark under it and the line *Sell · Rent · Share · Donate*.

The hero is two equal halves at 900px and wider, and the logo is centred in the right
half, which puts it at **three quarters of the window width** (measured: 75.0% at 1,890px,
1,600px and 1,280px, 74.9% at 1,000px). A 60px offset absorbs the page gutter and the
hero's own padding, which would otherwise leave it at about 73%.

Below 900px the logo is hidden and
the hero reads exactly as before, because a 150px mark beside the heading on a narrow
screen would squeeze the text. The mark is `aria-hidden`, since the header already gives
the site its name and a screen reader should not hear it twice.

### How round 6 was verified

| Check | Result |
|---|---|
| Sign-in page — no "Demo accounts", no password, no seeded emails | OK |
| Footer markup on `/login` and `/register` | OK — absent, and no donation dialog either |
| Footer still on `/`, `/browse`, `/terms` | OK — present on all three |
| Screenshot of `/login` at 1,400px | OK — page background fills the space, no gap |
| Hero logo centre, 1,890px to 900px | OK — 75.0% of the window, 146px clear of the text at the narrowest |
| Hero logo at 820px, 640px and 390px | OK — hidden, hero unchanged |
| Page width at those six widths | OK — no sideways scroll at any of them |
| `/demo-accounts.txt` over HTTP | OK — 404, not served |
| Donation dialog — no "Sample details" text; still 6 rows | OK |
| Footer — no "CS-31" or "Second-Year Group Project", as guest and member | OK |
| `/verify-email` | OK — 404 |
| Register a new account | OK — 302 to `/`, signed in, welcome notification present |
| Sign out and back in with that account | OK — 302 to `/`, no verification detour |
| Admin menu on all 8 admin pages, correct item highlighted | OK — 8 of 8 |
| Notification button is a link to `#notifications`; old dropdown markup gone; dialog rendered once with 4 recent items | OK |
| Guest pages have no notifications dialog | OK |
| 20 more guest and member pages — 200, no PHP warnings | OK |
| Privacy page — no mention of verification codes | OK |
| **Headless Chrome, 1,600px:** click Notification | OK — panel 549px wide, centre 800 of 800 across; focus on ×; page scroll locked |
| **Headless Chrome, 390px** | OK — panel fills the screen; no sideways scroll |
| Escape | OK — closes, focus back on the button, scroll unlocked |
| Donation dialog through the new shared script | OK — opens, 6 Copy buttons |
| Admin pages at 1,600px and 390px (categories, broadcast, a complaint, add and edit user) | OK — menu at left on desktop, stacked on phone, no sideways scroll |
| Screenshots — notification pop-up at both widths, categories, broadcast | Looked at each |
| Database after migration 003 | OK — 10 tables; `users` has no `email_verified_at` |
| Syntax | OK — 87 PHP files pass `php -l`; `app.js` passes `node --check` |

The accounts registered by the checks were deleted afterwards, and the temporary test page
was removed from `public/`. `config/config.local.php`, which holds the database password,
was read by the migration script but not modified.

---

## Bugs found and fixed during verification

Two genuine defects, both caught by actually running the system rather than by reading it.

### 1. `seed.sql` could not be imported — MySQL error #1701

`TRUNCATE TABLE requests` failed with *"Cannot truncate a table referenced in a foreign
key constraint"*, even though the script sets `FOREIGN_KEY_CHECKS = 0`.

**Cause:** `TRUNCATE` is a DDL operation, so it is **not** covered by the
`FOREIGN_KEY_CHECKS` bypass. `reviews.fk_reviews_request` references `requests`, so MySQL
refuses outright. The import stopped there, and no `INSERT` ever ran — which is why the
tables were still empty afterwards.

**Fix:** swapped every `TRUNCATE` for `DELETE FROM` (which *does* honour the bypass),
followed by an `ALTER TABLE ... AUTO_INCREMENT = 1` per table so ids still start at 1.
Verified by importing twice in a row on a populated database — the case that used to fail.

### 2. Dynamic routes never matched — every `/listings/{id}` was a 404

`/listings/1` and `/users/2` returned 404 despite the rows existing.

**Cause:** in `App\Core\Router::dispatch()`, `preg_quote()` ran *before* the `{id}`
substitution. It escaped the braces to `\{id\}`, so the replacement pattern no longer
matched, and the compiled regex ended up looking for a literal `{id}` in the URL.

**Fix:** substitute a placeholder token *before* quoting, then swap it for `([0-9]+)`
after. Verified: `/listings/1` and `/listings/7` now 200, `/listings/999` correctly 404s.

> Worth noting for the report: the static route check passed on this bug — it confirmed
> every route pointed at a real controller method, which was true. Only running the
> request exposed it. Static checks and runtime tests catch different things.

---

## Before anything will run

```bat
:: 1. Create the database and application user (needs your MySQL root password)
"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u root -p < database\setup.sql

:: 2. Create the eight tables      (password: NexUse_Local_2026)
"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u nexuse_app -p nexuse < database\schema.sql

:: 3. Load the demo data           (password: NexUse_Local_2026)
"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u nexuse_app -p nexuse < database\seed.sql
```

Then `start.bat` → <http://localhost:8000>

Demo accounts — the six seeded emails and their password are in **`demo-accounts.txt`**
in the project root (moved there in round 6; they are no longer shown on the sign-in page).

---

## The MVC refactor

The first build was one-file-per-page procedural PHP (`browse.php`, `listings/create.php`
and so on). That has been **replaced**, not wrapped. Old files were deleted; nothing
procedural remains.

| Before | After |
|---|---|
| `index.php`, `browse.php`, `listing.php`, `about.php` | `HomeController`, `ListingController` + `app/Views/` |
| `auth/*.php` | `AuthController` + `app/Views/auth/` |
| `listings/*.php` | `ListingController` + `app/Views/listings/` |
| `includes/functions.php` | Split: `app/Core/*` classes + `app/Helpers/functions.php` |
| `includes/header.php`, `footer.php` | `app/Views/layouts/app.php` |
| `includes/auth_guard.php` | `app/Core/Auth.php` |
| `config/db.php` | `app/Core/Database.php` + `app/Core/Config.php` |
| `assets/` | `public/assets/` |
| Direct file URLs (`/listings/create.php`) | Clean routes (`/listings/create`) via front controller |

**Security gain:** only `public/` is web-reachable now. Application code and
`config/config.local.php` sit above the document root.

---

## ✅ Phase 0 — Database and configuration

| File | Holds |
|---|---|
| `database/setup.sql` | Creates the `nexuse` database and `nexuse_app` user |
| `database/schema.sql` | 8 tables, foreign keys, indexes, check constraints |
| `database/seed.sql` | 6 users, 10 categories, 20 listings, 12 requests, 6 reviews, 2 complaints, 17 notifications |
| `config/config.local.php` | Local credentials (git-ignored) |
| `config/config.local.example.php` | Template for other team members |
| `.gitignore`, `start.bat` | Excludes credentials/uploads; starts the dev server |

**Schema note:** the item condition column is `item_condition`, not `condition` —
`CONDITION` is a reserved word in MySQL. Update the ER diagram in your report to match.

## ✅ Phase 1 — MVC core and UI kit

*Figures below are current, not as-at-Phase-1; the growth since is noted in brackets.*

**`app/Core/` — 10 classes:** `Router`, `Controller`, `Model`, `Database`, `Config`,
`Auth`, `Session`, `Request`, `View`, `Upload` *(`Mailer` was added in round 2 and
removed with the OTP in round 6)*

**Entry points:** `bootstrap.php` (PSR-4 autoloader, session, helpers), `routes.php`
(54 routes — 48 at Phase 1, 8 added in round 2, 2 removed in round 6), `public/index.php` (front controller),
`public/router.php` (dev server), `public/.htaccess` (Apache)

**Presentation:** `app/Helpers/functions.php` (28 view helpers), `app/Views/layouts/app.php`,
`partials/listing_card.php`, `partials/admin_nav.php`, `partials/donate_modal.php`,
`partials/notif_modal.php`, `errors/404|403|500.php`,
`public/assets/css/app.css` (2,052-line design system in 21 sections — §17 added in
round 2, §18 in round 3, §19 in round 4, §20 and §21 in round 6), `public/assets/js/app.js`

## ✅ Phase 2 — Authentication · criterion 1

`AuthController` (register / login / logout) + `User` model + `app/Views/auth/`.
Server-side validation, bcrypt, `session_regenerate_id()`, uniform failure message,
suspended-account check, `password_needs_rehash()` upgrade, safe redirect-back.

## ✅ Phase 3 — Listings · Member 1's CRUD

`ListingController` · models `Listing`, `ListingImage`, `Category` · views `listings/`

| Op | Route | Status |
|---|---|---|
| Create | `/listings/create` | ✅ multi-image upload, per-type price rules, transactional |
| Read | `/browse`, `/listings/{id}`, `/listings` | ✅ search across title, description and category name · 6 filters · 6 sorts including best-match · removable filter chips · pagination |
| Update | `/listings/{id}/edit` | ✅ all fields + add/remove/reorder images |
| Delete | `/listings/{id}/delete` | ✅ confirmation page, file cleanup, live-request warning |

## ✅ Phase 4 — Requests & rentals · Member 2's CRUD

`RequestController` · model `ItemRequest` · views `requests/`

| Op | Route | Status |
|---|---|---|
| Create | `/requests/send/{id}` | ✅ date validation for rentals and loans |
| Read | `/requests/sent`, `/received`, `/{id}` | ✅ status tabs and counts |
| Update | `/requests/{id}/respond`, `/{id}/return` | ✅ accept/reject, dates, return condition |
| Delete | `/requests/{id}/withdraw` | ✅ releases the listing back to available |

State machine enforced: `pending → accepted → completed`, `pending → rejected | withdrawn`.
Listing status is kept in step, and contact details are only exchanged after acceptance.

## ✅ Phase 5 — Profile, reviews, complaints · Member 3's CRUD

`ProfileController`, `ReviewController`, `ComplaintController` · models `Review`,
`Complaint` · views `profile/`, `reviews/`, `complaints/`

- **Reviews (headline entity):** write (only on a `completed` request, only by its two
  parties), read on profiles with a rating breakdown, edit, delete.
- **Complaints (second set):** report, list, edit while open, withdraw.
- **Profile:** own profile with activity summary, edit, change password, public profile.

## ✅ Phase 6 — Notifications & admin · Member 4's CRUD

`NotificationController` + four `Admin\*` controllers · model `Notification` · views
`notifications/`, `admin/`

- **Notifications:** raised by the request, review and complaint modules through
  `Notification::raise()`; header Notification button with unread count (a bell emoji until round 4); mark read/unread; mark all read;
  dismiss; clear read.
- **Admin:** dashboard with stats and bar charts, user management (create / search /
  edit / suspend / delete, with last-admin and self-lockout guards), category CRUD,
  complaint resolution, and broadcast to a role or everyone.

## ✅ Phase 7 — Integration

Navigation wired through `app/Views/layouts/app.php` and `routes.php`; cross-module
effects in place (request accepted → notification + listing reserved → review unlocked on
completion). `README.md` written — setup, demo credentials, architecture, module
ownership, security notes. **The click-through audit still needs running against a live
database.**

---

## Interim criteria — current state

| Criterion | State | Evidence |
|---|---|---|
| **1 — Auth works for all users** | ✅ **Met, and strengthened** | New account registered and signed in live; admin and member logins both work; guards refuse signed-out and non-admin access; CSRF enforced. The emailed OTP added in round 2 was removed in round 6, so a new account is signed in straight away |
| **2 — All UIs implemented and navigable** | 🔄 **Nearly** | All 54 routes respond correctly and every page is linked from the shared layout. Round 3 audited navigation for *signed-out* visitors specifically, and removed the links a guest could not follow. A human click-through is still worth doing before the demo — curl proves the routes answer, not that every link on every page points somewhere sensible |
| **3 — Four CRUD ops per student** | ✅ **Met** | All 16 operations exercised against the live database — see the table above |

The system is demo-ready. The one honest gap is a manual click-through for criterion 2.

---

## Still outstanding

1. **Click-through audit by a human** — the last piece of criterion 2. Open every link on
   every page in a browser and confirm nothing is orphaned or mislabelled.
2. **`git init`** — the repository still has not been created. The proposal lists a GitHub
   repository with commit history as a deliverable, and criterion 3 is graded
   individually, so each member should commit their own module under their own name.
3. **Update the report diagrams** — three changes now: the ER diagram needs
   `item_condition` (not `condition`, which is reserved in MySQL), it needs the two new
   tables (`conversations`, `messages`) — `email_otps` came and went, so leave it out — and
   the class diagram should reflect the MVC layering rather than page-per-file.
4. **Upload one listing photo through the browser.** `Core\Upload::image()` itself is now
   proven — the round-3 profile-picture tests pushed a real multipart upload through it,
   confirmed the file was stored and served, and confirmed a non-image is refused. What
   has still not been done once by hand is attaching a photo to a *listing*, which uses
   the same validator through a multi-file field. Worth five minutes before the demo, so
   the browse grid shows real photographs instead of the placeholder icon.
5. ~~Decide how to present email delivery~~ — **no longer applies.** The OTP, and the
   mailer with it, were removed in round 6.
6. ~~Extend the demo script~~ — **done.** §7 of the interim plan's step 01 is back to
   registering and landing signed in, now that the OTP is gone, and a new §7a adds an optional five-minute block for the sign-in wall,
   search, chat and profile pictures, with a note on where each slots into a single pass.
7. **Rehearse the demo** — timed, on the machine you will present from.
8. **Put the real donation bank details in — more important since round 6.** Until a
   `donation` block with an account number is added to `config/config.local.php`, the
   footer's donate dialog shows sample numbers (`0000 0000 0000`). Round 6 removed the
   "do not transfer money" warning, as asked, so nothing on screen now says they are
   samples. Fill in the real details before anyone outside the group sees it, or say
   plainly in the demo that they are placeholders.
9. ~~Decide whether the header and footer should go full width~~ — **done in round 5.**
   The header, footer and every page now run full width, so the logo lines up with the
   content everywhere.
10. **Decide whether the home page should show more items per section.** Each section
    shows four, so on a wide screen each row of cards ends partway across. Showing more
    is a content choice — how many items the front page should feature — so it was left
    as it is.
11. **Decide whether the long forms should also go full width.** Post an item, edit a
    listing, request an item and the sign-in style forms stay centred at a readable
    width. Widening them is a one-line CSS change if you want it.
12. **Re-run migration 003 on any other copy of the database.** Each member who already
    imported the round-2 schema should run `database/migration_003_remove_otp.sql`, or
    re-import `schema.sql` and `seed.sql`. This machine's database is already done.

Done since the last update: round 6 moved the demo accounts into `demo-accounts.txt`,
removed the OTP system (2 routes, 3 files and 1 table), removed the donation warning and
the footer credit, dropped the footer from the two auth pages, put the logo in the home
hero, put the admin menu on every admin page, and turned notifications into
a centred pop-up that shares one dialog script with donations.

---

## Resuming

The build needs no further code before the interim demo. What remains is the outstanding
list above — a human click-through, the git repository, the report diagrams, one manual
listing-photo upload, and a timed rehearsal.

Start the system with `start.bat` and sign in with the administrator account from
`demo-accounts.txt`.
