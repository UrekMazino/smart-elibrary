# API Integration — Plan

**Status:** Not started — options and a recommended first version, written 2026-10-21 before an
integration with another web app. Which option (or mix) depends on the open questions in section 5.

## 1. What exists today

- **Laravel Sanctum** is installed: the `personal_access_tokens` table exists and `User` has
  `HasApiTokens`. Tokens never expire yet (`config/sanctum.php` `expiration` is `null`).
- **`routes/api.php`** holds only Laravel's sample `GET /api/user`.
- **CORS** allows any origin on `api/*` (`config/cors.php` `allowed_origins: ['*']`): tighten before
  anything is exposed.
- **JSON endpoints for the OPAC's own pages** (`/search/suggest`, `/search/facet`, `/search/cite/{id}`,
  `/search/quick/{id}`, `/search/ai-answer`): built for the site, not a contract for other apps.
- **Reusable pieces:** `App\Services\CatalogSearch` (Meilisearch search with filters and counts),
  `buildCitations()` (APA / MLA / Chicago), MARC21 / MARCXML export (`exportRecord`), the
  published-and-not-deleted rule every public page applies, full texts in private storage
  (`fullTextPath()`), and the outbox pattern of the AI index sync (`rag_sync_queue`).

## 2. Options

| | A. REST API with tokens | B. OAuth2 / sign-in across apps | C. Library-standard protocols | D. Webhooks |
|---|---|---|---|---|
| What it is | `/api/v1/...` JSON; the other app's server sends a secret token | The other app signs users in with their eLibrary account and acts for them | OAI-PMH (harvesting) and/or SRU (search), MARCXML / Dublin Core | The eLibrary sends a signed message when something changes |
| Best when | One known partner app reads the catalogue, maybe creates reservations | Patrons see their own loans and reservations inside the other app | The other system is a library system (Koha, a union catalogue, a discovery service) | The other app must react quickly to changes without polling |
| Built with | Sanctum (installed) | Laravel Passport (new) | Hand-built XML endpoints on the MARCXML export | The AI-sync outbox pattern, generalised |
| Effort | Small to medium | Large | Medium | Medium |
| Main cost | A custom contract to maintain | Clients, keys and consent screens to secure | Read-only metadata; XML | Retries and failed deliveries to handle |

They combine rather than compete: **A** is the core almost any integration needs, **D** extends A,
**C** is a separate public door for library systems, **B** only matters if users must sign in across
apps. GraphQL was considered and left out: one or two known partners don't need its flexibility.

## 3. Recommendation: A first, designed so D can follow

### 3.1 Version 1 (read-only)

- **API clients, not people, hold tokens.** A dedicated API-client account or table (not a staff or
  patron login) per partner app. Token abilities: `catalog:read`, `holdings:read`, and later
  `fulltext:read`, `reservations:write`. An admin page issues a token (shown once), revokes it, and
  shows when it was last used.
- **Endpoints** under `/api/v1`:

  | Method | Path | Returns |
  |---|---|---|
  | GET | `/records?q=&type=&year=&page=` | Search results (CatalogSearch), paginated |
  | GET | `/records/{id}` | One published record |
  | GET | `/records/{id}/citation?style=apa\|mla\|chicago` | The citation (`buildCitations()`) |
  | GET | `/records/{id}/copies` | Copies and availability |
  | GET | `/material-types` | Material types for filters |
  | GET | `/records/{id}.marcxml` | MARCXML (optional) |

- **Published records only**, the same rule as the OPAC: held and deleted records answer 404.
- **Full texts never through the API.** With `fulltext:read`, the API returns a signed link to the
  PDF that expires after 5 minutes (`URL::temporarySignedRoute`), served from private storage.
- **One response shape:** Laravel API Resources, `{data, meta, links}`; errors as
  `{error: {code, message}}`; page-based pagination in `meta`.
- **Security:** token expiry (about a year), a rate limit per token (about 120 requests a minute),
  HTTPS only, CORS limited to the partner's origin (only needed if their browser calls the API), no
  patron personal data in v1.
- **Documentation and tests:** an OpenAPI description generated from the code (for example
  dedoc/scramble; check Laravel 13 support) and a feature test per endpoint.

### 3.2 Later

- **Writes (A v2):** `POST /reservations` with `reservations:write`, once there is a rule for how the
  partner identifies a patron (library code plus consent).
- **Webhooks (D):** `record.published`, `record.updated`, `reservation.status_changed`; an outbox
  table, HMAC-signed payloads, retries with back-off, delivered by the scheduler.
- **OAI-PMH (C):** if Koha (on the same server today) or a union catalogue should harvest the
  catalogue.
- **OAuth2 (B):** only if users must sign in to the other app with eLibrary accounts.

## 4. Before any of it

- Restrict CORS and set a Sanctum token expiry.
- Decide where API clients live (a `User` with a dedicated role, or an `ApiClient` model with
  `HasApiTokens`) and who may issue tokens (a permission such as `manage.api`).
- Version from day one (`/api/v1`) and keep the response shape stable within a version.

## 5. Open questions

1. What will the other app do: only show the catalogue, or also create things (reservations, patrons)?
2. Who calls: the other app's server, or its users' browsers?
3. Should its users sign in with eLibrary accounts?
4. Which app is it: Koha, a DOST system, or something new?
