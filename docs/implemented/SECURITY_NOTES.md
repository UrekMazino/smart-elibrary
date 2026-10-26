# Security notes

The rules the eLibrary enforces, written down as OWASP ASVS 5.0 asks: input rules (2.1.1), sign-in and
rate limits (6.1.1), who may do what (8.1.1), and how fast vulnerable packages are patched (15.1.1).
Checked against the code on 2026-10-26 (branch `FNRI-Week-4`). When a rule in the code changes, change
it here in the same commit.

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
- **Passwords:** see section 1. Changing a password needs the current one.
- **Forms people could flood:** the chat button has a hidden field that spam bots fill in; such posts
  are accepted and thrown away.

Limits per visitor (signed in: per account; otherwise per IP address), per minute, each action counted
separately (`app/Http/Middleware/ThrottleRequests.php`):

| Action | Limit |
|---|---|
| Register | 10 |
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
| Admin Settings: clear cache 10, AI status 20, index check 10, re-index 5, sitemap 5 | as listed |
| Admin Appearance preview | 120 |

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

| Role | Permissions (of 36) |
|---|---|
| `developer` | Everything, including permissions added later (`Gate::before`) |
| `super_admin` | All 36 |
| `system_admin` | 32: all except AI mode, material types, patron ban/block and editing import batches |
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
- Account states: banned = cannot sign in, read or download; blocked = cannot send inquiries or chat.

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
