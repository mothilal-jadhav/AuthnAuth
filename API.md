# API Development Guide

This is for developers **extending** AuthnAuth's API. If you just want to
*call* the API, see the [README's API section](README.md#api) and the live
docs at `/docs/api` — this guide is about how the API layer is built, so a
new endpoint follows the same conventions as the existing ones.

## The one rule that matters

**Never write a new authorization check for the API.** Every rule about who
can do what to whom already lives in `app/Policies/UserPolicy.php` (ordinal
hierarchy) and the `permission:<name>` / `role:<name>` middleware (route
gating), shared verbatim with the web app. An API controller's job is to
call the same `Gate::authorize()` / `$this->authorize()` / `->can()` the web
controllers call — never to re-derive "is this actor allowed to do this."
If you find yourself writing an `if ($actor->role->level > ...)` check
inside an API controller, stop — that logic belongs in `UserPolicy`, reused
by both interfaces.

## Layout

```
routes/api.php                       Route::prefix('v1') group, flat style (no Route::resource)
app/Http/Controllers/Api/V1/         One controller per resource
app/Http/Requests/Api/V1/            One FormRequest per mutating/filtering endpoint
app/Http/Resources/                  Shared between... nothing else yet — API-only for now
app/Actions/                         Business logic genuinely shared with the web app
```

`app/Http/Resources/` isn't namespaced under `Api/V1/` — if a second API
version is ever needed, version the Resources too at that point rather than
guessing at the shape now.

## Adding a new endpoint

1. **Route** in `routes/api.php`, inside the existing `auth:sanctum` +
   `throttle:api` group. Add `permission:<name>` (or `role:<name>`)
   middleware — every endpoint should be gated by something, even a
   read-only one. If the route takes a path parameter that needs implicit
   model binding, remember trashed-record routes (see `users/trashed`,
   `users/{id}/restore`) can't use implicit binding — order matters (a
   literal path segment like `/trashed` must be registered before a
   `{wildcard}` route with the same method and segment count, or the
   wildcard swallows it).
2. **FormRequest** in `app/Http/Requests/Api/V1/`. `authorize()` should call
   `$this->user()->can(...)` for anything needing the ordinal hierarchy
   (mirroring the equivalent web FormRequest if one exists — reuse
   `HasNameEmailRules` and other shared concerns rather than re-deriving
   rules). Validate `sort`/filter inputs against an explicit whitelist
   (`Rule::in(...)`) before they can reach a raw `orderBy()`/`where()` —
   see `IndexUserRequest` for the pattern.
3. **Controller method**: thin. Validate via the FormRequest, authorize via
   policy/`$this->authorize()` for anything not already covered by route
   middleware or the FormRequest's `authorize()`, do the work, return a
   Resource (or `Resource::collection()` for a list). Never `fill()`/mass-
   assign fields that aren't in the model's `#[Fillable(...)]` list (see
   `User` — `role_id`/`department_id`/`must_change_password` are set via
   explicit property assignment everywhere, never `fill()`).
4. **Resource**: add fields to the allow-list explicitly. Never let a
   Resource fall through to the default full-model serialization — every
   field returned should be a deliberate choice, and secrets
   (`password`, `remember_token`, tokens after their one-time issuance)
   never belong in one.
5. **Docs**: a one-line PHPDoc summary + a short description on the
   controller method (Scramble reads this directly — first line becomes
   the sidebar title, rest becomes the expanded description). Add
   `#[Dedoc\Scramble\Attributes\Response(status, description: '...')]`
   for any response Scramble can't infer on its own — check
   `php artisan scramble:analyze` and inspect `/docs/api.json` before
   assuming it got picked up automatically. It reliably infers `401` (from
   `auth`/`auth:*` middleware), `403` (from a FormRequest's non-`true`
   `authorize()`), `404` (from implicit route-model binding), and `422`
   (from a FormRequest with `rules()`/`after()`) — it does **not** infer
   custom JSON error responses your controller returns directly (like the
   `423` on login), inline `$this->authorize()` calls with no FormRequest
   backing them (like `destroy()`/`restore()`'s `403`s), or a plain
   `int $id` route parameter's `404` (only implicit *model* binding is
   auto-detected).
6. **Test** in `tests/Feature/Api/V1/`. Match the existing per-file
   `actorWithPermissions()`/helper-method convention — no shared test
   trait. For anything auth-related, don't use `actingAs()`; it sets the
   user directly on the `web` guard in-process, and Sanctum's `auth:sanctum`
   guard checks that guard *before* the bearer token (see "Gotchas" below)
   — so an `actingAs()`-based test can pass even when the real HTTP
   bearer-token flow is broken. Go through a real `POST /api/v1/auth/login`
   and a real `Authorization` header instead, as `AuthControllerTest` does.
7. Run `composer format:check && composer analyse && composer test` before
   calling it done — same gates as the web app, no separate API pipeline.

## Response shape

Success: `{"data": {...}}` for a single resource, `{"data": [...], "links": {...}, "meta": {...}}`
for a paginated collection (Laravel's default `Resource::collection()` +
paginator behavior — don't hand-roll this). Small, unpaginated catalogs
(Roles, Permissions) return `{"data": [...]}` with no `meta`/`links` —
that's intentional, not a bug to "fix" into matching Users.

Errors: Laravel's default `{"message": "..."}` (`+ "errors": {...}` for
`422`). Don't invent a different error envelope for the API — see
`bootstrap/app.php`'s `withExceptions()` if you need to adjust what a
specific exception renders as (e.g. the `ModelNotFoundException` message
rewrite already there, to avoid leaking the internal model class name).

## Gotchas (things that will cost you an hour if you don't know them)

- **`auth:sanctum` is not purely bearer-token-based.** Sanctum's guard
  checks the `web` session guard *first* (`Laravel\Sanctum\Guard::__invoke()`),
  falling back to the bearer token only if that's empty. This is dormant in
  this app only because `routes/api.php` sits in the stateless `api`
  middleware group (no `StartSession`) — if anyone ever adds session
  middleware there, this reactivates silently. Don't assume "authenticated
  on `/api/*`" strictly means "presented a valid token."
- **`RequestGuard`/Sanctum's guard caches its resolved user for the guard
  instance's lifetime**, which — unlike a real request — persists across
  multiple simulated requests within one PHPUnit test method. If a test
  makes two authenticated calls with different expected outcomes (e.g.
  "valid, then revoked"), call `$this->app['auth']->forgetGuards();`
  between them or the second call will silently reuse the first's cached
  resolution. See `AuthControllerTest`'s logout/expiration tests.
- **Password changes must revoke tokens.** If you add a fourth place a
  password can change, call `$user->tokens()->delete()` there too — three
  existing call sites do this deliberately (`ChangePasswordController`,
  `ProfileController::updatePassword`, `ResetPasswordController`); a
  stolen token shouldn't survive a password change.
- **Faker-generated test names can fail validation.** `HasNameEmailRules`
  requires letters/spaces only; `UserFactory` already strips anything else
  from Faker's output, so this shouldn't recur — but if you generate names
  another way in a new test, don't reuse them raw in a request payload
  without the same care.
