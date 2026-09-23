# NexUse — CRUD status and improvement plan

*CS-31 · prepared 21 September 2026 · measured against `Project Proposal.pdf` and the code
as committed on `main` (`e7ec095`).*

Every "done" below was checked against the routes, controllers and schema, not taken from
earlier notes. Where the proposal and the code disagree, the proposal's wording is quoted.

**Status key**

| Mark | Meaning |
|---|---|
| ✅ | Done — works end to end |
| 🟡 | Partly done — the core exists, something the proposal asks for is missing |
| ❌ | Not built |
| ⛔ | Out of scope by the proposal's own §3 / §7 (payments, delivery) |

---

## 1. At a glance

- **The graded interim CRUD is complete.** All 16 operations (4 members × 4 operations) work
  and were exercised against the live database.
- **Admin, Buyer, Seller, Lender and Renter are well covered** for the *listing → request →
  exchange → review* flow, in all four exchange types. **Donor and Donation Receiver are
  not**, because the donation flow runs in the opposite direction to the proposal's.
- **The largest gap is the Donation Receiver actor.** The proposal has receivers *post a
  need* and donors *pledge* to it. The system only supports the opposite direction (donor
  lists, receiver requests). Six of the receiver's use cases depend on this.
- **The second gap is rental aftercare:** return reminders, late-return alerts, penalties
  and compensation requests are all in the proposal's §3 scope and none are built.
- **The admin is missing reports, full transaction records and one-to-one
  notifications.**
- **The proposal contradicts itself** on cart, payments and delivery: they appear in the
  Buyer requirements and use cases but are ruled out in §3 and §7. This needs settling in
  the final report (see §5.1).

Counted row by row from the tables in §4:

| | Done | Partial | Not built | Out of scope | Total |
|---|---|---|---|---|---|
| §8 functional requirements | 42 | 12 | 14 | — | 68 |
| Extra use cases from the diagrams, plus the §5 admin-approval line | 6 | 5 | 5 | 4 | 20 |
| **All actor rows (§4.1–4.7)** | **48** | **17** | **19** | **4** | **88** |

---

## 2. How the proposal's actors map onto the system

The proposal lists seven actors. The system stores only two account roles, `admin` and
`member`. **Buyer, Reseller, Donor, Donation Receiver, Lender and Renter are all
`member` accounts**, and which one a person is depends on the exchange they are in:

| Proposal actor | In the system | Decided by |
|---|---|---|
| Admin | `users.role = 'admin'` | Account role |
| Reseller / Seller | Owner of a `sell` listing | `listings.listing_type` |
| Buyer | Requester on a `buy` request | `requests.request_type` |
| Lender | Owner of a `rent` or `share` listing | `listings.listing_type` |
| Renter | Requester on a `rent` or `borrow` request | `requests.request_type` |
| Donor | Owner of a `donate` listing | `listings.listing_type` |
| Donation Receiver | Requester on a `donation` request | `requests.request_type` |

This matches how people actually use the platform (one person sells a bike and borrows a
drill in the same week). The final report should say so explicitly, because a marker
reading seven actors will look for seven account types.

---

## 3. CRUD by entity

### 3.1 The graded entities — interim criterion 3

One entity per member, all four operations each. **All 16 are done.**

| Member | Entity | Create | Read | Update | Delete |
|---|---|---|---|---|---|
| **1** | `listings` | ✅ Post an item with photos — `/listings/create` | ✅ Browse, search, filter, detail, my listings | ✅ Edit details, photos, availability — `/listings/{id}/edit` | ✅ Delete with photo clean-up — `/listings/{id}/delete` |
| **2** | `requests` | ✅ Send a buy/rent/borrow/donation request, with dates for rent and borrow — `/requests/send/{id}` | ✅ Sent, received, detail | ✅ Accept or reject (dates adjustable), mark returned with condition, complete | ✅ Withdraw a pending request |
| **3** | `reviews` | ✅ Rate 1–5 and review after a completed exchange | ✅ My reviews; on public profiles with a rating breakdown | ✅ Edit own review | ✅ Delete own review |
| **4** | `notifications` | ✅ Raised automatically by requests, reviews, messages and complaints; admin broadcast | ✅ Header count, centred pop-up, full page | ✅ Mark read, mark unread, mark all read | ✅ Dismiss one, clear all read |

### 3.2 Supporting entities

| Entity | Owner | C | R | U | D | What is missing |
|---|---|---|---|---|---|---|
| `users` (self-service) | M3 / M4 | ✅ Register | ✅ Profile, public profile | ✅ Edit profile, photo, password | ❌ | A member cannot delete their own account; the privacy page says "contact an administrator" |
| `users` (admin) | M4 | ✅ Add user | ✅ List by role and status, search | ✅ Edit, suspend, reset password | ✅ Delete | — |
| `categories` | M4 (M1's table) | ✅ | ✅ | ✅ Rename | ✅ | — |
| `complaints` (member) | M3 | ✅ Report a member and/or listing | ✅ My complaints | ✅ Edit while open | ✅ Delete while open | — |
| `complaints` (admin) | M4 | — | ✅ Queue, detail | ✅ Status and note | — | — |
| `listing_images` | M1 | ✅ | ✅ | ✅ Choose the main photo | ✅ | Photos cannot be reordered beyond choosing the main one |
| `conversations` / `messages` | M2 | ✅ | ✅ Inbox, thread, read receipts | ❌ | ❌ | Cannot edit or delete a message, or archive a conversation |

### 3.3 CRUD that does not exist yet

These are the entities the proposal implies but the schema does not have. Each would make a
clean, self-contained CRUD set in the final phase.

| Entity | From the proposal | C | R | U | D |
|---|---|---|---|---|---|
| `donation_requests` (a receiver's posted need) | Donation Receiver: "Create and manage a donation request", "Delete a donation request" | ❌ | ❌ | ❌ | ❌ |
| `pledges` (a donor's offer against a need) | Donor: "Pledge donation", "Cancel donation pledge"; Receiver: "Accept/Reject a donation pledge" | ❌ | ❌ | ❌ | ❌ |
| `compensation_claims` | §3: "allows compensation requests for damage or non-return"; Lender: "Request compensation" | ❌ | ❌ | ❌ | ❌ |
| `wishlist_items` | Buyer: "Add products to a wishlist" | ❌ | ❌ | — | ❌ |
| `review_replies` | Seller and Lender use cases: "Reply to Reviews" | ❌ | ❌ | ❌ | ❌ |
| `reports` (saved admin reports) | Admin: "Generate reports (monthly, custom)" | ❌ | ❌ | — | ❌ |

---

## 4. Requirements by actor

Every functional requirement from proposal §8, plus the extra use cases from the diagrams,
checked against the code.

### 4.1 Admin

| Requirement | Status | Where / what is missing |
|---|---|---|
| Log in and log out | ✅ | Admins land on `/admin` |
| View users by role | ✅ | `/admin/users` — role and status filters, search |
| Edit or delete any user account | ✅ | Plus create, suspend, reset password |
| Summary dashboard (users, transactions, deliveries) | 🟡 | Users, listings, requests, completed exchanges, reviews, open complaints, by-type and by-status bars. "Deliveries" does not apply — delivery is out of scope |
| View all product listings and transaction records | 🟡 | Only the latest few appear on the dashboard. **No admin page listing every listing or every request**, and removed listings cannot be seen at all |
| Manage and resolve disputes or complaints | ✅ | Open → reviewing → resolved / dismissed, with an admin note the member sees |
| Send notifications to individual users or user groups | 🟡 | Groups ✅ (everyone, members, admins). **One named user ❌** |
| Generate reports (monthly, custom — sales, user activity) | ❌ | Nothing. Listed as "admin report export" in the deferred list |
| *Use case:* manage product categories | ✅ | `/admin/categories` |
| *Use case:* validate products / donation requests | ❌ | Listings go live immediately; there is no approval queue |
| *Use case:* suspend / delete user, view user profiles | ✅ | |
| *§5 legal:* "Users are verified through admin approval" | ❌ | Accounts are active on registration |

### 4.2 Buyer

| Requirement | Status | Where / what is missing |
|---|---|---|
| Create an account | ✅ | `/register`, signed in straight away |
| Log in securely | ✅ | bcrypt, session regeneration, CSRF |
| Maintain and update profile | ✅ | Details, photo, password |
| Browse products | ✅ | `/browse`, paginated, 4 type tabs |
| Search by keyword | ✅ | Title, description and category name, ranked by relevance |
| Filter by category, price, location, condition | ✅ | Plus new/used. Location is members-only by design |
| View details with images and description | ✅ | Gallery, condition, related items |
| Add products to a wishlist | ❌ | Deferred since the interim plan |
| Add products to a shopping cart | ❌ | Conflicts with §3 / §7 — see §5.1 |
| Place orders | 🟡 | Done as a **buy request** the seller accepts, as §3 "Buying (Reselling)" describes. No checkout |
| View order status and purchase history | ✅ | `/requests/sent` — every status, kept after completion |
| Chat with sellers | ✅ | One thread per listing, read receipts |
| Rate and review products and sellers | 🟡 | Sellers ✅. Reviews attach to the *person*, not the *product* |
| *Use case:* make payment | ⛔ | §3 out of scope |
| *Use case:* track delivery | ⛔ | §3 out of scope |

### 4.3 Seller / Reseller

| Requirement | Status | Where / what is missing |
|---|---|---|
| Register, log in and out, manage profile | ✅ | |
| Create listings | ✅ | Photos, description, price, condition, category, location |
| Edit descriptions, prices and details | ✅ | |
| Delete or remove listings | ✅ | Delete outright, or set availability to removed |
| Notified of new orders or inquiries | ✅ | New request → notification; new message → notification and badge |
| Chat with buyers | ✅ | |
| Report suspicious activity, fraudulent buyers or scams | ✅ | Complaint against a member and/or listing |
| Verify account information | ❌ | The email OTP was built in round 2 and **removed at the group's request in round 6**; no other verification exists |
| *Use case:* view orders, update order status | ✅ | `/requests/received` — accept, reject, complete |
| *Use case:* forgot password | 🟡 | An admin can reset a password; **no self-service reset** |
| *Use case:* view ratings and reviews | ✅ | Public profile, with breakdown |
| *Use case:* reply to reviews | ❌ | |
| *Use case:* view payments | ⛔ | Out of scope |

### 4.4 Donor

The proposal's donor **pledges against a receiver's posted need**. The system's donor
**lists an item and receivers ask for it**. The rows below mark what the current model
covers.

| Requirement | Status | Where / what is missing |
|---|---|---|
| Log in and out, manage profile | ✅ | |
| Browse active donation requests | ❌ | There are no posted needs to browse. Donors *can* browse donation **listings** |
| Filter donation requests by category, location, item type | ❌ | Same reason |
| Cancel a donation pledge before it is accepted | 🟡 | In the current model the donor can reject a receiver's request, and a receiver can withdraw theirs |
| View status of pledged donations | 🟡 | Via `/requests/received` on the donor's listing |
| Communicate with receivers | ✅ | Chat |
| View notifications related to donation activity | ✅ | |
| View donation history | 🟡 | "My listings" has status tabs but **no filter by exchange type**, so donations are mixed in with everything else |
| Notified when a pledge is accepted or rejected | ❌ | Needs pledges |

### 4.5 Donation Receiver

| Requirement | Status | Where / what is missing |
|---|---|---|
| Log in and out, manage profile | ✅ | |
| Create and manage a donation request | ❌ | **No `donation_requests` entity** |
| Delete a donation request | ❌ | |
| View pledges received for a request | ❌ | **No `pledges` entity** |
| Accept a donation pledge | ❌ | |
| Reject a donation pledge | ❌ | |
| Communicate with donors | ✅ | Chat, from the donor's listing |
| View notifications about requests and pledges | 🟡 | Notified about requests on donation *listings*; nothing for pledges |
| Notify a requester when a donor pledges | ❌ | |
| *Use case:* mark donation as received | 🟡 | Only the **owner** can close an exchange. The receiver cannot confirm they got it |

### 4.6 Lender

| Requirement | Status | Where / what is missing |
|---|---|---|
| Log in and out | ✅ | |
| List items for rent with description, condition, price, availability | ✅ | Price is per day. Availability is a status, not a calendar |
| View and manage listed items | ✅ | `/listings`, status tabs |
| Receive rental requests | ✅ | With the renter's proposed dates |
| Accept or reject rental requests | ✅ | |
| Set rental duration (start and return date) | ✅ | Owner can adjust the dates when accepting |
| Track rented items and their status | ✅ | `/requests/received` |
| Verify item condition on return | ✅ | As given, minor damage, major damage, not returned |
| Request compensation for damage or non-return | 🟡 | The damage is **recorded**, and a complaint can be filed. **No compensation claim** the renter can see and answer |
| View ratings and reviews from renters | ✅ | |
| *Use case:* set availability period | 🟡 | Available / reserved only; no blocked-out dates |
| *Use case:* chat with renters | ✅ | |
| *Use case:* reply to reviews | ❌ | |
| *Use case:* report damaged / missing items and late return | 🟡 | Via return condition and a complaint; **nothing detects "late" automatically** |
| *Use case:* forgot password | 🟡 | Admin reset only |

### 4.7 Renter

| Requirement | Status | Where / what is missing |
|---|---|---|
| Log in and out | ✅ | |
| Search and view items for rent | ✅ | "For rent" tab and type filter |
| Send rental requests | ✅ | With start and return dates, validated |
| View request status (pending, accepted, rejected) | ✅ | `/requests/sent` |
| View rental details (duration, conditions, return date) | ✅ | `/requests/{id}` |
| Notifications for request updates | ✅ | |
| Notifications for **return reminders** | ❌ | No reminder is ever raised |
| Use the item for the agreed period | ✅ | Happens off-platform, tracked by the dates |
| Return within the agreed time | 🟡 | Dates are stored; **no late detection** |
| Confirm return of the item | 🟡 | Only the lender marks it returned; the renter cannot confirm their side |
| Rate and review lenders | ✅ | |
| *Use case:* edit rental request / rental period | ❌ | A pending request can only be withdrawn and sent again |
| *Use case:* view rental history, report issues | ✅ | |
| *Use case:* make payment | ⛔ | Out of scope |

### 4.8 System-wide scope items from §3

| §3 in-scope item | Status | Notes |
|---|---|---|
| Registration, login, profiles, reviews, dispute reporting | ✅ | |
| List any item with description, condition, availability type, ownership rules per type | ✅ | Sell and donate close the listing; rent and share return it to available |
| Request to buy, rent, share or receive, owner accepts or rejects, no automatic approval | ✅ | |
| Track rental and sharing periods with start and return dates | ✅ | |
| **Send reminders** | ❌ | |
| **Manage late returns with penalties** | ❌ | |
| Verify item condition | ✅ | |
| **Compensation requests for damage or non-return** | ❌ | Recorded, not requested |
| Selling transfers ownership, donating is permanent, sharing is temporary with return tracking | ✅ | |
| **List items for disposal**, handled by authorised parties, platform only coordinates | ❌ | No disposal type or flow |
| Notifications for requests, approvals, rejections | ✅ | |
| Notifications for **return reminders and late-return alerts** | ❌ | |
| Rate and review users, visible to all users | 🟡 | Visible to signed-in members; public profiles moved behind sign-in in round 3 |
| Admin manages users, complaints, notifications | ✅ | |
| Admin manages **system analytics** and **monitors transactions** | 🟡 | Dashboard figures only |

---

## 5. Improvements based on the proposal

### 5.1 Settle first — contradictions inside the proposal

These need a decision recorded in the final report, not code.

| Contradiction | Recommendation |
|---|---|
| Buyer FR asks for a **shopping cart**, **place orders** and the use case has **make payment** and **track delivery** — but §3 and §7 say the system "does not handle in-app payments" and "does not provide delivery management" | Drop cart, payment and delivery from the requirements and say why (§3 scope). "Place orders" is met by the buy request. If a cart is wanted anyway, redefine it as *request several items in one go* — no payment involved |
| §3 says disputes are reported "**without providing resolution mechanisms**", but §6 says "Admin manages and **resolves** disputes" | Keep what is built: the admin records an outcome and a note but does not enforce anything. State that this is "resolution" in the record-keeping sense |
| §5 says "Users are verified through admin approval", but nothing in §8 asks for an approval step | Either build a verified badge (5.2, item 8) or remove the sentence |
| Actor named **Donar** in §3 and **Render** in §8 | Correct to Donor and Renter in the final report |

### 5.2 Build — in the proposal's scope, not yet done

Ordered by how much of the proposal each one closes. Owners follow the module split.

| # | Improvement | Closes | Owner | Size |
|---|---|---|---|---|
| 1 | **Donation requests and pledges.** New `donation_requests` (receiver posts a need: title, category, location, quantity, needed-by) and `pledges` (donor offers against it; receiver accepts or rejects; donor can cancel while pending). Browse and filter needs; notifications both ways. | 13 Donor and Receiver requirements | M1 (requests board) + M2 (pledge flow) | Large |
| 2 | **Return reminders and late-return alerts.** A check that raises "due tomorrow" and "overdue" notifications for accepted rent and borrow requests. With no cron on the dev machine, run it lazily (on each page load, at most once an hour) or as `php bin/reminders.php` through Windows Task Scheduler. Use a **24-hour grace period** — the option 40% of the survey chose | §3 reminders, Renter reminders, Lender late returns | M2 + M4 | Medium |
| 3 | **Compensation claims.** After a return marked damaged or not returned, the lender files a claim (reason, amount, photo); the renter accepts or disputes; a disputed claim becomes a complaint. Record-only, no money moves — consistent with §3 | §3 compensation, Lender requirement | M2 | Medium |
| 4 | **Late penalty (recorded).** Store a per-day late fee on rent listings and show the amount owed once a return is late. It is information for the two parties, not a charge | §3 "manage late returns with penalties" | M1 + M2 | Small |
| 5 | **Admin: all listings and all requests.** Two admin pages with filters (type, status, date, member), including removed listings. Admin can hide a listing | Admin "view all listings and transaction records", "monitor transactions" | M4 | Medium |
| 6 | **Admin reports.** Monthly and date-range figures: new members, listings by type and category, requests by status, completed exchanges, top categories, complaints. Print view plus CSV export (no library needed) | Admin "generate reports" | M4 | Medium |
| 7 | **Notify one member.** Add "a specific member" to the broadcast audience, with a member search | Admin "send notifications to individual users" | M4 | Small |
| 8 | **Account verification.** A *Verified* badge an admin grants after checking the member (phone call, NIC sighted in person). Show it on profiles, listings and request pages. Optional approval queue for new accounts | Seller "verify account", §5 admin approval; survey: scams 63%, verified profiles the top-rated feature | M4 + M3 | Medium |
| 9 | **Self-service password reset.** Needs outbound email, which this machine cannot send (no MTA, no openssl). Either build it for the deployed server, or keep the admin reset and document it as the reset path | Forgot Password in four use-case diagrams | M4 | Small–Medium |
| 10 | **Reply to reviews.** One reply per review, by the person reviewed, editable and deletable | Seller and Lender use cases | M3 | Small |
| 11 | **Edit a pending request.** Renter changes dates or message while it is still pending | Renter "edit rental request / period" | M2 | Small |
| 12 | **Two-sided confirmation.** Receiver confirms "received"; renter confirms "returned". The exchange closes when both agree, or when the owner closes it after a timeout | Receiver "mark as received", Renter "confirm return" | M2 | Small |
| 13 | **Wishlist.** Save a listing, list saved items, remove; notify when a saved item's price drops or it becomes available again | Buyer "add to wishlist" | M1 | Small |
| 14 | **Disposal listings.** A `dispose` listing type (e-waste, broken furniture) that authorised collectors can respond to; coordination only | §3 disposal | M1 | Small |
| 15 | **Donation and rental history filters.** Add an exchange-type filter to *My listings* and *Requests* | Donor "view donation history", Renter "rental history" | M1 + M2 | Small |
| 16 | **Availability calendar for rentals.** Block out dates already booked so two renters cannot ask for the same days | Lender "set availability period" | M1 + M2 | Medium |
| 17 | **Product-level ratings.** Show on a listing the rating its owner received on past exchanges of that item | Buyer "rate and review products" | M3 | Small |

### 5.3 Build — answering the proposal's own survey (§5, social feasibility)

The survey is the strongest argument in the proposal. Features that answer it directly are
easy to justify in the final report.

| Survey finding | Improvement |
|---|---|
| **Unclear item conditions — 70%**, the top frustration | A condition guide beside the condition picker (what "good" or "fair" means, with examples); require at least one photo for *fair* and *poor*; optional per-category checklist (e.g. "screen cracked?"). Handover photos at the start of a rental, compared on return |
| **Scams and fake profiles — 63%** | The verified badge (5.2 #8); show *member since*, completed exchanges and response rate on every listing; limit how many listings a brand-new account can post on day one |
| **Trusting the borrower — 70%**; damage or theft — 60% | Show the requester's rating and completed exchanges on the lender's request page; an optional **deposit amount** on rent listings (recorded, paid off-platform); compensation claims (5.2 #3) |
| **Late penalties** — 24-hour grace 40%, flat fee per day 26.7% | Implement exactly that: 24-hour grace, then a flat per-day fee set by the lender (5.2 #4) |
| **Detailed search filters** — 18 of 30 "very important" | Already strong. Add *posted within*, district-level location, *pickup available*, and sort by newest price drop |
| **Multi-language (English, Sinhala, Tamil)** — 11 "very important" | Move interface strings into `lang/en.php`, `lang/si.php`, `lang/ta.php` with a switcher in the header. Big, but in keeping with §5's "inclusive and accessible for rural users" |
| **Doorstep pickup — 40%** for donations; difficult delivery — 43% | Delivery stays out of scope, but add a *"pickup from my place"* / *"can drop off nearby"* flag and a meeting-point note on listings |
| **Knowing exactly who receives the donation — 26.7%** | Donation requests with a stated purpose (5.2 #1); show the receiver's profile and history to the donor before accepting |

### 5.4 Proposal deliverables not yet produced (§6)

| Deliverable | State | Action |
|---|---|---|
| Source code in GitHub with commit history | 🟡 Local repository, 17 commits, **no remote yet** | Create the GitHub repository and push; each member commits their own future work under their own name |
| ER diagrams, normalisation | ❌ Needs updating | Add `conversations`, `messages`; use `item_condition`, not `condition`; leave out `email_otps` |
| Use case, sequence and class diagrams | 🟡 Use cases in the proposal | Update the use cases to match what is built; add sequence diagrams for *request → accept → complete → review* and *rental return*; class diagram of the MVC layers |
| Testing reports — unit, integration, UAT | ❌ | See 6.2 |
| UI mockups in Figma | ❌ | Can be produced from the live screens |
| Deployment on a local server or free-tier cloud | 🟡 Local only | Deploy to a free PHP + MySQL host for the final demo |
| Final report, user manual, slides | ❌ | User manual per actor, reusing the tables in §4 |

---

## 6. Other improvements

Not asked for in the proposal, but they would strengthen the system and the marking.

### 6.1 Security

| Improvement | Why |
|---|---|
| **Rate-limit sign-in** — lock for a few minutes after 5 failed attempts per email and per IP | Nothing currently stops password guessing |
| **Turn `debug` off for any demo or deployment** | With it on, PHP errors print file paths into the page |
| **Session timeout** after inactivity, and `Secure` cookies once served over HTTPS | Shared or public computers |
| **Security headers** — Content-Security-Policy, X-Frame-Options, Referrer-Policy | Cheap defence against injection and clickjacking |
| **Audit log of admin actions** (suspend, delete, role change, complaint outcome) | Accountability; also useful evidence for the report |
| **Password strength rule** beyond 8 characters, e.g. reject the most common passwords | |
| **Self-service account deletion** with a confirmation step | The privacy page promises deletion "on request" |
| **Rotate the MySQL root password** | It was in `config.local.example.php` until round 6. It never reached git, but it had sat in a shareable file |

### 6.2 Testing — a proposal deliverable

- **Unit tests** as plain PHP scripts (no framework needed, in keeping with the proposal) for
  the rules that matter: request state transitions, date validation, rating averages,
  price filters, upload validation.
- **Integration tests** — the HTTP checks used to verify each feature round (register,
  sign in, full request lifecycle, access control, 403/404 cases) turned into one
  re-runnable script with a pass/fail summary.
- **UAT** — five to ten people outside the group, each given a task per actor ("borrow the
  drill for two days"), recording success and time taken. The report needs this evidence.

### 6.3 Usability

| Improvement | Why |
|---|---|
| **Pagination on every long list** — only browse is paginated today | Requests, messages, notifications, admin users and complaints will grow |
| **"Mark as sold / given away" quick action** on My listings | Faster than opening the edit form |
| **Photo reordering** (a main photo can already be chosen) | Listing photos are the first thing buyers judge |
| **Image resizing on upload** | Photos are stored full size because the `gd` extension is not installed; enabling `gd` would cut page weight a lot on mobile data |
| **Saved searches with alerts** ("tell me when a bicycle under Rs 10,000 appears in Kandy") | Makes the platform useful between visits |
| **Accessibility pass** — keyboard-only run-through, colour contrast, screen-reader labels on every form | §5 "inclusive and accessible" |
| **Empty and error states reviewed page by page** | Part of the criterion-2 click-through still outstanding |

### 6.4 Data and code

- **Soft-delete listings** that have completed requests, so exchange history and reviews keep
  their context instead of cascading away with the listing.
- **Database transactions** around multi-step changes (accepting a request and reserving
  the listing) so a failure halfway cannot leave them inconsistent.
- **Seed data refresh** for the final demo: more listings per category, rentals that are
  due and overdue (to show reminders), donation requests and pledges.
- **One consistent owner per module in the repository** from here on, so the commit history
  itself shows each member's individual contribution (criterion 3 is graded individually).

---

## 7. Suggested order for the final phase

| Order | Work | Who |
|---|---|---|
| 1 | Settle the proposal contradictions (§5.1); push to GitHub | All |
| 2 | Donation requests and pledges | M1 + M2 |
| 3 | Reminders, late alerts, recorded penalty, compensation claims | M2 + M4 |
| 4 | Admin: all listings and requests, reports, notify one member | M4 |
| 5 | Verified badge, reply to reviews, product-level ratings | M3 + M4 |
| 6 | Wishlist, disposal type, history filters, edit pending request, two-sided confirmation | M1 + M2 |
| 7 | Survey-driven items: condition guide, deposit, pickup flag, trust signals | M1 + M3 |
| 8 | Security items in §6.1 | M4 |
| 9 | Tests, UAT, diagrams, Figma, deployment, user manual, report | All |

Items 2–4 alone clear most of the ❌ rows: every one in §4.5 Donation Receiver and §4.4
Donor, the reminder and late-return rows in §4.6–4.8, and three of the four Admin gaps.
Each also introduces a new entity with a full CRUD set, which gives every member fresh,
individually attributable work for the final assessment.
