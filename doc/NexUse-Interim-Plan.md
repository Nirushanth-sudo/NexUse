# NexUse — Interim Delivery Plan

**CS-31 · Second-Year Group Project**

An eight-week route from an empty folder to a demo that satisfies all three interim
criteria: working authentication, a fully navigable set of implemented screens, and
four CRUD operations owned individually by each of the four members.

| | |
|---|---|
| **Runway** | 8 weeks |
| **Team** | 4 members |
| **Architecture** | **MVC (Model–View–Controller)** |
| **Stack** | PHP · MySQL · Vanilla JS (no frameworks) |
| **Server** | Apache via XAMPP |
| **Starting from** | Proposal only |

---

## 1. What the interim is marked on

Three criteria, and every decision below exists to serve one of them. Criteria 1 and 2
are graded on the team; criterion 3 is graded on each student individually.

### Criterion 1 — Team — Authentication works for all users

Sign-up, login, logout and session handling functioning end to end for every user of the
system, admin included.

> Built as a team sprint in Week 2, signed off before anyone starts their own module.

### Criterion 2 — Team — All finalized UIs implemented and navigable

Every screen in the design exists as a real page, and a marker can reach all of them by
clicking — no dead links, no orphan pages.

> Screen inventory frozen in Week 1, navigation wired and audited in Week 6.

### Criterion 3 — Individual — Four CRUD operations per student

Each member builds Create, Read, Update *and* Delete for one entity they own — separate
from, and in addition to, the shared login and sign-up work.

> One headline entity per member, no overlap, assigned in section 4.

---

## 2. Two decisions to settle in week one

Both are cheap to decide now and expensive to change in week five. Agree on them as a
group before any code is written.

### How roles actually work

The proposal names seven actors: Admin, Reseller, Buyer, Donor, Donation Receiver,
Lender and Renter. Six of those seven are not account types — they are *roles a person
occupies within a single transaction*. The same person sells a bicycle on Monday and
rents a drill on Tuesday.

So store two account types and derive the rest from context:

> **Recommended model**
>
> `users.role` holds only `'admin'` or `'member'`. A member who posts a listing *is* the
> reseller, lender or donor for that listing. A member who sends a request *is* the
> buyer, renter or receiver for that request.
>
> This still satisfies "login and sign-up must work for all users" — every actor in the
> proposal can register and log in — while sparing you six near-identical dashboards you
> have no time to build.

If your supervisor insists on explicit role selection at sign-up, keep the column but
treat it as a display preference rather than a permission gate. **Ask before Week 2
starts, not after.**

### Freeze the database schema on the Friday of Week 1

Four people writing SQL against a moving schema is the most reliable way to lose a week.
Once the schema below is agreed and committed, changes happen only by a request to the
whole group, and whoever approves one updates `database/schema.sql` in the same commit.

---

## 3. The shared foundation

Everything in this section is built once, in Week 1, by the whole group sitting
together. Nobody starts their own module until it exists.

### Database schema

Eight tables cover the interim scope, and two more were added afterwards for chat. The owner column shows who is responsible for the queries against
each table — not who may read it.

| Table | Holds | Key columns | Owner |
|---|---|---|---|
| `users` | Every account, admin and member | `user_id, name, email, password_hash, phone, city, bio, avatar_path, role, status, created_at` | M4 |
| `categories` | Item categories for listings | `category_id, name, slug` | M1 |
| `listings` | Items posted to sell, rent, share or donate | `listing_id, user_id, category_id, title, description, condition, listing_type, price, location, status` | M1 |
| `listing_images` | Uploaded photos per listing | `image_id, listing_id, image_path, is_primary` | M1 |
| `requests` | Buy, rent, share and donation requests | `request_id, listing_id, requester_id, owner_id, request_type, status, start_date, return_date, actual_return_date` | M2 |
| `reviews` | Ratings and reviews after a completed request | `review_id, request_id, reviewer_id, reviewee_id, rating, comment, created_at` | M3 |
| `complaints` | Disputes reported against a user or listing | `complaint_id, complainant_id, against_user_id, listing_id, subject, description, status` | M3 |
| `notifications` | Per-user alerts and admin broadcasts | `notification_id, user_id, type, title, message, link, is_read, created_at` | M4 |
| `conversations` | One chat thread per (listing, interested member) | `conversation_id, listing_id, buyer_id, owner_id, last_message_at` | M2 |
| `messages` | The lines inside a conversation | `message_id, conversation_id, sender_id, body, is_read, created_at` | M2 |

The last two were added after the interim build, with chat. The original eight are the
interim scope; these ten are the current schema. (An `email_otps` table for email
verification was added in round 2 and removed in round 6.)

`listing_type` is `ENUM('sell','rent','share','donate')`. That single column is what lets
one listings table serve all four exchange types the proposal describes, instead of four
parallel tables.

### Software architecture — MVC

The system is built on the **Model–View–Controller** pattern, hand-rolled in PHP with no
framework. Every request follows the same path, which is what lets four people work in
parallel without treading on each other:

```
Browser
   │
   ▼
public/index.php  ......  Front controller — the only web-reachable PHP file
   │
   ▼
routes.php  ............  Maps a URL to a controller action
   │
   ▼
Controller  ............  Reads input, applies the rules, decides what happens
   │           │
   │           ▼
   │        Model  ......  All SQL for one table. Nothing else touches the database.
   │           │
   ▼           ▼
View  .................  HTML only. No queries, no business logic.
```

**The three layers, and the one rule each:**

| Layer | Lives in | The rule |
|---|---|---|
| **Model** | `app/Models/` | Owns every query for one table. A controller never writes SQL. |
| **View** | `app/Views/` | Owns the HTML. A view never queries the database or decides anything. |
| **Controller** | `app/Controllers/` | Owns the decisions. A controller contains no SQL and no HTML. |

Supporting these is `app/Core/` — the small framework everyone shares: `Router`,
`Controller`, `Model`, `Database`, `Auth`, `Session`, `Request`, `View`, `Upload`,
`Config`. Written once in Week 1, then left alone.

**Why this matters for the interim:** criterion 3 is graded individually. With MVC, each
member's work is a clearly bounded set of files — one model, one controller, one view
folder — so "which part did you build?" has an obvious answer.

### Repository layout

| Path | Contains | Owner |
|---|---|---|
| `public/index.php` | Front controller — every request enters here | Shared |
| `public/router.php` · `public/.htaccess` | Dev-server routing / Apache rewrite | Shared |
| `public/assets/` | `css/`, `js/`, `uploads/` | M3 |
| `routes.php` | The whole URL map, in one readable file | Shared |
| `bootstrap.php` | Autoloader, session, helpers | Shared |
| `config/` | `config.local.php` (git-ignored) and its example | Shared |
| `app/Core/` | Router, Controller, Model, Database, Auth, Session, View, Upload | Shared |
| `app/Helpers/functions.php` | View helpers: `e()`, `url()`, `money()`, `time_ago()` | Shared |
| `app/Models/` | `Listing`, `ListingImage`, `Category` (M1) · `ItemRequest` (M2) · `Review`, `Complaint` (M3) · `User`, `Notification` (M4) | per module |
| `app/Controllers/` | `ListingController` (M1) · `RequestController` (M2) · `Review`/`Complaint`/`ProfileController` (M3) · `Auth`/`Notification`/`Admin\*` (M4) | per module |
| `app/Views/` | `layouts/`, `partials/`, then one folder per module | per module |
| `database/` | `setup.sql`, `schema.sql`, `seed.sql` | Shared |

Only `public/` is exposed to the web. Application code, config and credentials sit above
the document root and cannot be requested directly — a real security benefit over the
one-file-per-page approach.

### Conventions everyone follows

- **Passwords** go through `password_hash()` and `password_verify()`. Never `md5` —
  markers do look.
- **Every query** uses PDO prepared statements, and lives in a **Model**. No SQL in a
  controller, and never in a view.
- **Every view escapes output** with `e()`. Views hold no logic beyond loops and `if`.
- **Every protected action** starts with `Auth::requireLogin()` or `Auth::requireAdmin()`,
  so one class decides who may see what.
- **Every route is declared in `routes.php`**, which doubles as the navigation checklist
  for criterion 2.
- **One branch per member**, taken off the latest `main` and merged by pull request
  every Friday. Nobody commits to `main` directly.

---

## 4. Who owns which four CRUD operations

Four members, four headline entities, no overlap. Each member can point at one table and
demonstrate Create, Read, Update and Delete against it, which is exactly what criterion 3
asks for. The supporting work listed under each member is real, gradable contribution
beyond those four operations.

### Member 1 — Item Listings

**Headline entity: `listings`**

| Op | What they build |
|---|---|
| **Create** | Post an item — title, description, category, condition, exchange type (sell / rent / share / donate), price, and photo upload. |
| **Read** | Browse grid with keyword search (title, description and category name) and filters on type, category, new/used, condition, location and a price range, plus five sort orders including best-match; a single item detail page with related items; and the owner's "My Listings" view. |
| **Update** | Edit any field of an owned listing, swap its photos, and change its availability status. |
| **Delete** | Remove an owned listing, with its images cleaned up from `public/assets/uploads/`. |

*Also owns:* image upload handling, the categories table, and the public browse page —
the highest-traffic screen in the demo.

### Member 2 — Requests & Rentals

**Headline entity: `requests`**

| Op | What they build |
|---|---|
| **Create** | Send a request against a listing — buy, rent, borrow, or receive a donation — with a message and proposed dates. |
| **Read** | "Requests I sent" and "Requests I received", each showing status, plus a request detail page. |
| **Update** | Owner accepts or rejects; rental start and return dates are set; the item is marked returned with its condition verified. |
| **Delete** | Requester withdraws a pending request, releasing the listing back to available. |

*Also owns:* the status state machine (pending → accepted → completed) and the rental
return-date tracking the proposal calls for.

### Member 3 — Reviews & Complaints

**Headline entity: `reviews` · second entity: `complaints`**

| Op | What they build |
|---|---|
| **Create** | Leave a star rating and written review on the other party once a request is completed. |
| **Read** | Reviews listed on a public profile with a computed average rating, plus a "My reviews" page. |
| **Update** | Edit your own review, rating and text, within an allowed window. |
| **Delete** | Remove your own review; an admin can remove any review found abusive. |

*Also owns:* the shared UI kit — global CSS, header, footer, navigation — plus profile
view and edit, and a full second CRUD set on complaints.

### Member 4 — Notifications & Admin

**Headline entity: `notifications` · second entity: `users`**

| Op | What they build |
|---|---|
| **Create** | Notifications raised on request, acceptance, rejection and return-due, plus an admin broadcast to one role or to everyone. |
| **Read** | A **Notification** button in the header with an unread count, a centred pop-up of recent items, and a full notifications page. |
| **Update** | Mark one notification read, or mark all read at once. |
| **Delete** | Dismiss a single notification, or clear all the read ones. |

*Also owns:* the authentication module for criterion 1, the admin dashboard, and admin
user management — view by role, edit, suspend, delete.

> **One thing to get right**
>
> Member 4 owns both the shared authentication work *and* their own CRUD. The criterion
> says four operations *in addition to* login and sign-up, so in the demo Member 4
> presents **notifications** as their headline entity, not `users`, and shows admin user
> management afterwards as supporting work. That ordering removes any chance a marker
> reads their CRUD as the shared sign-up feature counted twice.

---

## 5. Eight weeks, six phases

Each phase ends on a gate — a condition you can check. If a gate is not met on the
Friday, that is the week to raise it, not the week before submission.

### Week 1 — Foundation — Design and scaffold, together

Nobody writes module code this week. The whole group produces the things everyone else
depends on.

- **All** — Wireframe every screen in Figma and agree the final screen list
- **All** — Create the GitHub repo, agree branch rules, install matching XAMPP versions
- **All** — Write and commit `schema.sql` — all eight tables, with foreign keys
- **All** — Build the **MVC skeleton**: `app/Core/` (Router, Controller, Model, Database,
  Auth, Session, View), `public/index.php`, `bootstrap.php`, `routes.php`
- **M3** — Build the shared UI kit: `app/Views/layouts/app.php` and the global CSS
- **M4** — Build `Config`, `Database` and the `Auth` guard contract

> **Gate:** schema frozen and committed. Every member can clone the repo, run
> `start.bat`, and see a styled page rendered through the MVC stack on their own machine.

### Week 2 — Criterion 1 — Authentication, end to end

Member 4 leads and everyone reviews, because the whole team is graded on this. The other
three convert their Figma screens into static HTML on the shared kit while they wait.

- **M4** — Sign-up with server-side validation and hashed passwords
- **M4** — Login, logout and session handling
- **M4** — `App\Core\Auth` guards protecting member pages and admin pages separately
- **All** — Test each other's sign-up and login, including every failure path

> **Gate — criterion 1 met:** a brand-new account can register, log in, reach a member
> page, log out, and be blocked from the admin area.

### Weeks 3–5 — Criterion 3 — Four modules, built in parallel

The core of the interim. Each member works on their own branch, in their own folder, on
their own four operations. Merge to `main` every Friday without exception — three small
merges hurt far less than one big one.

- **M1** — Listings: create with upload → browse and detail → edit → delete
- **M2** — Requests: send → sent and received lists → accept, reject, return → withdraw
- **M3** — Reviews: write → profile listing with average → edit → delete, then complaints
- **M4** — Notifications: raise → Notification button and list → mark read → dismiss, then admin

> **Gate:** every member can demonstrate all four of their operations working against
> the real database.

### Week 6 — Criterion 2 — Integration and navigation

The week the four modules stop being four projects. Cross-module wiring happens here,
and this is where hidden mismatches surface.

- **All** — Wire navigation so every screen on the Week 1 list is reachable by clicking
- **M2** — Requests fire notifications through Member 4's helper on accept and reject
- **M3** — Reviews unlock only once a request reaches `completed`
- **M1** — Listing status flips to reserved when a request is accepted
- **All** — Click-through audit: open every link on every page, list the dead ones, fix them

> **Gate — criterion 2 met:** a marker starting at the home page can reach every
> implemented screen without typing a URL.

### Week 7 — Hardening — Seed data, testing, bug fixing

A demo on an empty database looks unfinished. Populate it, then break it on purpose.

- **All** — Seed about 6 accounts, 20 listings across all four exchange types, and
  requests at every status
- **All** — Write the test-case table the proposal promises: unit, integration and UAT
- **All** — Validation pass: every form rejects empty, oversized and malformed input
- **All** — Responsive check at phone width; the proposal commits to a responsive web app

> **Gate:** someone outside the group can be handed login details and complete a full
> sell-and-buy cycle unaided.

### Week 8 — Submission — Freeze, rehearse, submit

No new features. The last week is for the things that are always underestimated.

- **All** — Code freeze on Monday, bug fixes only after that
- **All** — Rehearse the demo in section 7 twice, timed, on the machine you will present from
- **All** — Interim report, updated ER and use-case diagrams, per-member contribution log
- **All** — Export the database and commit it, so the demo is reproducible from a clean clone

> **Gate:** submitted, with two days of buffer still unspent.

---

## 6. The screen inventory

Criterion 2 says implemented *and navigable*. Freeze this list in Week 1 and treat it as
the checklist for the Week 6 click-through audit — a screen that exists but cannot be
reached from the navigation does not count.

Under MVC these are **routes**, not files. Every one is declared in `routes.php`, so that
file *is* this checklist — if a route is not in it, the page does not exist.

**Public — M3**
`/` · `/about` · `/browse` · `/listings/{id}` ·
`/terms` · `/privacy` · `/contact`

**Members only — M3**
`/users/{id}` — a member's public profile. Moved behind the sign-in wall in feature
round 3, along with every item location.

**Authentication — M4**
`/register` · `/login` · `/logout`

**Listings — M1**
`/listings` · `/listings/create` · `/listings/{id}/edit` · `/listings/{id}/delete`

**Requests — M2**
`/requests/send/{id}` · `/requests/sent` · `/requests/received` · `/requests/{id}` ·
`/requests/{id}/respond` · `/requests/{id}/return` · `/requests/{id}/withdraw`

**Profile & trust — M3**
`/profile` · `/profile/edit` · `/profile/password` · `/reviews` · `/reviews/write/{id}` ·
`/reviews/{id}/edit` · `/reviews/{id}/delete` · `/complaints` · `/complaints/report` ·
`/complaints/{id}/edit` · `/complaints/{id}/delete`

**Notifications — M4**
`/notifications` · `/notifications/read/{id}` · `/notifications/unread/{id}` ·
`/notifications/read-all` · `/notifications/dismiss/{id}` · `/notifications/clear-read`

**Admin — M4**
`/admin` · `/admin/users` · `/admin/users/create` · `/admin/users/{id}/edit` ·
`/admin/users/{id}/delete` · `/admin/categories` (+ create/update/delete) ·
`/admin/complaints` · `/admin/complaints/{id}` · `/admin/broadcast`

**Messaging — M2** *(added after the interim)*
`/messages` · `/messages/start/{id}` · `/messages/{id}`

**Deferred to final**
wishlist and cart · admin report export

In-app chat was originally deferred; it has since been built — see
`NexUse-Build-Progress.md`, "Feature round 2".

> **Scope discipline**
>
> In-app chat, wishlist and cart are in the proposal but not in the interim criteria.
> Leave them out until the three criteria are met. A complete, navigable system without
> chat marks better than a broken one with it.
>
> **Update, 4 September 2026:** with all three criteria verified, chat *was* built, along
> with email OTP verification, a terms page, a full footer and related items. A third
> round then added a password reveal, wider search, new/used flags on sale items,
> members-only locations and profiles, profile pictures, and a fix for the rating
> breakdown. A fourth round (13 September 2026) added a donation dialog, a full-width browse
> page with pinned filters and a text Notification button, and removed the header search.
> A fifth round made every page, the header and the footer full width.
> A sixth round (17 September 2026) removed the email OTP again, took the demo accounts
> off the sign-in page, put the admin menu on every admin page, and made notifications a
> centred pop-up.
> Wishlist and cart remain deferred.

---

## 7. The demo, in fifteen minutes

Rehearse this order. It walks the marker through the three criteria in the order they are
written, and gives each member a clearly attributable turn on screen.

| # | Step | Who | Time |
|---|---|---|---|
| 01 | Register a brand-new account live and land signed in; sign out and back in. Criterion 1, shown rather than claimed. The demo logins are in `demo-accounts.txt`, not on screen — have it open | M4 | 2 min |
| 02 | Walk the navigation top to bottom, showing every area is reachable — criterion 2 | M3 | 2 min |
| 03 | Post a listing with a photo, edit it, then delete a second one | M1 | 2.5 min |
| 04 | From a second account send a request, accept it as the owner, mark it returned, withdraw a third | M2 | 2.5 min |
| 05 | Show the unread count appear on the header's **Notification** button from that acceptance, open the pop-up, mark it read, dismiss it | M4 | 1.5 min |
| 06 | Leave a review on the completed request, edit it, delete it, then file a complaint | M3 | 2.5 min |
| 07 | Log in as admin: dashboard, users by role, suspend a user, broadcast a notice | M4 | 2 min |

**Fifteen minutes exactly.** Step 01 is back to two minutes now that there is no
verification screen, and step 06 has its thirty seconds back.

### 7a. The additions, if you are asked what else is there

Six minutes, only if there is time or a question invites it. None of these are part of
the three criteria — do not spend criterion time on them.

| # | Step | Who | Time |
|---|---|---|---|
| 08 | **The sign-in wall.** Open `/browse` signed out: every location reads "Sign in to see" and there is no location filter. Sign in, reload the same URL: locations appear and the filter is back. Mention that the *filter* is withheld too, not just the display, or `?location=Colombo` would give the game away | M1 | 1.5 min |
| 09 | **Search.** Search "electronics" from the home page — it matches the category, not just the words — then on `/browse` narrow with the New / Used filter, drop one filter by its chip, and scroll the items to show the filter panel staying put | M1 | 1 min |
| 10 | **Chat.** Message a seller from a listing, reply from the other account, show the unread badge and the read receipt | M2 | 1.5 min |
| 11 | **Profile picture and rating breakdown.** Upload a picture on `/profile/edit`, then open a member profile to show the picture and the rating bars | M3 | 1 min |
| 12 | **Donations.** Press "Donate to NexUse" in the footer: the full-screen dialog, a Copy button, then Escape to close. If the real account is not configured yet, the numbers shown are placeholders and the dialog no longer says so — say it out loud | M3 | 1 min |

> **Where these fit if you would rather do one pass.** Step 08 slots in front of step 02
> (it *is* navigation). Step 09 belongs with step 03. Step 10 follows step 04, since chat
> and requests are both Member 2. Step 11 belongs with step 06. Step 12 fits at the end of
step 02, since the footer is where that walk finishes.

> **Two accounts, side by side**
>
> Open the second account in a private window before you start. Switching between a
> seller and a buyer without logging out and back in is the difference between a demo
> that flows and one that stalls twice a minute.

---

## 8. What usually goes wrong

Four-person PHP group projects fail in a small number of predictable ways. Each has a
cheap countermeasure if it is applied early.

| Risk | Countermeasure |
|---|---|
| **Schema drift** — one member quietly adds a column and three others' queries break | Schema frozen on the Friday of Week 1; every later change goes through the group and updates `schema.sql` in the same commit. |
| **Conflicts in shared files** — the layout, the global CSS and `routes.php` get touched by everyone | Member 3 owns the layout and CSS outright. `routes.php` is append-only: add your own block, never reformat someone else's. |
| **MVC leaking** — SQL creeping into a controller, or a query into a view | Each layer has one rule (section 3). If a controller has SQL in it, that query belongs in a model — fix it at the Friday merge, not in week 7. |
| **Integration left to the last week** — four modules that have never met do not merge in two days | Merge to `main` every Friday from Week 3, even when the work is unfinished. |
| **Upload paths that only work locally** — absolute Windows paths break on the demo machine | Store relative paths in the database, never `C:\xampp\...`. |
| **A member falls behind** — criterion 3 is graded individually, so others cannot quietly cover it | The Friday gate makes it visible in Week 3, not Week 7. |
| **An empty demo database** — two listings and no requests makes a finished system look unfinished | Seed data is a Week 7 task with a named owner, not an afterthought. |
| **Nothing to show per person** — markers ask which part you built | A branch per member plus a contribution log means the git history answers for you. |

---

*NexUse · CS-31 · Interim delivery plan · eight-week schedule from a standing start*
