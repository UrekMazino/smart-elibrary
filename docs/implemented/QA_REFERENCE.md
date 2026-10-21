# DOST-FNRI eLibrary — QA Reference: Pages and Functions

As of 21 October 2026.

Every page and action of the eLibrary, with its address, who may use it and what to check. Addresses are relative to the site being tested (for example the staging server); `{id}` stands for a record or item number.

## Roles and test accounts

Test every page with the account its **Who** column names, and once with an account that should be refused. There are three kinds of account; a staff account also has a role, and the role decides which admin menus and buttons appear.

| Account | How to get one | What it can do |
| --- | --- | --- |
| Guest | Not signed in | Browse the OPAC: home, search, record pages, citations. Read, Download, Save and Inquire ask them to sign in. |
| Patron | Register at `/register`, or staff create one at `/admin/register` | Read, download, save, inquire, reserve and review; their own profile, inquiries and reservations |
| Staff | A super admin creates it at `/superadmin/admin-create` | The admin panel, limited by role. In the OPAC: search, read, download, share and save only; no inquiries, reservations or reviews, and their views and downloads are not counted |

| Role (staff) | Permissions (default) | Use it to test |
| --- | --- | --- |
| `super_admin` | All 35 | Everything, including administrators, roles and permissions |
| `developer` | All 35, and passes every check | Same as super admin |
| `system_admin` | 31: all except AI mode, material types, blocking/banning patrons, editing imports | Most admin pages |
| `cataloging_admin` | Records (create, edit, delete), batch import, record list, MARC template create | Cataloguing |
| `circulation_admin` | Reservations (view, manage), check-out/in, block/ban patrons | Circulation |
| `faculty` | Records (create, edit, delete), no record list | Record editing without lists |
| `serial_admin` | Dashboard only | That everything else is refused |

In the tables below, **Who** gives the account type and the permission a page needs (for example `edit.catalog`). A staff account without that permission gets "403 Forbidden" and does not see the menu item or button. Roles and permissions can be changed at `/admin/roles`; the lists above are the defaults.

## OPAC: public pages

These pages are open to everyone. Only published records ever appear; a held, deleted or unknown record answers "404 Not Found".

| Page | Address | Who | What to check |
| --- | --- | --- | --- |
| Landing | `/` | Anyone | Goes straight to `/home` |
| Home | `/home` | Anyone | Search box; Browse the Shelves (records by material type, with covers); Recent Uploads; Highlights; Most Downloaded; Related News. A signed-in patron who has not done the satisfaction survey sees its popup |
| Search results | `/results?query=…` | Anyone | Keyword search; filters (material type, author, year, full text available) with counts; sort; grid and list views; paging; suggestions while typing |
| Quick view (on results) | button on each result | Anyone | Opens the record's main details over the list without leaving it |
| Cite (on results) | button on each result | Anyone | Shows APA, MLA and Chicago; each Copy button copies its citation |
| AI mode (on results) | switch on the search page | Anyone, when enabled in Settings | Answers a question from the collection with numbered sources; at most 10 questions a minute; when the AI service is off it says "AI mode is temporarily unavailable" |
| Record page | `/full-description/{id}` | Anyone | Redirects to `/full-description/{id}-{title}`. Title, authors, ratings, view and download counts, Full Record table, Citation, Physical Copies, Reviews |
| Citation box | on the record page | Anyone | APA, MLA, Chicago switch: one shows at a time; Copy copies the one showing; the last style chosen is remembered after a reload |
| Read | button on the record page | Patron, staff | Opens the PDF reader over the page and shows all pages. Guest gets a sign-in popup. Greyed out when the record has no PDF |
| Download | button on the record page | Patron, staff | Saves the PDF. Guest gets a sign-in popup. A patron's download is counted, staff downloads are not |
| Share | button on the record page | Anyone | Copies the record's link |
| Save | star on the record page and results | Patron, staff | Adds or removes the record from Saved; guest gets a sign-in popup |
| Inquire | button on the record page | Patron | Opens the inquiry form; the message appears under Inquiries. Guest gets a popup with the library's email. Staff see it greyed out. At most 5 a minute |
| Schedule Reservation | box on the record page | Patron | Only when a copy is available. Date and time pickers, then a Confirm popup, then "Thank You!". Refused with a red message: weekends, past dates, times outside 8:00 AM to 5:00 PM. Staff see it greyed out |
| Submit review | button on the record page | Patron | Opens the review page: 1 to 5 stars (required), remarks up to 1,000 characters. One review per patron per record. Staff see it greyed out |
| Quick review popup | after a download, or a minute of reading | Patron | Asks for stars and remarks; saving reloads the page with the review shown |
| Sign in | `/login`, or the popup on any page | Guest | Patrons land on `/home`, staff on the admin. 5 wrong passwords lock sign-in for a short time |
| Register | `/register` | Guest | Name, email, contact number, password (8+ characters, typed twice). The email must not already be registered. At most 10 a minute |
| Forgot password | `/forgot-password`, then the emailed link `/reset-password/{token}` | Guest | Sends a reset link; the link sets a new password. At most 5 a minute |
| Verify email | `/verify-email` | Signed in | Resend the verification link (at most 6 a minute) |
| Satisfaction survey | `/csf/system-review` | Signed in | Seven ratings and three comments; a second try says it is already done |
| Sign out | menu item | Signed in | Ends the session; pages that need sign-in then ask for it |
| Sitemap and robots | `/sitemap.xml`, `/robots.txt` | Anyone | Lists published records only; `robots.txt` names the sitemap |

## OPAC: patron account

A signed-in patron reaches these from the **Welcome, {name}** menu at the top right; the red badges count unread replies. A staff account sees **Return to admin** instead of this menu and is sent to `/home` if it opens these addresses.

| Page | Address | Who | What to check |
| --- | --- | --- | --- |
| Profile | `/patron/profile` | Patron | Edit name, email (must be unused), contact number, birthdate (before today), affiliation, sex, patron type (required), region, address. Library code shown as a QR code |
| Change password | on the Profile page | Patron | Needs the current password; new password 8+ characters, typed twice. At most 10 tries a minute |
| Saved | **Saved** button in the search bar | Patron | Shows how many records are saved; opens `/results?saved=1`, the saved records with the usual filters. The star on a record adds or removes it |
| Reservations | `/patron/transactions` | Patron | Each reservation with its code, title, visit date and time, and status (For Approval, Approved, Open = checked out, Declined, Closed = returned). **Messages** opens a chat with the library about that reservation |
| Reservation chat | Messages button on a reservation | Patron | Send a message; the library's replies appear; opening it clears the badge. Only the patron's own reservations open. A blocked account cannot send |
| Inquiries | `/patron/inquiries` | Patron | Each inquiry with its record and messages; reply in the thread; "Seen" marks. Only the patron's own inquiries open. A blocked account cannot reply |
| To Review | `/patron/to-review` | Patron | Records the patron read or downloaded and has not reviewed yet; reviewing one removes it from the list |

## Admin: cataloguing

Every page here needs a staff account with `access.cataloging`, plus the permission shown. New records start **on hold** and appear in the OPAC only once published.

| Page or action | Address | Who | What to check |
| --- | --- | --- | --- |
| Create New Record | `/admin/create-record` | `create.catalog` | MARC editor (choose a template, fill fields), or import one MARC file. Saving opens the new record's page; the record is on hold |
| Record List | `/admin/record-list` | `view.catalog.list` | Published records only. Search, filters (material type, author, year, full text, cover) with counts, sort, paging |
| Bulk actions | checkboxes on Record List and On-Hold | Publish/Hold: `publish-hold.catalog`; Delete: `delete.catalog` | Publish, hold, delete or re-index the ticked records; each asks for confirmation. At most 30 a minute |
| Bulk export | on Record List and On-Hold | `access.cataloging` | Ticked records as MARC21 (`.mrc`) or MARCXML (`.xml`); `/admin/export-records/{format}` exports all |
| On-Hold | `/admin/on-hold-list` | `view.catalog.list` | Held records only; same search, filters and bulk actions as Record List |
| Record page | `/admin/view-record/{id}` | `access.cataloging` | Details, citations (each with Copy), copies, reviews. An unknown number answers 404 |
| Publish / Hold | buttons on the record page | `publish-hold.catalog` | The record appears in, or disappears from, the OPAC and its search |
| Edit | `/admin/edit-record/{id}` | `edit.catalog` | MARC editor with the record's fields; saving updates the OPAC. Fixing a problem the import flagged clears that flag |
| Delete | button on the record page | `delete.catalog` | Asks for confirmation; the record leaves the lists and the OPAC |
| Cover photo | upload or remove on the record page | `edit.catalog` | JPG, PNG or WEBP up to 10 MB; other files refused with a message. Removing asks for confirmation. A generated title cover shows when there is no photo; it can be turned off per record |
| Full text (PDF) | upload or remove on the record page | `edit.catalog` | PDF up to 100 MB. **Read PDF** opens it in a new tab and **Download** saves it, through `/admin/full-text/{id}`; both work for held records too. Removing asks for confirmation |
| Copies | add, edit, delete on the record page | `edit.catalog` | Circulation number (unique), source, location, status. A copy marked Available makes the OPAC show the reservation box |
| Export MARC | `/admin/export-record/{id}/{format}` | `access.cataloging` | `mrc` or `xml`; the file opens in a MARC tool with the record's fields |
| Re-index for AI | button on the record page | `access.cataloging` | Queues the record for the AI search; it shows in the AI Sync Monitor |
| Import flag | banner on the record page | `edit.catalog` | Shows the problems the import found; **Mark reviewed** removes it |
| Batch Import | `/admin/create-batch` | `create.catalog.import` | Upload a MARC file (`.mrc`); the analysis shows record counts and material types; map types, then import (an "Importing please wait…" bar shows meanwhile); ends on the import report |
| Batch list | `/admin/batch-list` | `view.catalog.importlist` | Each import with file name, records, imported, skipped and "needs review" counts |
| Batch records | `/admin/batch-records/{id}` | `view.catalog.importlist` | The records of one import, with the same filters as Record List |
| Publish or hold a batch | on the batch list | `publish-hold.catalog` | All records of the import change at once |
| Delete a batch | on the batch list | `delete.catalog.import` | Asks for confirmation; removes the import's records |
| Import report | `/admin/batch-report/{id}` | `view.catalog.importlist` | Skipped records and imported-but-flagged records, each with its problems (no title, no author, possible duplicate…). **Download skipped records** gives a `.mrc` of the skipped ones |
| MARC Templates | `/admin/marc-templates` | `access.configuration` + `view.config.template` | List and search templates |
| Create / edit template | `/admin/marc-templates/create`, `/admin/marc-templates/edit` | `create.config.template`, `edit.config.template` | Add MARC tags and subfields; the template appears in Create New Record |
| Delete template | on the template list | `delete.config.template` | Asks for confirmation |
| Material Types | `/admin/utilities/material-types` | `access.configuration` + `manage.material.types` | Add, rename, hide or show, merge into another type, delete. A type still in use cannot be deleted (the message says to merge it); MARC record types can only be hidden |

## Admin: circulation and inventory

Circulation pages need `access.circulation`; Physical Count needs `access.inventory`. A reservation moves For Approval → Approved → Open (checked out) → Closed (returned), or For Approval → Declined.

| Page or action | Address | Who | What to check |
| --- | --- | --- | --- |
| Reservation | `/admin/reservations` | `circulation.reservation.view` | Every reservation: title, requested by, email, status, code, date of visit; search and sort. Names and titles show as plain text, even when they contain symbols such as `<` or `>` |
| Material and patron details | click a title or a name in the list | `circulation.reservation.view` | Popups with the record's copies, and the patron's details |
| Approve | button on a For Approval row | `manage.circulation.reservation` | Pick an available copy first; the copy becomes Reserved and the status Approved |
| Decline | button on a For Approval row | `manage.circulation.reservation` | Asks for confirmation; status Declined |
| Check-out from a reservation | button on an Approved row | `manage.circulation.reservation` | Opens Check-Out with the reservation filled in |
| Revert | button on an Approved row | `manage.circulation.reservation` | Back to For Approval; the copy returns to Available |
| Reply | button on each row | `manage.circulation.reservation` | Chat with the patron about that reservation; the red badge counts unread patron messages. Greyed out for a blocked account |
| Block or ban the patron | arrow beside Reply | `manage.patron.status` | Blocked: no chat or inquiries. Banned: no reading or downloading. The badge shows in the list |
| Check-Out/Check-In | `/admin/check-out-in` | `manage.circulation.checkouts` | Find a patron (library code) and a copy (circulation number) |
| Check-out | on Check-Out/Check-In | `manage.circulation.checkouts` | Due date and overdue fine rate; status Open; the copy is no longer Available |
| Edit a check-out | on Check-Out/Check-In | `manage.circulation.checkouts` | Change the due date or fine rate of an open loan |
| Check-in | on Check-Out/Check-In | `manage.circulation.checkouts` | Overdue hours and fine calculated; record amount paid and amount waived; status Closed; the copy is Available again |
| Transaction | `/admin/borrowed` | `access.circulation.transaction` | Every loan, newest first: title, patron, transaction code, reservation code (or Walk-in), status, date; each opens its details |
| Physical Count | `/admin/inventory` | `access.inventory` | Enter or scan circulation numbers of copies on the shelf; the list pages and sorts through every copy; remove a wrong entry; **Reset** starts a new count (asks first) |
| Present and missing reports | `/admin/inventory/presented`, `/admin/inventory/reportpresented`, `/admin/inventory/reportmissing` | `access.inventory` | Copies counted, and copies on record but not counted; printable |

## Admin: people and roles

Patron pages need `access.accounts`; administrators, roles and permissions need the `super_admin` or `developer` role. Every staff account can edit its own profile.

| Page or action | Address | Who | What to check |
| --- | --- | --- | --- |
| Patrons | `/admin/patron` | `access.accounts` | Every patron: name, library code, contact, email, location, joined; filter by Active / Blocked / Banned; search. Names show as plain text |
| Generate Library Code | action on a patron | `edit.patron` | Gives the patron a new library code |
| Reset Password | action on a patron | `edit.patron` | Shows a new random password once; the patron can sign in with it |
| Block / Unblock | action on a patron | `manage.patron.status` | Blocked patrons cannot chat or send inquiries; a badge shows in lists |
| Ban / Unban | action on a patron | `manage.patron.status` | Banned patrons cannot read or download, even in a session already open |
| Delete patron | action on a patron | `delete.patron` | Asks for confirmation; the patron disappears from the list and cannot sign in |
| Register a Patron | `/admin/register` | `create.patron` | Creates a patron with a library code. **Known issue:** the message says the password was emailed, but no email is sent; the patron has to use Forgot password |
| Administrators | `/superadmin/admin-list` | `super_admin` or `developer` role | Every staff account with its role |
| Create administrator | `/superadmin/admin-create` | same | Name, email, role; the new account opens the admin |
| Change role | on Administrators | same | Only a developer can give the `developer` role. The last super admin cannot be changed to another role |
| Reset password, library code, delete | actions on Administrators | same | As for patrons; delete asks for confirmation |
| Roles | `/admin/roles` | same | Create, rename, delete roles; tick or untick each permission of a role. A staff member's menus change accordingly at their next page load |
| Permissions | `/admin/permissions` | `super_admin` or `developer`; add and delete: `developer` only | List and edit permission names |
| My profile | `/admin/profile` | Any staff | Edit own name and email (must be unused); change password with the current one (at most 10 tries a minute) |

## Admin: dashboard, news, inquiries, reports, settings

The rest of the admin, in sidebar order. Staff land on the Dashboard after signing in (or on the first page their role allows).

| Page or action | Address | Who | What to check |
| --- | --- | --- | --- |
| Dashboard | `/admin/dashboard` | `access.dashboard` | Library at a glance, Activity chart (choose a metric and a period; switch to a table), Top materials, Titles by material type, Patrons by category, Newest patrons, Recent reviews, Needs attention |
| Notification bell | top bar | `access.notifications` | Counts pending reservations, unread inquiries and reservation messages; each item opens its page, and the count drops once it is handled |
| OPAC button | top bar, `/admin/opac` | Any staff | Opens the OPAC signed in; **Return to admin** (`/admin/opac/return`) goes back to the admin page it came from |
| Inquiries | `/admin/inquiry` | `access.inquiry` | Every inquiry, unread ones marked; open a thread, reply, see "Seen by"; block or ban the patron from there |
| News Article | `/admin/news` | `access.news` | List, edit, delete (asks first) |
| Add or edit news | `/admin/news/add`, `/admin/news/edit/{id}` | `access.news` | Title, body (formatting kept, scripts removed), cover image (JPG, PNG or WEBP up to 2 MB), link (must start with http:// or https://), Featured. Shows under Related News on the OPAC home |
| Reports | `/admin/report` | `access.reports` | Pick a report and a period, preview, print: Acquisition, Patron, Borrowed Materials, Inquiries, CSF (satisfaction survey), Publication Downloads, Publication Uploads |
| Visitor log | `/admin/visits` | `access.reports` | Not in the menu; opens by address |
| Settings | `/admin/settings` | `access.configuration` + `access.settings` | AI mode on/off and its status check (`manage.ai.mode`); hide the site from search engines; Google verification code; generated covers on/off; **Save Settings** |
| Search index | card on Settings | same | **Scan** reports records missing from search; **Rebuild** re-creates the index (at most 5 a minute) |
| Sitemap | card on Settings | same | Regenerates `/sitemap.xml` (at most 5 a minute) |
| Clear cache | card on Settings | same | Home page shelves and counts refresh; patrons and staff stay signed in (at most 10 a minute) |
| Utilities | `/admin/utilities` | `access.configuration` | Cards for the tools the role may use (Material Types); "No utilities for your role yet" otherwise |
| AI Sync Monitor | `/admin/ai-sync-monitor` | `access.configuration` + `access.ai.sync.monitor` | Pending, Failed and Synced counts; filter by change type and status; long titles wrap, and phones show each change as a card. **Re-sync** queues one record; **Run sync now** processes the queue (needs the AI service running) |

## Must not work: security checks

Repeat these every test round: each one should be refused. A "pass" here means the refusal happened. The automated test `tests/Feature/RouteSecurityTest.php` also checks every route on every push: sign-in, staff-only and per-permission refusals, the security token on every form, and no server error on any page, even for a missing record.

| Try this | As | Expected |
| --- | --- | --- |
| Open a PDF by its file address: `/pdf/{file}.pdf` or `/storage/pdf/{file}.pdf` | Anyone | 404. Full texts open only through Read and Download |
| `/read/{id}` | Guest | The sign-in page |
| Read or download a record after it was put on hold | Patron | 404, even from a page opened before the hold |
| `/admin/full-text/{id}` | Patron | 403 Forbidden |
| Any `/admin/...` address | Guest | The sign-in page |
| Any `/admin/...` address | Patron | Sent back to the OPAC |
| An admin page outside the role, for example `/admin/settings` as `cataloging_admin` | Staff | 403 Forbidden, and no menu item for it |
| `/patron/profile`, `/patron/inquiries` | Staff | Sent to `/home` |
| Another patron's reservation chat or inquiry (change the code or number in the address) | Patron | 403 Forbidden |
| `/full-description/{id}` of a held, deleted or unknown record | Anyone | 404 |
| `/admin/view-record/999999` (no such record) | Staff | 404, not an error page |
| `/csf/get-ratings/json` and `/marcfile/upload` (removed) | Anyone | 404 |
| `/opac/assets/contact.php`, or any `.php` address other than the site itself | Anyone | 404 or 403, never a page |
| A folder address such as `/frontpages/` or `/thumb/` | Anyone | 404 or 403, never a list of files |
| Register or edit a patron with a name like `<b>Test</b>` or `<img src=x onerror=alert(1)>`, then view it in reviews, the admin Reservation list, reservation chat, Patrons | Patron, then staff | The name shows exactly as typed, as text; no bold, no image, no popup |
| A news link starting with `javascript:` | Staff | Refused with a message |
| A cover upload that is not really an image (for example a text file renamed `.jpg`) | Staff | Refused with a message |
| Send 6 inquiries within a minute; then open several quick views and send one more inquiry the next minute | Patron | The 6th inquiry is refused ("Too Many Requests"); quick views never use up the inquiry allowance, so the later inquiry goes through. Each limit counts its own action |
| 6 wrong passwords in a row | Guest | Sign-in locked for a short time with a message |
| Open any page and check its response headers (browser developer tools, Network tab) | Anyone | `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin` |
| Show any eLibrary page inside a frame on another website | Anyone | The browser refuses to show it |
