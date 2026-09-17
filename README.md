# AuthnAuth

A server-rendered authentication and role-based access control (RBAC) system built on Laravel. AuthnAuth handles login, password resets, forced first-login password changes, and a full admin user-management workflow with an ordinal role hierarchy, plus a versioned JSON API onto the same rules. The web app's own auth is hand-rolled (no Breeze, Jetstream, or Fortify); the API uses Laravel Sanctum for token auth only — see [API](#api).

[![CI](https://github.com/mothilal-jadhav/AuthnAuth/actions/workflows/ci.yml/badge.svg)](https://github.com/mothilal-jadhav/AuthnAuth/actions/workflows/ci.yml)
![PHP](https://img.shields.io/badge/PHP-%5E8.3-777bb4)
![Laravel](https://img.shields.io/badge/Laravel-%5E13.17-ff2d20)
![License](https://img.shields.io/badge/license-MIT-blue)

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Access Control Model](#access-control-model)
- [Requirements](#requirements)
- [Getting Started](#getting-started)
- [Provisioning an Admin Account](#provisioning-an-admin-account)
- [Running the App](#running-the-app)
- [Testing & Code Quality](#testing--code-quality)
- [Project Structure](#project-structure)
- [Routes](#routes)
- [API](#api)
- [Configuration Notes](#configuration-notes)
- [Continuous Integration](#continuous-integration)
- [License](#license)

## Overview

AuthnAuth is primarily a Blade, server-rendered monolith — plain forms and redirects, no SPA framework — with a separate, versioned JSON API (`/api/v1/*`) layered on top of the exact same authorization rules rather than a competing set. It's built around three things:

1. **Authentication** — login, "forgot password" / "reset password", and a forced password-change flow for accounts that were provisioned with a temporary password. (There's no self-service registration — every account is created by an admin/manager, or via `app:create-admin` for the first one — see [Provisioning an Admin Account](#provisioning-an-admin-account).)
2. **Authorization** — a hand-rolled role/permission system with an ordinal role hierarchy, so higher-privileged roles can manage lower-privileged ones without hardcoding every rule per-controller.
3. **API** — a token-authenticated (Sanctum) REST API reusing that same authorization layer with zero duplicated logic — see [API](#api).

## Features

- Email/password login with session-based auth (no self-service registration — accounts are admin/manager-created)
- Forgot/reset password flow using Laravel's built-in password broker
- Forced password change on first login for admin-provisioned accounts
- Role-based (`role:<name>`) and permission-based (`permission:<name>`) route middleware
- An ordinal role hierarchy (`Role::level`) enforced centrally in a single policy — higher-level roles can manage lower-level ones; a role can never manage or delete itself, and the last remaining admin can't be deleted
- Full user management UI: list/filter by role, create, edit, delete, with role-aware guardrails on who can assign which roles
- A `php artisan app:create-admin` command for bootstrapping the first admin account on a fresh install
- Named, per-route rate limiting on login, password reset, and API login
- Idempotent database seeders for roles and permissions — safe to re-run at any time
- A versioned JSON API (`/api/v1/*`) — Sanctum bearer-token auth, the same `UserPolicy`/permission rules as the web app, and auto-generated interactive OpenAPI docs (see [API](#api))

## Tech Stack

| Layer | Choice |
|---|---|
| Language | PHP ^8.3 |
| Framework | Laravel ^13.17 |
| Views | Blade (server-rendered, no SPA framework) |
| Frontend build | Vite + Tailwind CSS 4 |
| Database | MySQL (SQLite supported for local/dev/testing) |
| Testing | PHPUnit (`RefreshDatabase`), run via `php artisan test` |
| Static analysis | Larastan (PHPStan for Laravel), level 5 |
| Code style | Laravel Pint |
| API auth | Laravel Sanctum (bearer tokens) |
| API docs | [Scramble](https://scramble.dedoc.co) (auto-generated OpenAPI 3.1) |
| CI | GitHub Actions |

## Access Control Model

Authorization is split across two complementary layers:

- **Route-level gating** (`role:<name>` / `permission:<name>` middleware) — coarse-grained "can this user even reach this route" checks, backed by `User::hasRole()` / `User::hasPermission()`.
- **Management authorization** (`app/Policies/UserPolicy.php`) — fine-grained "who can create/edit/delete/assign a role to whom", based on an ordinal `Role::level` column (seeded as `admin = 100`, `manager = 50`, `user = 10`, with gaps left for inserting new tiers later). The rule: `admin` is the one special case that can manage anyone, including other admins; every other role can only manage roles at a strictly lower level; nobody can manage themselves; and the last remaining admin can never be deleted.

This keeps the hierarchy logic in one place instead of duplicated across controllers.

## Requirements

- PHP 8.3+
- Composer
- Node.js + npm (for the Vite/Tailwind asset build)
- A database — MySQL, or SQLite for a quick local setup

## Getting Started

```bash
git clone git@github.com:mothilal-jadhav/AuthnAuth.git
cd AuthnAuth

composer setup
```

`composer setup` runs the full bootstrap in one shot: installs PHP dependencies, copies `.env.example` to `.env` if it doesn't exist yet, generates the app key, runs migrations, installs npm dependencies, and builds frontend assets.

Then seed the roles and permissions tables (not run automatically by `composer setup`, and always safe to re-run):

```bash
php artisan db:seed
```

This creates the three default roles (`admin`, `manager`, `user`) and their associated permissions.

## Provisioning an Admin Account

There's no self-service way to become an admin — the first admin account is created via an Artisan command:

```bash
php artisan app:create-admin admin@example.com
```

- Requires the roles table to be seeded first (`php artisan db:seed`).
- Refuses to run in a `production` environment unless you pass `--force`.
- Omit `--password` to get a random 16-character password printed once to the console.
- The account is created with `must_change_password = true`, so the user is forced through the change-password flow on first login regardless of which password was used.

```bash
php artisan app:create-admin admin@example.com --password=SomeStrongPassword! --force
```

## Running the App

```bash
composer dev
```

This runs the dev server, queue listener, log viewer (Pail), and Vite together via `concurrently`. Visit `http://localhost:8000`.

## Testing & Code Quality

Run the same checks CI runs, in the same order:

```bash
composer format:check   # Laravel Pint — code style
composer analyse         # Larastan — static analysis
composer test              # PHPUnit — test suite
```

Use `composer format` (without `:check`) to auto-fix style issues.

Tests read database configuration from a local `.env.testing` (copy it from `.env.testing.example`); CI instead points directly at an in-memory SQLite database, so no file is needed there.

## Project Structure

```
app/
  Actions/                 Business logic shared by the web app and the API (e.g. CreateUser)
  Console/Commands/       Artisan commands (e.g. app:create-admin)
  Http/Controllers/Auth/  Login, ForgotPassword, ResetPassword, ChangePassword
  Http/Controllers/       Admin dashboard, user/department management, profile
  Http/Controllers/Api/V1/ API controllers — reuse the same UserPolicy/middleware, no parallel authz
  Http/Requests/Api/V1/    API-specific FormRequest validation
  Http/Resources/          API response shaping (deliberate field allow-lists)
  Http/Middleware/        Role/permission route guards, forced-password-change redirect
  Models/                 User, Role, Permission, Department
  Policies/                UserPolicy — the role-hierarchy management rules (shared by web + API)
  Providers/               Policy + rate limiter registration

database/
  migrations/              Users, roles, permissions, and their pivot/foreign-key wiring
  seeders/                 Idempotent Role/Permission/RolePermission seeders

resources/views/           Blade templates (no SPA framework)
routes/web.php             Web application routes
routes/api.php             Versioned JSON API routes (/api/v1/*)
config/sanctum.php          API token auth configuration
config/scramble.php         API documentation generator configuration
tests/                     PHPUnit feature and unit tests (tests/Feature/Api/V1/ for the API)
```

## Routes

| Purpose | Method / Path | Middleware |
|---|---|---|
| Login | `GET/POST /login` | `throttle:login` on submit |
| Logout | `POST /logout` | `auth` |
| Dashboard | `GET /dashboard` | `auth` |
| Forgot / reset password | `GET/POST /forgot-password`, `GET/POST /reset-password/{token}` | `guest`, `throttle:password-reset` |
| Change password | `GET/POST /password/change` | `auth` |
| Admin panel | `GET /admin` | `auth`, `role:admin` |
| User management | `GET/POST/PUT/DELETE /users*` | `auth`, `permission:users.view\|create\|update\|delete` |
| Profile | `GET /profile` | `auth` |

## API

AuthnAuth also exposes a versioned, token-authenticated JSON API alongside the Blade web app — same database, same `UserPolicy`/RBAC rules, zero duplicated authorization logic. It runs on the same app/process as the web app (no separate server to start) — `composer dev`/`php artisan serve` serves both.

### Architecture

- **Base path**: `/api/v1/*`, registered in `bootstrap/app.php`, routes defined in `routes/api.php`.
- **Controllers**: `app/Http/Controllers/Api/V1/` (`AuthController`, `UserController`, `RoleController`, `PermissionController`) — thin, and reuse the exact same `UserPolicy`/`Gate::authorize()`/`permission:<name>` middleware the web controllers use. There is no parallel authorization system for the API.
- **Validation**: `app/Http/Requests/Api/V1/` — dedicated FormRequest classes, reusing the shared `HasNameEmailRules` trait where the rules are identical to the web forms.
- **Responses**: `app/Http/Resources/` — every response is a deliberate field allow-list (`UserResource`, `RoleResource`, `PermissionResource`); `password`/`remember_token` are never serialized.
- **Shared business logic**: `app/Actions/CreateUser.php` — "create a user with a system-generated temporary password" is used by both the web admin-create-user form and the API, so that rule lives in exactly one place.

### Authentication

Stateless bearer-token auth via [Laravel Sanctum](https://laravel.com/docs/sanctum) personal access tokens — no cookies, no CSRF, no shared session with the web app.

1. `POST /api/v1/auth/login` with an email/password to receive a token.
2. Send it as `Authorization: Bearer <token>` on every other request.
3. `POST /api/v1/auth/logout` revokes the current token (only that one — other active tokens/devices are untouched).

A few deliberate security properties, not just defaults:

- Tokens expire after 30 days (`SANCTUM_TOKEN_EXPIRATION_MINUTES` in `.env`) rather than never.
- Any password change — forced first-login, self-service, or forgot-password reset — revokes **all** of that user's existing tokens.
- An account provisioned with a temporary password (`must_change_password = true`) can't obtain a token at all until the password is changed via the web app (`423 Locked`).

### Endpoints

| Purpose | Method / Path | Auth | Notes |
|---|---|---|---|
| Log in | `POST /api/v1/auth/login` | — | 6 requests/minute per IP |
| Log out | `POST /api/v1/auth/logout` | Bearer | |
| Current user | `GET /api/v1/auth/me` | Bearer | |
| List users | `GET /api/v1/users` | Bearer + `users.view` | Paginated; filter by `role`/`name`/`email`/`status`; sort by a whitelisted column |
| Create user | `POST /api/v1/users` | Bearer + `users.create` | Same `UserPolicy::create()` hierarchy rules as the web form |
| Get user | `GET /api/v1/users/{user}` | Bearer + `users.view` | |
| Update user | `PUT /api/v1/users/{user}` | Bearer + `users.update` | Also syncs functional roles if `functional_role_ids` is present in the body |
| Delete user | `DELETE /api/v1/users/{user}` | Bearer + `users.delete` | Soft delete |
| List trashed users | `GET /api/v1/users/trashed` | Bearer + `users.restore` | |
| Restore user | `POST /api/v1/users/{id}/restore` | Bearer + `users.restore` | |
| List roles | `GET /api/v1/roles` | Bearer + `roles.view` | Hierarchy roles (admin/manager/user) + functional roles (e.g. Payroll Officer) |
| Get role | `GET /api/v1/roles/{role}` | Bearer + `roles.view` | |
| List permissions | `GET /api/v1/permissions` | Bearer + `permissions.view` | |
| Get permission | `GET /api/v1/permissions/{permission}` | Bearer + `permissions.view` | |

Every permission-gated endpoint enforces the exact same `UserPolicy` ordinal-hierarchy rules as the web app — self-management block, strictly-higher-level requirement, last-admin protection — see [Access Control Model](#access-control-model).

### Interactive API documentation

Full OpenAPI 3.1 documentation — every request/response schema, validation rule, and auth requirement, generated from the actual code so it can't drift out of sync — is available at:

- `/docs/api` — interactive UI (try requests directly from the browser)
- `/docs/api.json` — raw OpenAPI spec

Only reachable in the `local` environment by default (or when an explicit `viewApiDocs` gate allows it elsewhere) — it's not meant to be publicly exposed as-is.

### Example request/response

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "admin@example.com", "password": "password"}'
```

```json
{
    "data": {
        "id": 1,
        "name": "Admin",
        "email": "admin@example.com",
        "must_change_password": false,
        "role": { "id": 1, "name": "admin", "type": "hierarchy", "level": 100 },
        "department": null,
        "functional_roles": [],
        "created_at": "2026-09-16T10:58:03+00:00",
        "updated_at": "2026-09-16T10:58:12+00:00"
    },
    "meta": {
        "token": "1|abcdef0123456789..."
    }
}
```

Then use the token on any protected endpoint:

```bash
curl http://localhost:8000/api/v1/users \
  -H "Authorization: Bearer 1|abcdef0123456789..." \
  -H "Accept: application/json"
```

Errors follow a predictable shape — `{"message": "..."}`, with an added `"errors"` object for `422` validation failures:

```json
{
    "message": "The email field is required. (and 2 more errors)",
    "errors": {
        "email": ["The email field is required."],
        "name": ["The name field is required."],
        "role_id": ["The role id field is required."]
    }
}
```

### Testing the API

API tests live in `tests/Feature/Api/V1/` and run as part of the normal suite — no separate command:

```bash
composer test
```

They deliberately avoid Laravel's `actingAs()` test helper for anything auth-related (it sets the user directly on the session guard in-process, which can mask real bearer-token bugs — see the comment at the top of `tests/Feature/Api/V1/AuthControllerTest.php`); auth-flow tests go through a real `POST /api/v1/auth/login` and a real `Authorization` header instead.

### Extending the API

See [API.md](API.md) for conventions, a walkthrough of adding a new endpoint, and a few non-obvious gotchas (Sanctum's session-guard fallback, a test-guard-caching pitfall) worth knowing before you dig in.

## Configuration Notes

- **Mail**: `.env` defaults to `MAIL_MAILER=log`, which is dev-only — password-reset emails are written to the log file instead of being delivered. Set a real mail transport (SES, SMTP, Postmark, etc.) before relying on password resets in any non-local environment.
- **Sessions/queue/cache**: default to the `database` driver; make sure migrations have run before relying on them.
- **API tokens**: `SANCTUM_TOKEN_EXPIRATION_MINUTES` (defaults to 43200, i.e. 30 days) controls how long an API bearer token stays valid — see [API](#api).
- **CORS**: not currently published as an explicit `config/cors.php`, so Laravel's framework default (`allowed_origins: ['*']` for `api/*`) is in effect. Confirm this is actually the intended policy before exposing the API beyond local development.

## Continuous Integration

Every push and pull request against `main` runs via GitHub Actions (`.github/workflows/ci.yml`): install dependencies against an in-memory SQLite database, then `composer format:check` → `composer analyse` → `composer test`, in that order. `composer test` covers the API test suite too — no separate CI step needed.

## License

This project is licensed under the MIT License, as declared in `composer.json`.
