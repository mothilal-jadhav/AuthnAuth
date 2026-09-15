# AuthnAuth

A server-rendered authentication and role-based access control (RBAC) system built on Laravel. AuthnAuth handles user registration, login, password resets, forced first-login password changes, and a full admin user-management workflow with an ordinal role hierarchy — all with no external auth package (no Breeze, Jetstream, Fortify, or Sanctum).

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
- [Configuration Notes](#configuration-notes)
- [Continuous Integration](#continuous-integration)
- [License](#license)

## Overview

AuthnAuth is a Blade-only, server-rendered monolith — there's no SPA framework and no JSON API surface, just plain forms and redirects. It's built around two things:

1. **Authentication** — registration, login, "forgot password" / "reset password", and a forced password-change flow for accounts that were provisioned with a temporary password.
2. **Authorization** — a hand-rolled role/permission system with an ordinal role hierarchy, so higher-privileged roles can manage lower-privileged ones without hardcoding every rule per-controller.

## Features

- Email/password registration and login with session-based auth
- Forgot/reset password flow using Laravel's built-in password broker
- Forced password change on first login for admin-provisioned accounts
- Role-based (`role:<name>`) and permission-based (`permission:<name>`) route middleware
- An ordinal role hierarchy (`Role::level`) enforced centrally in a single policy — higher-level roles can manage lower-level ones; a role can never manage or delete itself, and the last remaining admin can't be deleted
- Full user management UI: list/filter by role, create, edit, delete, with role-aware guardrails on who can assign which roles
- A `php artisan app:create-admin` command for bootstrapping the first admin account on a fresh install
- Named, per-route rate limiting on login, registration, and password reset
- Idempotent database seeders for roles and permissions — safe to re-run at any time

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
  Console/Commands/       Artisan commands (e.g. app:create-admin)
  Http/Controllers/Auth/  Login, Register, ForgotPassword, ResetPassword, ChangePassword
  Http/Controllers/       Admin dashboard, user management
  Http/Middleware/        Role/permission route guards, forced-password-change redirect
  Models/                 User, Role, Permission
  Policies/                UserPolicy — the role-hierarchy management rules
  Providers/               Policy + rate limiter registration

database/
  migrations/              Users, roles, permissions, and their pivot/foreign-key wiring
  seeders/                 Idempotent Role/Permission/RolePermission seeders

resources/views/           Blade templates (no SPA framework)
routes/web.php             All application routes
tests/                     PHPUnit feature and unit tests
```

## Routes

| Purpose | Method / Path | Middleware |
|---|---|---|
| Register | `GET/POST /register` | `throttle:register` on submit |
| Login | `GET/POST /login` | `throttle:login` on submit |
| Logout | `POST /logout` | `auth` |
| Dashboard | `GET /dashboard` | `auth` |
| Forgot / reset password | `GET/POST /forgot-password`, `GET/POST /reset-password/{token}` | `guest`, `throttle:password-reset` |
| Change password | `GET/POST /password/change` | `auth` |
| Admin panel | `GET /admin` | `auth`, `role:admin` |
| User management | `GET/POST/PUT/DELETE /users*` | `auth`, `permission:users.view\|create\|update\|delete` |
| Profile | `GET /profile` | `auth` |

## Configuration Notes

- **Mail**: `.env` defaults to `MAIL_MAILER=log`, which is dev-only — password-reset emails are written to the log file instead of being delivered. Set a real mail transport (SES, SMTP, Postmark, etc.) before relying on password resets in any non-local environment.
- **Sessions/queue/cache**: default to the `database` driver; make sure migrations have run before relying on them.

## Continuous Integration

Every push and pull request against `main` runs via GitHub Actions (`.github/workflows/ci.yml`): install dependencies against an in-memory SQLite database, then `composer format:check` → `composer analyse` → `composer test`, in that order.

## License

This project is licensed under the MIT License, as declared in `composer.json`.
