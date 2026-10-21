1. Clone the reposatory
2. run composer update
3. create a database "elibrarydb"
4. import the database located at database/sql/elib_new.sql
5. create an .env file and configure its parameter
6. run php artisan storage:link

# smart-eLibrary

FPRDI/DOST agri-forestry catalog / OPAC (Laravel), built RAG-ready for the **Project Y** local AI stack.

**📖 Documentation**
- **Full-stack rebuild** (this app + Hermes + RAG): [`DEPLOYMENT_RUNBOOK.md`](https://github.com/UrekMazino/hermes-with-rag/blob/main/DEPLOYMENT_RUNBOOK.md) in the `hermes-with-rag` repo — the single ordered entry point.
- **Deploying this app:** [`DEPLOYMENT_CHECKLIST.md`](docs/implemented/DEPLOYMENT_CHECKLIST.md) — ordered deploy steps, required `.env`, out-of-git state, and the cross-repo change record.
- **This repo's docs** ([`docs/`](docs/README.md), sorted into implemented and not implemented):
  - Implemented: [`LARAVEL_UPGRADE_RUNBOOK.md`](docs/implemented/LARAVEL_UPGRADE_RUNBOOK.md) · [`MEILISEARCH_SETUP.md`](docs/implemented/MEILISEARCH_SETUP.md) · [`RAG_CATALOG_SYNC_PLAN.md`](docs/implemented/RAG_CATALOG_SYNC_PLAN.md) · [`OPAC_SEO_PLAN.md`](docs/implemented/OPAC_SEO_PLAN.md) · [database documentation](docs/implemented/database/)
  - Not implemented: [`API_INTEGRATION_PLAN.md`](docs/not-implemented/API_INTEGRATION_PLAN.md) · [`RAG_INTEGRATION_PLAN.md`](docs/not-implemented/RAG_INTEGRATION_PLAN.md) · [`EVENT_DRIVEN_SIDE_EFFECTS_PLAN.md`](docs/not-implemented/EVENT_DRIVEN_SIDE_EFFECTS_PLAN.md) · [`Z3950_SRU_INTEGRATION_PLAN.md`](docs/not-implemented/Z3950_SRU_INTEGRATION_PLAN.md)

**🔒 Security checks**
- **On GitHub, every push and pull request:** [`.github/workflows/security.yml`](.github/workflows/security.yml) scans for secrets (gitleaks, full history), vulnerable PHP / npm packages (`composer audit`, `npm audit`) and risky code (Semgrep); [`tests.yml`](.github/workflows/tests.yml) runs PHPUnit against a throwaway MySQL. Dependabot ([`.github/dependabot.yml`](.github/dependabot.yml)) opens update PRs weekly.
- **On your machine:** run `git config core.hooksPath .githooks` once per clone. Then [`.githooks/pre-commit`](.githooks/pre-commit) blocks commits that add a secret, and [`.githooks/pre-push`](.githooks/pre-push) runs the same checks as the workflows ([`scripts/security-check.sh`](scripts/security-check.sh), ~30 s) and stops a push if any fail. Run them by hand with `composer security` (`composer security -- --no-tests` skips PHPUnit). Needs [gitleaks](https://github.com/gitleaks/gitleaks/releases) and `semgrep` (`pip install semgrep==1.178.0`) on PATH or in `~/.local/bin`; tests need the `elibrarydb_test` database.
- **Never commit** `.env`, private keys or real password hashes. Seeded accounts get `SEED_USER_PASSWORD` from `.env`.

---

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 1500 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[OP.GG](https://op.gg)**
- **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
- **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).



DEVELOPERS NOTE:

## php.ini
memory_limit=2048M

max_execution_time=1200

max_input_time=-1

## uploading large dump file into db using shell
mysql -h localhost -u username databasename < dump.sql

C:\xampp\htdocs\elib_p_db.sql

## Restoring database incase Error: MySQL shutdown unexpectedly.
1. Rename folder mysql/data to mysql/data_old

2. Make a copy of mysql/backup folder and name it as mysql/data

3. Copy all your database folders from mysql/data_old into mysql/data (except mysql, performance_schema, and phpmyadmin folders)

4. Copy mysql/data_old/ibdata1 file into mysql/data folder

5. Start MySQL from XAMPP control panel