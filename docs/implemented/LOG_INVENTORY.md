# Log inventory

Every log the eLibrary and its server keep: what is in it, where, for how long, and who reads it (OWASP ASVS
V16, "a log inventory"). Paths are for the Linux server in `DEPLOYMENT_CHECKLIST.md` (code in
`/var/www/elibrary`, web server user `www-data`). **Last checked:** 2026-10-29.

## The app's own logs (`storage/logs/`)

| Log | What is in it | Kept | Who reads it |
|---|---|---|---|
| **Security log**: `security.log` | Sign-ins, wrong passwords, lockouts, refused sign-ins, sign-outs, accounts made and deleted, password changes, bans and blocks, sessions ended, role and permission changes, refused pages (403). Times in UTC. IP address, browser, account, email address typed. Never a password, token or session id. | **1 year**: one file, appended to; the entries older than a year are removed from its top nightly at 03:05 (`security-log:prune`) | Developer and super admin, on Administration → Accounts → Security Log; on the server, the web server's group (`0640`) |
| **App log**: `laravel-YYYY-MM-DD.log` | Errors and warnings with their stack traces; failed calls to outside services (Meilisearch, Cloudflare, Google, the AI gateway); with `MAIL_MAILER=log` on staging, the emails themselves (password-reset links). Times in Philippine time. | 30 days: a file a day, the oldest deleted by Laravel (`config/logging.php`, channel `daily`) | The developer, on the server |
| `laravel.log` (one file) | The app log as it was written before 29 October 2026. Nothing new goes in. | Until deleted by hand | The developer |

Settings: `config/logging.php` (channels `security`, `daily`); `LOG_CHANNEL=stack`, `LOG_LEVEL` in `.env`
(`DEPLOYMENT_CHECKLIST.md` D1). The guests' addresses kept for the statistics watch are data, not a log:
`SECURITY_NOTES.md` section 6.

## The server's logs

Set up by MIS; listed so an investigation knows where to look. Ask MIS for the actual paths and periods.

| Log | What is in it | Usual place | Usual period |
|---|---|---|---|
| Web server access log | Every request: IP address, time, address asked for, status, browser | `/var/log/apache2/access.log` or `/var/log/nginx/access.log` | 14–52 weeks (`logrotate`) |
| Web server error log | PHP fatal errors, TLS and configuration errors | `/var/log/apache2/error.log` or `/var/log/nginx/error.log` | as above |
| PHP-FPM log | Worker crashes, slow requests | `/var/log/php8.5-fpm.log` | as above |
| MySQL error log | Start, stop, crashes, failed connections | `/var/log/mysql/error.log` | as above |
| Meilisearch | Its service output | `journalctl -u meilisearch` | the system journal's limit |
| Scheduler | The nightly jobs (`visitors:forget`, `security-log:prune`, `sitemap:generate`, `search:reconcile`) | cron sends its output to `/dev/null` (`DEPLOYMENT_CHECKLIST.md` D4) | not kept |
| SSH sign-ins | Who signed in to the server, from where | `/var/log/auth.log` | as above |

## Rules

- Times: the security log in UTC; the app log in the app's timezone (`Asia/Manila`); the server's logs in the
  server's timezone. Compare with care.
- No passwords, tokens, session ids or full-text contents in any app log.
- A new log, or a change to what one holds or how long, is added here in the same commit.
