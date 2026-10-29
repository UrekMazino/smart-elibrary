# Security notes

The rules the eLibrary enforces, written down as OWASP ASVS 5.0 asks: input rules (2.1.1), sign-in and
rate limits (6.1.1), who may do what (8.1.1), how fast vulnerable packages are patched (15.1.1), how
long a sign-in lasts (7.1.1, 7.5.2), and what is logged (16.1.1: section 2 and
[`LOG_INVENTORY.md`](LOG_INVENTORY.md)). Checked against the code on 2026-10-29 (branch `FNRI-Week-4`). When a
rule in the code changes, change it here in the same commit.

## 1. Input rules (ASVS 2.1.1)

Every rule below is checked on the server; a check in the browser only saves people a round trip.

| Input | Rule | Where |
|---|---|---|
| Email address | A valid address, at most 255 characters (190 on the chat button); one account per address, whatever its case | Registration, patron profile, chat button |
| Password | 12 to 128 characters, any characters; not one of 10,694 common passwords; not known from data breaches (Have I Been Pwned, only 5 characters of the SHA-1 hash sent). Stored as Argon2id | `AppServiceProvider` (`Password::defaults()`), `config/hashing.php` |
| Password the system gives out | 16 random characters in groups of 4, no look-alike characters | `generateRandomPassword()` |
| Full name | Text, at most 255 characters (150 on the chat button) | Registration, profile, chat button |
| Contact number | Accounts: text, at most 255. Chat button: digits with an optional leading `+`, spaces, dashes or brackets; at least 7 digits; at most 30 | Registration, profile, chat button |
| Birthdate, sex, patron type, region | A date before today; Male, Female or empty; a patron type and region that exist | Patron profile |
| Inquiry | Subject required, at most 125 characters; message required, at most 5000; staff, banned and blocked accounts refused | Record page inquiry, chat button |
| Review | 1 to 5 stars, remarks at most 1000 characters; published records only; one review per patron per record | Review form, quick review popup |
| Reservation | A record that exists; a visit date today or later, Monday to Friday; a time from 8:00 AM to 5:00 PM | `ReservationOpacController` |
| Search page address | Field, sort and view from fixed lists; page 1 to 2000; at most 6 advanced rows | `CatalogSearchParams` |
| News | Title at most 255; link `http`/`https` only, at most 2048; cover JPEG, PNG or WebP, at most 2 MB; body cleaned by an allowlist sanitiser | `NewsController`, `helper_joe.php` |
| Cover image | JPEG, PNG or WebP by name and by content, at most 10 MB; renamed by the server | `CatalogingController` |
| Full-text PDF | `.pdf`, at most 100 MB; stored outside `public/`. Content is not yet checked (assessment gap 13) | `CatalogingController` |
| MARC import | `.mrc` checked by content (a text or PHP file named `.mrc` is refused), at most 40 MB | `CatalogingController` |
| Ids in addresses | Numbers only (`whereNumber`) | `routes/web.php` |

## 2. Sign-in and rate limits (ASVS 6.1.1)

- **Sign-in:** 5 failed attempts for one email address from one IP address lock that pair out for 60
  seconds (`LoginRequest`). Banned accounts cannot sign in.
- **Passwords:** see section 1. Changing a password needs the current one. An account made with "Continue
  with Google" has none until its owner sets one, which needs a Google sign-in in the last 15 minutes or
  "Confirm with Google" instead (`App\Services\GoogleSignIn`); signing in with a password fails for it.
- **Sign-up: an "I'm human" check** (Cloudflare Turnstile, `App\Rules\HumanCheck`), in place of email
  verification. Cloudflare confirms the form's token on the server (siteverify) before the account is made;
  a token works once, within 5 minutes, for the form it was made for. If Cloudflare can't be reached the
  sign-up is refused, not let through. Off while `TURNSTILE_SITE_KEY` or `TURNSTILE_SECRET_KEY` is unset.
- **"Continue with Google"** (patrons; `GoogleSignInController`, Laravel Socialite; ASVS V10, the eLibrary
  as OAuth client): the code flow with `state` and PKCE (S256), scopes `openid email profile`; Google's
  tokens are not kept, only the account's Google id (`users.google_id`). A new Google account becomes a
  patron; an existing account with the same address is linked, if Google says the address is confirmed
  (`email_verified`), and keeps its password. Refused: staff accounts (they keep their password and its
  limits), banned accounts (the banned popup, as with a password), removed accounts, an address Google
  hasn't confirmed, and an address whose account is linked to another Google account. No "Keep me signed
  in". Off (no button, routes 404) while `GOOGLE_CLIENT_ID` or `GOOGLE_CLIENT_SECRET` is unset.
  Known limit: an account someone else opened with your address before you first continue with Google
  keeps the password they chose; a later change could reset it at linking.
- **Forms people could flood:** the chat button has a hidden field that spam bots fill in; such posts
  are accepted and thrown away.
- **Security event log** (ASVS V16; since 29 October 2026; `App\Services\SecurityLog`): one file,
  `storage/logs/security.log`, one event a line, times in **UTC**:
  - signing in (with the password, with Google, or kept signed in), a wrong email or password, a lockout
    (once per lockout, however many tries), a refused sign-in (banned; Google's refusals with the reason),
    signing out;
  - accounts made (sign-up, Google, by staff with the role), passwords changed (own, reset link, reset by
    staff), ban/unban, block/unblock, accounts deleted, sessions ended (own, one person's, everyone's);
  - a role changed for an account, a permission given to or taken from a role, roles and permissions made
    or deleted;
  - every refused page or action (403), with the address asked for and the reason shown.

  Each line has the account, the staff member who did it, the IP address, the browser and the page, and
  never a password, token or session id. Typed values are JSON-encoded, so they can't start a line of
  their own. The file is readable by the web server's group only (0640), and every write locks it.
- **Keeps a year.** Nightly at 03:05, `security-log:prune` removes the entries older than a year from the
  top of the same file and keeps the rest; the file is never deleted or replaced.
- **Read on** Administration → Accounts → **Security Log** (developer and super admin, by role, whatever
  else a role has): a date range of up to 31 days, newest first, filtered by kind and searched by email, IP
  address or page. Nothing in the app edits or deletes an entry. What else the system logs:
  [`LOG_INVENTORY.md`](LOG_INVENTORY.md).

Limits per visitor (signed in: per account; otherwise per IP address), per minute, each action counted
separately (`app/Http/Middleware/ThrottleRequests.php`):

| Action | Limit |
|---|---|
| Register | 10 |
| Continue with Google: going to Google; coming back; "Confirm with Google" | 10 each |
| Request a password reset link; reset a password | 5 each |
| Resend the verification email; verify an email address | 6 each |
| Change your password (patron or staff) | 10 |
| Chat button (General inquiry); inquiry from a record page | 5 each |
| Reply to an inquiry or a reservation chat | 20 |
| Mark an inquiry as read | 60 |
| Quick review; "Not now" on it | 10; 30 |
| Save a record | 60 |
| Search: find an author, Quick view | 60 each |
| AI answer | 10 |
| Admin: bulk record actions and exports | 30 (export downloads 60) |
| Admin Settings: refresh cached pages 10, AI status 20, check search 10, rebuild search index 5, rebuild sitemap 5 | as listed |
| Admin Appearance preview | 120 |
| Staff session warning: time left; "Stay signed in" | 60 each |
| Sign out one of your sessions, or all the others (takes the password) | 10 |
| Signed in now: sign one person or everyone out (takes the password) | 10 |
| Statistics watch: take accounts out of the rankings; put one back | 30 each |
| Rankings: refresh the home page | 10 |

Not limited by the app: the public search results, search suggestions and record pages. In production
the web server or a proxy in front of it should cap requests per IP address.

## 3. Who may do what (ASVS 8.1.1)

**Kinds of user.** Guests (no account), patrons (`usertype` Patron) and staff (`usertype` Administrator
or Developer, with one or more roles).

**Pages and actions (function level).**
- Every `admin/` and `superadmin/` route needs a signed-in staff account and names a permission. A few
  are open to any staff member: their own profile and appearance, the OPAC button and the MARC look-ups
  of the record editor. `tests/Feature/RouteSecurityTest.php` fails when a new route breaks this.
- Permissions belong to roles. The matrix lives in Administration → Accounts → Roles, and its seed copy
  in `database/seeders/data/role_has_permissions.json`.
- A module's pages also need the module's access permission: Cataloging, Circulation, Accounts &
  Patrons, Configuration (Settings, with `access.settings`) and Utilities (`access.utilities`, then each
  utility's own permission). The Roles page gives each module a switch per role and locks what's inside
  while it's off; Ban / Block Patrons is the exception (Inquiries and Transactions use it, so it needs only itself).

| Role | Permissions (of 40) |
|---|---|
| `developer` | Everything, including permissions added later (`Gate::before`) |
| `super_admin` | All 40 |
| `system_admin` | 34: all except AI mode, material types, patron ban/block, editing import batches, Signed in now and Statistics watch |
| `cataloging_admin` | 10: cataloguing, records, imports, MARC templates |
| `circulation_admin` | 7: reservations, check-out/in, patron ban/block |
| `faculty` | 6: create, edit and delete records |
| `serial_admin` | 2: dashboard and notifications |

**Records and data (data level).**
- Patrons reach only their own profile, inquiries, reservations, saved records, reviews and
  notifications. Inquiry threads are checked per inquiry (`InquiryController::authorizeInquiry`).
- In an inquiry thread a patron sees "Library", never which staff member answered.
- Staff can open every inquiry. A guest's inquiry is answered by email only.
- Staff browsing the OPAC cannot inquire, reserve or review, and their visits are not counted.
- Full texts: read and download only published records, signed in and not banned; held records only
  in the staff preview.
- Account states: banned = signed out everywhere and cannot sign in, read or download (section 5); blocked =
  cannot send inquiries or chat.

## 4. Patching vulnerable packages (ASVS 15.1.1)

**How we find out.**
- Dependabot opens pull requests weekly for PHP (Composer) and JavaScript (npm) packages, monthly for
  GitHub Actions (`.github/dependabot.yml`).
- The Security workflow runs every Monday at 09:00 Manila time: a gitleaks scan of the whole history,
  `composer audit` and `npm audit` (`.github/workflows/security.yml`).
- A gitleaks pre-commit hook stops secrets before they are committed (`.githooks/pre-commit`).

**Deadlines, from the day the advisory is known.**

| Severity | Fix in production within | If it can't be fixed in time |
|---|---|---|
| Critical | 3 days | Turn off the affected feature until it is |
| High | 7 days | Write down why and the new date in this file |
| Medium | 30 days | Write down why and the new date in this file |
| Low | The next monthly update | — |

**Routine.**
- Monthly: `composer update` and `npm update` within the version constraints, run the test suite, deploy.
- Stay on PHP and Laravel versions that still get security fixes; move to a new minor version within
  3 months of its release.
- Server software (operating system, MySQL, Apache/PHP, Meilisearch) gets its security updates monthly,
  or by the deadlines above for a critical or high advisory.

**Exceptions.** None at present.

## 5. Sessions (ASVS 7.1.1, 7.3.1, 7.3.2, 7.4.2, 7.4.3, 7.4.5, 7.5.2)

**How long a sign-in lasts.**

| Account | Signed out after this long without activity | Signed out this long after signing in, however active |
|---|---|---|
| Staff | 30 minutes | 8 hours |
| Patron | 2 hours | 12 hours |
| Anyone who ticked "Keep me signed in" (staff or patron) | No limit, on that device | 30 days |

- **Activity** is any request to the site: opening a page, saving, searching. The staff warning asking
  how much time is left does not count.
- **Staff warning.** 2 minutes before a limit, a dialog counts down. Before the idle limit, "Stay signed
  in" (or Esc) starts the 30 minutes again. Before the 8-hour limit it can only warn: save your work.
  It checks with the server first, so working in another tab keeps it away. Staff browsing the OPAC get
  it too; not on a device kept signed in, which has no idle limit.
- **After a sign-out** the sign-in page says why, and signing in again returns to the page.
- **"Keep me signed in"** on the sign-in page, for staff and patrons (staff since 28 October 2026): 30
  days on that device, with no idle limit. The cookie is `__Host-remember_web`: Secure, HttpOnly, 30 days. Signing out on any device ends it on every device
  (Laravel keeps one remember token per account).
- **Password change.** Every other session of the account ends at its next request, and "Keep me signed
  in" stops working on every device. This covers a change in the profile, a reset link and a librarian's
  reset (Accounts → a patron or staff member → Reset Password). The device where someone changed their
  own password stays signed in. A change to the password hashing settings has the same effect once per
  account, when its stored hash is upgraded at the next sign-in.
- **Compared with NIST SP 800-63B (2017).** Staff limits are within AAL2: sign in again every 12 hours,
  and after 30 minutes without activity. Patron accounts hold only the patron's own loans, inquiries,
  reservations and reviews, so they follow AAL1: sign in again at least every 30 days, the length of
  "Keep me signed in". A staff member who ticks "Keep me signed in" is at AAL1's length on that device,
  and whoever uses that device has their staff access for up to 30 days: tick it only on your own
  computer, locked when you leave it. Signing out there, or "Sign out all other sessions" on the
  profile, ends it.
- **Where you're signed in** (the profile page, staff and patrons): every browser signed in to the
  account, with when it was last active and its IP address; "Sign out" for each, and "Sign out all other
  sessions". Both ask for the current password first (ASVS 7.5.2). Signing out one session deletes it. If
  "Keep me signed in" was ticked on that device, the account also gets a new remember token, which ends
  "Keep me signed in" on the other devices too (each stays signed in until its session ends). "Sign out
  all other sessions" is Laravel's `logoutOtherDevices` (a new hash of the same password), and deletes
  the other sessions. An account made with Google has no password: instead of typing one it needs a Google
  sign-in in the last 15 minutes, or "Confirm with Google" (Google again, for the same Google account);
  "Sign out all other sessions" then deletes the other sessions and gives the account a new remember token. A device kept signed in by its cookie is listed only while it has a session; the
  profile page says so, and always offers "Sign out all other sessions". The page never shows a session id, only an HMAC of it.
- **Kept in the database** (`SESSION_DRIVER=database`, table `sessions`): for every visitor, signed in or
  not, the IP address, the browser (user agent), the last activity and the session's data. A row goes
  when its session ends (sign-out, a limit) or `SESSION_LIFETIME` after its last request. Switching from
  files signs everyone out once.
- **Banned accounts** are signed out everywhere as soon as the ban is set, "Keep me signed in" included,
  and any request a banned account still makes is refused: the sign-in page then shows the banned popup
  (`EndBannedSessions`, ASVS 7.4.2). The ban used to be checked only when signing in with the password. A
  deleted account is signed out too: it can no longer be found to sign in.
- **Signed in now** (Administration → Accounts; permission `access.sessions`: super admin and developer):
  how many staff and patrons are signed in, visitors not signed in over the last 30 minutes (roughly: one
  per browser, crawlers left out), and each person with their browsers, when they signed in, when they
  were last active and whether "Keep me signed in" is on. No IP addresses. "Sign out" ends all of one
  person's sessions and their "Keep me signed in"; "Sign out everyone" does that for everyone but your
  own session. Both ask for your password first (ASVS 7.4.5). Not your own account (use Log Out, or your
  profile), and only a developer can sign out a developer. The page doesn't refresh by itself, which
  would keep the viewer's own session active. Who signed whom out is in the security event log
  (section 2).
- **Where.** `config/session.php` ("limits"), `config/auth.php` (`remember`),
  `App\Services\SessionLimits`, `App\Http\Middleware\EnforceSessionLimits`, `EndBannedSessions`,
  `resources/views/partials/session-timeout.blade.php`, `App\Services\BrowserSessions`,
  `partials/browser-sessions*.blade.php` and `SignedInNowController`; the password check is Laravel's
  `AuthenticateSession` (`app/Http/Kernel.php`). `SESSION_LIFETIME` (how long an unused session is kept,
  180 minutes) must stay above the longest idle limit, or the sign-in page cannot say why a patron was
  signed out. Tests: `SessionLimitsTest`, `BrowserSessionsTest`, `BanEndsSessionsTest`, `SignedInNowTest`
  (`tests/Feature`).

## 6. Visitor addresses (Data Privacy Act, RA 10173)

Changed 2026-10-28. Until then the rule (R15, 2026-10-16) was: no visitor IP addresses at all, because
nothing used them and the Act's proportionality principle (s. 11) says not to keep what is not needed.
Now the library's statistics need to tell a reader apart from a reload or a script, so addresses may be
used, and stored, where a feature needs them, on these terms:

- **A purpose first.** Each use is written down in the table below before it ships: what is used, why and
  for how long. Only that purpose: never to profile readers, never shared outside the agency.
- **Lawful basis:** the agency's functions and its legitimate interest in protecting its systems and the
  honesty of its statistics (s. 12 (e), (f)).
- **As little as works.** A keyed hash (HMAC with the app key) instead of the address wherever comparing
  is enough; the address itself only where a person has to read it (an abuse investigation).
- **Retention.** The shortest that serves the purpose; anything kept in the database at most 90 days,
  then deleted.
- **Notice.** The OPAC's Privacy notice (footer link) says what is used and why; update it with this table.
- **Access.** Only the staff who need that report; never on public pages or in exports.
- Questions, or a use beyond these terms: the agency's Data Protection Officer.

| Where | What | Kept |
|---|---|---|
| Counting views (`saveView()`) | the account, or for a guest a keyed hash of IP address + browser (`visitorHash()`): a reload or a script counts once per material a day, and at most 120 views an hour count | in the cache until the day ends; on the guest's `views` rows (`visitor_hash`, from 28 October 2026) for 90 days, then removed (`visitors:forget`, nightly) |
| Statistics watch: guests (Administration → Accounts → Statistics watch) | the IP address behind each guest's hash (`visitor_addresses`), written once a day per guest: to see that unusual views came from one address (a script, a bot) | 90 days from when the guest was first seen, then deleted (`visitors:forget`); shown only to developer and super admin accounts, whoever else has the watch; never in exports |
| Rate limits (`throttle`) | the account, or a guest's IP address | the cache, for the limit's window |
| Sessions (`sessions` table) | IP address and browser of each session (Laravel's database sessions) | until the session ends; staff pages never show the address |
| "I'm human" check on sign-up (Cloudflare Turnstile) | the visitor's browser talks to Cloudflare directly; the server sends Cloudflare only the token, not the address | nothing kept by the eLibrary |
| "Continue with Google" | from Google: name, email address, whether Google confirmed it, the Google account's id | the id and the account's name and address, as long as the account |
| Security event log (`storage/logs/security.log`; section 2) | the IP address and browser of each sign-in, failure, lockout, refused page and account change, with the account and the email address typed: to investigate break-ins and misuse, which often come to light months later | **1 year**, then removed from the file nightly (`security-log:prune`); read by developer and super admin only. Longer than the 90 days above, for that purpose (decided 28 October 2026; to be confirmed with the Data Protection Officer) |

The `views` table holds no address or browser string, only a guest's keyed hash (`PrivacyTest`, `RankingIntegrityTest`).

What the statistics count and how: `RANKING_INTEGRITY.md`. The statistics watch shows accounts (name, email
address, when made, the day's activity) and guests (a code: the hash's first 8 characters) to the staff who have
its permission, and guests' IP addresses to developer and super admin only (decided 28 October 2026).
