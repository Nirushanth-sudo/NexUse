# NexUse

**A unified platform driving Sri Lanka's circular economy through reselling, renting,
sharing and donating.**

CS-31 · Second-Year Group Project · Interim submission

---

## What it is

One web platform where people can pass on things they no longer use, in four ways:

| Type | Ownership | Payment | Returned? |
|---|---|---|---|
| **For sale** | Transfers permanently | Yes | No |
| **For rent** | Stays with the owner | Yes | Yes |
| **To borrow** | Stays with the owner | No | Yes |
| **Donation** | Transfers permanently | No | No |

A member lists an item, another member requests it, the owner accepts or declines, the two
arrange the handover themselves, and afterwards they review each other. NexUse does not
take payments, arrange delivery, or mediate disputes — it records them and routes them to
an administrator.

### Features

- **Search** from the home page or the browse sidebar — across titles, descriptions and
  category names, with a best-match sort
- **Full-width layout** on every page, header and footer, with the browse filter panel
  staying in place while the items scroll
- **Filters** on exchange type, category, new/used, condition, location and a price
  range, each shown as a chip you can remove on its own
- **Listings** with photo upload, a **New / Used** flag on sale items, and **related
  items** on every listing page
- **Requests** through a full lifecycle: pending → accepted → completed, with rental
  dates and return-condition tracking
- **Member-to-member chat** with sellers, lenders and donors
- **Reviews and ratings** on completed exchanges, with a rating breakdown per member
- **Profile pictures**, with initials as the fallback
- **Complaints** routed to an administrator
- **Notifications** for every event, with an unread count, opening in a centred pop-up
- **Admin area**: dashboard, user management, categories, complaints, broadcast — with
  the admin menu on every admin page
- **Terms, privacy and contact** pages
- **Donations** — NexUse is funded by the people who use it. A button in the footer opens
  a full-screen dialog with the bank details, each with a one-tap Copy button

### What requires an account

Anyone can browse, search and open a listing. Two things are kept for signed-in members,
because both identify a real person:

| Members only | Why |
|---|---|
| **Item and member locations** | A location turns a listing into a doorstep |
| **Member profile pages** (`/users/{id}`) | They carry a city, a whole catalogue and every review about that member |

The location **filter** is withheld from guests as well, not just the display — otherwise
`?location=Colombo` would reconstruct exactly what hiding the field was meant to prevent.

---

## Architecture

Hand-rolled **MVC** in PHP. No framework, no Composer, no CDN — every line is ours, as the
project proposal requires.

```
Browser
   │
   ▼
public/index.php ........ Front controller — the only web-reachable PHP file
   │
   ▼
routes.php .............. URL + method → [Controller, action]
   │
   ▼
App\Core\Router
   │
   ▼
Controller .............. Decisions. No SQL, no HTML.
   │        │
   │        ▼
   │      Model ......... All SQL for one table. Prepared statements only.
   ▼
View .................... HTML only. No queries, no logic.
```

| Layer | Directory | Rule |
|---|---|---|
| Model | `app/Models/` | Owns every query for one table |
| View | `app/Views/` | Owns the HTML; never queries |
| Controller | `app/Controllers/` | Owns the decisions; no SQL, no HTML |
| Core | `app/Core/` | Router, Database, Auth, Session, Request, View, Upload, Config |

Only `public/` is exposed to the web. Application code and database credentials sit above
the document root and cannot be requested directly.

### Directory layout

```
NexUse/
├── app/
│   ├── Core/            Router, Controller, Model, Database, Auth, Session,
│   │                    Request, View, Upload, Config
│   ├── Models/          User, Listing, ListingImage, Category, ItemRequest,
│   │                    Review, Complaint, Notification, Conversation,
│   │                    Message
│   ├── Controllers/     Home, Page, Auth, Listing, Request, Profile, Review,
│   │                    Complaint, Message, Notification, Admin/*
│   ├── Views/           layouts/, partials/, errors/, and one folder per module
│   └── Helpers/         functions.php — view helpers
├── config/              config.local.php (git-ignored) + example
├── database/            setup.sql, schema.sql, seed.sql, migrations 002 and 003
├── public/              index.php, router.php, .htaccess, assets/
├── doc/                 Proposal and planning documents
├── bootstrap.php        Autoloader, session, helpers
├── demo-accounts.txt    Seeded logins for testing (not web-reachable)
├── routes.php           Every URL in the system
└── start.bat            Starts the development server
```

---

## Requirements

- PHP 8.1 or newer with `pdo_mysql`, `session` and `mbstring`
- MySQL 8.0 (or MariaDB 10.4+)

Developed against PHP 8.5.9 and MySQL 8.0 on Windows. `gd` and `fileinfo` are **not**
required — uploads are validated with `getimagesize()` instead.

---

## Setup

### 1. Create the database

```bat
mysql -u root -p < database\setup.sql
```

Creates the `nexuse` database and a `nexuse_app` user. On Windows, if `mysql` is not on
your PATH, use the full path:

```bat
"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u root -p < database\setup.sql
```

### 2. Create the tables and load demo data

Password when prompted: `NexUse_Local_2026`

```bat
mysql -u nexuse_app -p nexuse < database\schema.sql
mysql -u nexuse_app -p nexuse < database\seed.sql
```

Already imported an older schema that has the `email_otps` table? Either re-run the two
commands above, or remove just the OTP parts:

```bat
mysql -u root -p nexuse < database\migration_003_remove_otp.sql
```

### 3. Configure

`config/config.local.php` already exists for this machine. On a new machine, copy the
example and edit it:

```bat
copy config\config.local.example.php config\config.local.php
```

**Donation bank details.** The footer's "Donate to NexUse" dialog reads a `donation`
block from `config/config.local.php` — the example file shows its shape. Until an account
number is set, the dialog shows obviously fake sample values (`0000 0000 0000`). There is
no on-screen warning, so set the real details before the site is shown to anyone.

### 4. Run

```bat
start.bat
```

Then open <http://localhost:8000>.

---

## Demo accounts

The seeded accounts — one administrator and five members — and their shared password
are listed in **`demo-accounts.txt`** in the project root. They are not shown anywhere
on the website, and the file sits outside `public/`, so it cannot be downloaded.

---

## Running under XAMPP instead

The code is Apache-ready. Copy the project into `htdocs`, point the virtual host's
document root at `NexUse/public`, and `public/.htaccess` handles the rewriting. If you
serve it from a subfolder rather than a virtual host, set `base_url` in
`config/config.local.php`:

```php
'base_url' => '/NexUse/public',
```

---

## Module ownership

Per `doc/NexUse-Interim-Plan.md` §4 — each member owns one entity end to end, with all
four CRUD operations:

| Member | Entity | Controller | Model |
|---|---|---|---|
| 1 | `listings` | `ListingController` | `Listing`, `ListingImage`, `Category` |
| 2 | `requests` | `RequestController` | `ItemRequest` |
| 3 | `reviews` | `ReviewController`, `ComplaintController`, `ProfileController` | `Review`, `Complaint` |
| 4 | `notifications` | `NotificationController`, `Admin\*` | `Notification`, `User` |

Added after the interim, along the same ownership lines:

| Round | Addition | Sits with |
|---|---|---|
| 2 | Messaging (`MessageController`, `Conversation`, `Message`) | Member 2 |
| 2 | Email verification (`Mailer`, `EmailOtp`) — *removed in round 6* | Member 4 |
| 2 | Related items (`Listing::related()`), terms/privacy/contact pages, footer | Members 1 and 3 |
| 3 | Search, new/used flags, members-only locations (`Listing`, `ListingController`, browse and listing views) | Member 1 |
| 3 | Profile pictures and the members-only profile page (`User::setAvatar()`, `ProfileController`, profile views) | Member 3 |
| 3 | Rating-breakdown fix (`app.css`, `profile/public.php`) | Member 3 |
| 3 | Password reveal (`app.js`) | Shared — it touches every auth form |
| 4 | Full-width browse page, pinned filters (`browse.php`, `app.css` §19) | Member 1 |
| 4 | Donation dialog (`partials/donate_modal.php`, `donation_details()`, footer) | Member 3 |
| 4 | Text "Notification" button, header search removed (`layouts/app.php`) | Member 4 |
| 5 | Full-width layout on every page (`app.css`, width flags in 10 controllers) | Shared — it touches every page |
| 6 | OTP removed, demo accounts moved to `demo-accounts.txt` (`AuthController`, `User`, `auth/login.php`) | Member 4 |
| 6 | Admin menu on every admin page; notifications as a centred pop-up (`admin/*`, `partials/notif_modal.php`, `initModals()`) | Member 4 |
| 6 | Donation warning and footer credit removed (`partials/donate_modal.php`, `layouts/app.php`) | Member 3 |

Authentication is a shared, team-graded deliverable, led by Member 4.

---

## Security notes

- Passwords hashed with `password_hash()` (bcrypt), verified with `password_verify()`,
  and upgraded automatically via `password_needs_rehash()`.
- Every database query uses PDO prepared statements, inside a Model.
- Every POST form carries a CSRF token, checked by `Session::verifyCsrf()`.
- Every view escapes output through `e()`.
- Uploads — listing photos and profile pictures alike — are checked with `getimagesize()`
  and an extension allowlist, capped at 2 MB, stored under random filenames, and
  referenced by relative path. Replacing or removing a picture deletes the old file.
- Fields withheld from a signed-out visitor are withheld from the **filters** too, so a
  query string cannot reconstruct what the page does not show.
- Session id is regenerated on login and logout.

---

## Documentation

| File | Contents |
|---|---|
| `doc/Project Proposal.pdf` | The original proposal |
| `doc/NexUse-Interim-Plan.md` | Team plan: criteria, schedule, module ownership |
| `doc/NexUse-Build-Plan.md` | Technical build plan and architecture |
| `doc/NexUse-Build-Progress.md` | What is built, what is verified, what is left |
