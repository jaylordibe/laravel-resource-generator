# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Composer library (`jaylordibe/laravel-resource-generator`) that adds one Artisan command to a host Laravel app. It is **not** a Laravel app itself — there is no `app/`, no `routes/`, no `phpunit.xml`, and no test suite here. Everything under `src/Stubs/` is a template for code that will live in a *consumer* project.

## Commands

Development runs inside Docker (image `jaylordibe/nginx-php`, container `laravel-resource-generator`):

```bash
./start.sh                      # docker compose down + up + composer update
./stop.sh                       # docker compose down
docker exec -it laravel-resource-generator bash -c "composer validate"
docker exec -it laravel-resource-generator bash -c "composer dump-autoload"
```

There is no lint/test target — `phpunit/phpunit` is a dev dependency but no tests exist. Verification is done by installing the package in a consumer Laravel app and running:

```bash
php artisan app:generate-resource User
```

## Branching

Branches track Laravel major versions: `laravel-11.x`, `laravel-12.x` (current), `main`. Changes to stubs/command usually need porting across the version branches; keep `composer.json` `illuminate/*` constraints aligned with the branch name.

## Architecture

Three files do all the work:

- `src/Providers/LaravelResourceGeneratorServiceProvider.php` — auto-discovered via `extra.laravel.providers`; registers the command only when `runningInConsole()`.
- `src/Commands/LaravelResourceGeneratorCommand.php` — the entire generator.
- `src/Stubs/*.stub` — 12 templates.

### Generation flow (`handle()`)

`app/Models/{Model}.php` existing is the gate for the "first time" artifacts: if the model does **not** exist, the command also creates the model, the migration (and appends a constant to `app/Constants/DatabaseTableConstant.php`), and appends routes to `routes/api.php`. The remaining artifacts are always attempted:

| Stub | Destination |
|---|---|
| `Model` / `Migration` | `app/Models/`, `database/migrations/` (gated) |
| `Factory` | `database/factories/{Model}Factory.php` |
| `Request` | `app/Http/Requests/{Model}Request.php` |
| `Resource` | `app/Http/Resources/{Model}Resource.php` |
| `Data`, `FilterData` | `app/Data/{Model}Data.php`, `{Model}FilterData.php` |
| `UnitTest`, `FeatureTest` | `tests/Unit/`, `tests/Feature/` |
| `Controller` / `Service` / `Repository` | `app/Http/Controllers/`, `app/Services/`, `app/Repositories/` |

Every writer is idempotent by the same pattern: `File::exists($path)` → print an error and skip, else write. Nothing is ever overwritten.

### Token substitution (`getStubFile()`)

Stubs use `{{modelName}}`-style placeholders resolved by a parallel `$search`/`$replace` pair of arrays. **When adding a placeholder you must append to both arrays at the same index** — they are positional, not keyed. Available tokens: `modelName`, `modelNamePlural`, `modelNameCamelCase(Plural)`, `modelNameKebabCase(Plural)`, `modelNameUpperKebabCasePlural`, `modelNameSnakeCase(Plural)`, `modelNameUpperSnakeCasePlural`, `modelNameSpaceCase(Plural)`, `modelNameUpperWordSpaceCase`, `modelNameUpperFirstSpaceCase`, `modelNameId`.

### Host-file mutation (fragile by design)

Two methods edit files in the consumer project by raw string offset rather than parsing:

- `addRoute()` — inserts a `use App\Http\Controllers\{Model}Controller;` after the *last* `use ` line, then inserts a route group before the **last** `});` in `routes/api.php` (i.e. it assumes the API routes are wrapped in a middleware group closure).
- `createMigrationFile()` — inserts `const string X = '...';` before the last `}` in `app/Constants/DatabaseTableConstant.php` (that file must already exist; there is no guard).

If you touch these, preserve the `substr_replace` offset arithmetic assumptions or the insert silently lands in the wrong place.

### Conventions the stubs impose on the consumer app

The generated code will not compile unless the host project already provides these. Treat them as the package's implicit contract:

- Base classes: `App\Models\BaseModel`, `App\Data\BaseData`, `App\Http\Requests\BaseRequest`, `App\Http\Resources\BaseResource`.
- Support types: `App\Http\Requests\GenericRequest` (with `getRelations()`, `getAuthUserData()`, `getMetaData()`), `App\Utils\ResponseUtil` (`resource()`, `success()`), `App\Exceptions\BadRequestException`, `App\Constants\DatabaseTableConstant` (including a `USERS` constant).
- Config key `custom.numeric_regex`, used as the route `where()` constraint on `{modelId}` params.
- Layering: Controller → Service (throws `BadRequestException` on empty results) → Repository (all Eloquent access, filters applied via `applyFilters()` reading `$filterData->meta->{relations,columns,sortField,sortDirection,perPage}`).
- Migrations use a fixed audit shape: `id`, `created_at`/`updated_at`/`deleted_at`, and `created_by`/`updated_by`/`deleted_by` FKs to `DatabaseTableConstant::USERS`.
- Feature tests assume `Tests\TestCase::loginSystemAdminUser()` returning a bearer token, and REST routes at `/api/{kebab-plural}`.

### Adding a new generated artifact

1. Add `src/Stubs/{Name}.stub` using existing tokens.
2. Add a `create{Name}File(string $modelName)` method following the exists-check/write/info pattern.
3. Call it from `handle()` in the correct order (gated vs. always-run).

## Style

`.editorconfig` is authoritative: 4-space indent, LF, final newline. PHP files follow the JetBrains rules encoded there — one blank line after the class header and before the closing brace, one blank line before `return`, blank lines around `@param` blocks in docblocks. Every method carries a full docblock with `@param`/`@return`; match that.
