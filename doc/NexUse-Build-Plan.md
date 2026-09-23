# NexUse — Implementation Build Plan

*The Claude Code plan for building the interim system. Companion to
`NexUse-Interim-Plan.md` (the team schedule) — this one is the technical build plan for
the code itself.*

**Architecture: MVC (Model–View–Controller), hand-rolled in PHP, no framework.**

---

## Context

`NexUse-Interim-Plan.md` sets out what the CS-31 interim must contain: working
authentication for all users, every UI implemented and navigable, and four CRUD
operations per student across four distinct entities. Nothing had been built — the
project folder held only the proposal PDF and that plan.

This plan builds the whole interim-level system: MySQL database, PHP backend and a
complete styled front end, covering all four members' modules so the team has a running
system to demonstrate, extend and divide up.

### Environment reality (checked, differs from the proposal)

| Proposal assumed | Actually installed | Consequence |
|---|---|---|
| XAMPP + Apache | Neither | Serve with PHP's built-in server; `public/.htaccess` keeps it Apache-ready |
| PHP (XAMPP) | PHP 8.5.9 CLI at `C:\php` | Fine |
| MySQL | MySQL 8.0, service `MySQL80` on 3306 | Fine; root password not held |
| `gd` extension | **missing** | No thumbnailing — store originals, size via CSS |
| `fileinfo` extension | **missing** | Validate uploads with `getimagesize()` + extension allowlist |

`pdo_mysql`, `mysqli`, `session` and `mbstring` are present, which is everything the app
needs.

---

## The architecture

### Request lifecycle

```
Browser
   │
   ▼
public/index.php ........ Front controller. The ONLY web-reachable PHP file.
   │                      Loads bootstrap.php (autoloader, session, helpers).
   ▼
routes.php .............. Declarative URL map: path + method → [Controller, method]
   │
   ▼
App\Core\Router ......... Matches the path, resolves {id}, instantiates the controller
   │
   ▼
Controller .............. Reads input via Core\Request, checks access via Core\Auth,
   │        │             applies the rules, chooses a View or a redirect
   │        ▼
   │      Model .......... All SQL for one table, extending Core\Model
   │        │             (find/create/update/delete/count + entity-specific queries)
   │        ▼
   │      Core\Database .. PDO, prepared statements only
   ▼
Core\View ............... Renders app/Views/<module>/<action>.php
                          inside app/Views/layouts/app.php
```

### Layer rules

| Layer | Directory | Rule |
|---|---|---|
| **Model** | `app/Models/` | Owns every query for one table. Controllers never write SQL. |
| **View** | `app/Views/` | Owns the HTML. Never queries the database, never decides anything. |
| **Controller** | `app/Controllers/` | Owns the decisions. Contains no SQL and no HTML. |
| **Core** | `app/Core/` | The shared mini-framework. Written once, then left alone. |

### Core classes

| Class | Responsibility |
|---|---|
| `Router` | URL → controller action; `{id}` segments; 404 / 405 handling |
| `Controller` | Base class: `view()`, `redirect()`, `back()`, `notFound()`, `forbidden()`, `verifyCsrf()`, `redirectWithErrors()` |
| `Model` | Base class: `find()`, `all()`, `create()`, `update()`, `delete()`, `count()`, `exists()` |
| `Database` | PDO connection + `run()`, `one()`, `all()`, `value()`, transactions |
| `Auth` | Who is signed in; `requireLogin()`, `requireAdmin()`, `requireGuest()` |
| `Session` | Flash messages, CSRF tokens, remembered form input and errors |
| `Request` | Typed access to GET, POST and file uploads |
| `View` | Template rendering, layout wrapping, partials |
| `Upload` | Image validation and storage |
| `Config` | Loads `config/config.local.php` |

### Why MVC matters for the marking

Criterion 3 is graded individually. Under MVC each member's work is a bounded set of
files — one model, one controller, one view folder — so "which part did you build?" has
an obvious answer. It also keeps `routes.php` as a single readable map of the entire
system, which doubles as the navigation checklist for criterion 2.

### Security consequence

Only `public/` is web-reachable. Application code, configuration and database credentials
sit **above the document root** and cannot be requested directly — a real improvement on
one-file-per-page PHP, and worth a sentence in the report.

---

## Database setup — one command to run

`database/setup.sql` is written. Run it once with root:

```
"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u root -p < database\setup.sql
```

Then load the schema and demo data as the application user (`NexUse_Local_2026`):

```
"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u nexuse_app -p nexuse < database\schema.sql
"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u nexuse_app -p nexuse < database\seed.sql
```

---

## Conventions applied throughout

- **PDO prepared statements only**, and only inside Models.
- **`password_hash()` / `password_verify()`** (bcrypt). Never md5/sha1.
- **CSRF token on every POST form**, verified by `Session::verifyCsrf()`.
- **Every view escapes output** through `e()`.
- **Every protected action** opens with `Auth::requireLogin()` or `Auth::requireAdmin()`.
- **Uploads**: random filenames, extension allowlist, `getimagesize()` check, 2 MB cap,
  **relative** paths in the database (never `C:\...`), stored in `public/assets/uploads/`.
- **No frameworks, no CDN** — hand-written CSS and vanilla JS, matching the proposal.
- **Brand palette from the NexUse logo**: `#2563C9` blue, `#12945A` green.

---

## Phase 0 — Database and configuration

**Files:** `database/setup.sql`, `schema.sql`, `seed.sql`, `config/config.local.php`,
`config/config.local.example.php`, `.gitignore`, `start.bat`

Eight tables as specified in `NexUse-Interim-Plan.md` §3, with foreign keys and indexes on
the columns actually filtered on. `listings.listing_type ENUM('sell','rent','share','donate')`
is the column that lets one table serve all four exchange types.

Seed data so the demo is never empty: 1 admin + 5 members, 10 categories, 20 listings
across all four types, 12 requests at every status, 6 reviews, 2 complaints, 17
notifications. All demo passwords `Passw0rd!`.

## Phase 1 — MVC core and UI kit

**Files:** `bootstrap.php`, `routes.php`, `public/index.php`, `public/router.php`,
`public/.htaccess`, all of `app/Core/`, `app/Helpers/functions.php`,
`app/Views/layouts/app.php`, `app/Views/partials/`, `app/Views/errors/`

The framework everyone else builds on, plus the design system (`public/assets/css/app.css`)
and the progressive-enhancement JavaScript (`public/assets/js/app.js`).

## Phase 2 — Authentication · criterion 1

`AuthController` + `User` model + `app/Views/auth/`, with `Core\Auth` enforcing member
and admin areas separately.

## Phase 3 — Listings · Member 1's CRUD

`ListingController` · `Listing`, `ListingImage`, `Category` models · `app/Views/listings/`

Create with multi-image upload · browse with search, filters, sorting and pagination ·
detail page · edit including image management · delete with file cleanup.

## Phase 4 — Requests & rentals · Member 2's CRUD

`RequestController` · `ItemRequest` model · `app/Views/requests/`

Send · sent/received lists and detail · accept, reject, set dates, mark returned with
condition · withdraw. Enforces the status state machine and keeps `listings.status` in
step.

## Phase 5 — Profile, reviews, complaints · Member 3's CRUD

`ProfileController`, `ReviewController`, `ComplaintController` · `Review`, `Complaint`
models · `app/Views/profile/`, `reviews/`, `complaints/`

Reviews are the headline entity, complaints the second set. A review requires a
`completed` request and one of its two parties.

## Phase 6 — Notifications & admin · Member 4's CRUD

`NotificationController`, `Admin\AdminDashboardController`, `Admin\AdminUserController`,
`Admin\AdminCategoryController`, `Admin\AdminComplaintController` · `Notification` model ·
`app/Views/notifications/`, `app/Views/admin/`

Header Notification button with unread count (a bell emoji until Phase 10) · list, mark read/unread, dismiss, clear · admin dashboard with
stats and bar charts, user management, category CRUD, complaint resolution, broadcast.

## Phase 7 — Integration, audit, docs

Navigation audit against `routes.php`, cross-module effects verified end to end,
responsive pass, and a `README.md` with setup steps and demo credentials.

---

## Verification

1. **Syntax** — `php -l` across every `.php` file; zero errors.
2. **Static wiring** — walk `routes.php` and confirm every route points at a controller
   class and method that exist, and that every `view()` call names a template that exists.
   MVC adds indirection, so this catches the typos that a framework would otherwise only
   surface when someone clicks that one link.
3. **Server** — `php -S 127.0.0.1:8000 -t public public/router.php`.
4. **Route matrix** — curl every route in `routes.php`: public pages 200, protected pages
   redirect to `/login` when signed out, `/admin/*` rejects a member session.
5. **End-to-end flow** — a scripted curl session with a cookie jar running the demo script
   in `NexUse-Interim-Plan.md` §7 as a test.
6. **CRUD coverage** — all four operations confirmed working for `listings`, `requests`,
   `reviews` and `notifications`, since that is what criterion 3 is marked on.

---

## Phase 8 — Feature round 2 (added 4 September 2026)

Built after the three interim criteria were verified. Full detail, including the
verification results, is in `NexUse-Build-Progress.md`.

| Feature | Files | Layer additions |
|---|---|---|
| Terms, privacy and contact pages | `PageController`, `app/Views/pages/` | +1 controller, +3 views |
| Site footer with contact details and navigation | `app/Views/layouts/app.php`, `app.css` §17 | layout rebuild |
| Related items on a listing | `Listing::related()`, `listings/show.php` | +1 model method |
| Member-to-member chat | `Conversation`, `Message`, `MessageController`, `app/Views/messages/` | +2 models, +1 controller, +2 views |
| Email verification by OTP *(removed in Phase 12)* | `Mailer`, `EmailOtp`, `AuthController`, `auth/verify_email.php` | +1 core service, +1 model, +1 view |

Schema: `database/migration_002_features.sql` adds `conversations`, `messages` and
`email_otps`, plus `users.email_verified_at`. Additive — safe on a populated database.

**Mail transport constraint.** No MTA on this machine and no `openssl` extension, so TLS
SMTP is impossible. `Mailer` therefore has a `log` driver (writes a `.eml` to
`storage/mail/`) and a `mail` driver (PHP `mail()`). The OTP security logic — hashing,
expiry, attempt limits, cooldown — is identical on both paths; only delivery differs.

---

## Phase 9 — Feature round 3 (added 4 September 2026)

Seven changes, and the first round that added **no new files at all** — every one extends
code that already existed. That is the MVC layering paying off: a new search filter is a
clause in `Listing::buildFilters()`, a gated field is one helper call in a view, and a
profile picture is a column the schema already had.

| # | Change | Files touched | Layer |
|---|---|---|---|
| 1 | Show/hide password | `app.js`, `app.css` | Presentation only |
| 2 | Wider product search | `Listing`, `ListingController`, `browse.php`, `home/index.php`, `layouts/app.php` *(the header search box was removed again in Phase 10)* | Model + Controller + View |
| 3 | New / used on sale listings | `functions.php`, `listing_card.php`, `listings/show.php`, `Listing` | Helper + Model + View |
| 4 | Location for members only | `functions.php`, `ListingController`, `browse.php`, `listing_card.php`, `listings/show.php` | Controller + View |
| 5 | Seller profile for members only | `ProfileController`, `listings/show.php` | Controller + View |
| 6 | Profile pictures | `User`, `ProfileController`, `profile/edit.php`, 8 views, `app.js` | Model + Controller + View |
| 7 | Rating breakdown bars | `app.css`, `profile/public.php` | Presentation + View |

**No migration.** `users.avatar_path` was already in `schema.sql` from Phase 0, simply
never used; feature 6 wires it up. One `seed.sql` value changed so that a `new` sale item
exists to demonstrate features 3 and 2 — see the progress document.

### The access-control principle applied in features 4 and 5

Hiding a field in the view is not enough on its own. Whatever is hidden from display must
also be un-filterable, or the filter reconstructs it: a guest who cannot see locations but
*can* run `?location=Colombo` learns every Colombo item from the result set. So
`ListingController::browse()` drops the location filter for signed-out visitors, and the
browse sidebar replaces that field with a sign-in prompt. Verified by requesting the
filtered URL as a guest and confirming the result count is unchanged.

The same reasoning removes the "View profile" links a guest cannot follow, rather than
leaving links that bounce off `Auth::requireLogin()`.

---

## Phase 10 — Feature round 4 (added 13 September 2026)

Six presentation-level changes. Full detail and the verification results are in
`NexUse-Build-Progress.md`.

| # | Change | Files | Layer |
|---|---|---|---|
| 1 | Remove the header search box — it did not fit | `layouts/app.php`, `app.css` | View + presentation |
| 2 | Donation dialog opened from the footer | `partials/donate_modal.php` (new), `donation_details()`, `layouts/app.php`, `app.js`, `app.css` §19, `config.local.example.php` | Helper + View + presentation |
| 3 | Filter panel stays put while the items scroll | `browse.php`, `app.css` §19 | View + presentation |
| 4 | Remove the "Free items are always included in the upper bound" hint | `browse.php` | View |
| 5 | Full-width browse page *(superseded by Phase 11)* | `ListingController` (`wide` flag), `layouts/app.php`, `app.css` §19 | Controller + View |
| 6 | "Notification" as text instead of a bell emoji | `layouts/app.php`, `app.css` | View + presentation |

**One new file** (`app/Views/partials/donate_modal.php`), **no new routes, no schema
change.**

### Two design decisions worth recording

**Bank details live in configuration, and are never invented.** The dialog reads a
`donation` block from `config/config.local.php`. Until a real account number is set it
shows obviously fake values (`0000 0000 0000`) under a *"Sample details — do not transfer
money"* warning. A realistic-looking made-up account number on a live donate screen is how
somebody sends real money to nowhere.

**The dialog works without JavaScript.** The footer button is a link to `#donate`, and
CSS `:target` shows the overlay. JavaScript upgrades it — Escape to close, focus kept
inside and returned afterwards, page scroll locked, Copy buttons — and adds a `js` class
to `<html>` so the CSS stops relying on `:target`, because `history.replaceState()` does
not clear `:target` when the dialog closes.

### How layout claims were verified

`curl` proves markup, not layout — it cannot say whether a panel stays pinned or a dialog
fits a phone. So this phase was checked in headless Chrome, with the pages loaded inside
fixed-size iframes on a temporary same-origin test page that scrolled them, pressed the
buttons and read back `getBoundingClientRect()` and computed styles. The test page was
deleted afterwards.

---

## Phase 11 — Feature round 5 (added 13 September 2026)

Every page, the header and the footer run full width. Full detail and the verification
results are in `NexUse-Build-Progress.md`.

| Change | Files | Layer |
|---|---|---|
| Remove the 1,140px page cap; 24px gutter, 16px on a phone | `app.css` §3 | Presentation |
| Drop the `narrow` flag from 13 list and content pages; keep it on 3 long forms, keep `formWidth` on 13 short forms | 10 controllers | Controller |
| Remove the now-redundant browse-only `wide` flag and `.container-wide` | `ListingController`, `layouts/app.php`, `app.css` | Controller + View |
| Footer parts stack again | `app.css` — `.site-footer .container` | Presentation |
| Hero search aligned with its text | `app.css` — `.hero-search` | Presentation |
| Detail lists: label column beside value column | `app.css` — `.spec-list` | Presentation |
| Listing photo height capped, shown whole | `app.css` — `.gallery-main` | Presentation |
| Zero-count rating bars (class clash with `.empty`) | `app.css`, `profile/public.php` | Presentation + View |
| Admin pages scrolled sideways on phones | `app.css` — `.split > *`, `.radio-cards-3`; `admin/broadcast.php` | Presentation + View |

**No new files, no new routes, no schema change.**

Removing a width cap is a one-line change whose effects land on every page, so this phase
was verified by measurement rather than by reading the CSS: 33 pages at 1,894px and at
390px, as guest, member and administrator, recording each page's edges and whether it
scrolls sideways, plus screenshots of every page type. The two older bugs above were found
that way.

---

## Phase 12 — Feature round 6 (added 17 September 2026)

Six changes. Full detail and the verification results are in `NexUse-Build-Progress.md`.

| Change | Files | Layer |
|---|---|---|
| Demo accounts off the sign-in page, into a text file | `auth/login.php`, `demo-accounts.txt` (new, outside `public/`) | View |
| Remove the "Sample details" warning from the donation dialog | `partials/donate_modal.php` | View |
| Remove the OTP system | `AuthController`, `User`; deleted `EmailOtp`, `Mailer`, `auth/verify_email.php`, 2 routes, `app.js`/`app.css` OTP code; `schema.sql`, `seed.sql`, `migration_003_remove_otp.sql` (new) | Controller + Model + View + Database |
| Reword pages that described verification codes | `pages/privacy.php`, `pages/terms.php`, `pages/contact.php` | View |
| Remove "CS-31 Second-Year Group Project" from the footer | `layouts/app.php` | View |
| Admin menu on every admin page | `admin/categories.php`, `admin/broadcast.php`, `admin/complaints/show.php`, `admin/users/create.php`, `admin/users/edit.php`, `AdminUserController`, `app.css` (`.admin-form`) | Controller + View |
| Logo in the home hero, right-hand side | `home/index.php`, `app.css` §21 | View + Presentation |
| No footer on sign in and create account (`hideFooter` flag) | `AuthController`, `layouts/app.php` | Controller + View |
| Notifications as a centred pop-up | `partials/notif_modal.php` (new), `layouts/app.php`, `app.js` (`initModals()`), `app.css` §11, §19, §20 | View + Presentation |

**Net:** 87 PHP files (−3, +1), 54 routes (−2), 10 tables (−1).

The notification pop-up did not copy the donation dialog's script — that script was made
generic (`.modal` + `data-modal-open`) and both dialogs now run through it.

---

## Note on authorship and submission

The code is built as ordinary commits. No per-member branches or commits are created under
group members' names — that would fabricate evidence of individual authorship for work
they did not write, and criterion 3 is graded individually.

Use this as the team's working base: each member takes their own model, controller and
view folder, understands it, extends it, and commits their own work under their own name.
The per-member split in `NexUse-Interim-Plan.md` §4 maps one-to-one onto the MVC file
layout, which makes that division clean.
