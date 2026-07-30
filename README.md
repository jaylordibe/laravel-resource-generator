# Laravel Resource Generator

An Artisan command that scaffolds a complete CRUD resource across a strict layered architecture —
model, migration, factory, request, data objects, resource, controller, service, repository, tests,
and routes — in a single step.

> This package generates code for a **specific layered API architecture**. The stubs assume base
> classes such as `BaseModel`, `BaseRequest`, `BaseData`, a `DatabaseTableConstant`, a `MetaData`
> query envelope, and a `BadRequestException` service convention. It is intended for projects built
> on that template rather than as a general-purpose scaffolder.

## Compatibility

| Package | Laravel | Branch |
|---------|---------|--------|
| `^3.0`  | 13.x    | `laravel-13.x` |
| `^2.0`  | 12.x    | `laravel-12.x` |
| `^1.0`  | 11.x    | `laravel-11.x` |

Requires PHP 8.3+. Each major line targets a single Laravel major; older branches are frozen at the
cut and are not backported.

## Installation

```bash
composer require jaylordibe/laravel-resource-generator
```

The service provider is auto-discovered; the command registers itself when running in console.

## Usage

```bash
php artisan app:generate-resource AppVersion
```

The model name must be StudlyCase and alphanumeric. It is used to build both class names and file
paths, so anything else is rejected.

### What it generates

| Layer | Path |
|-------|------|
| Model | `app/Models/{Model}.php` |
| Migration | `database/migrations/{timestamp}_create_{table}_table.php` |
| Factory | `database/factories/{Model}Factory.php` |
| Request | `app/Http/Requests/{Model}Request.php` |
| Resource | `app/Http/Resources/{Model}Resource.php` |
| Data | `app/Data/{Model}Data.php` |
| Filter data | `app/Data/{Model}FilterData.php` |
| Controller | `app/Http/Controllers/{Model}Controller.php` |
| Service | `app/Services/{Model}Service.php` |
| Repository | `app/Repositories/{Model}Repository.php` |
| Unit test | `tests/Unit/{Model}UnitTest.php` |
| Feature test | `tests/Feature/{Model}FeatureTest.php` |

It also registers the table name on `app/Constants/DatabaseTableConstant.php` and appends the CRUD
routes to `routes/api.php`.

### Re-running

The command is safe to re-run. Existing files are skipped, and the table constant and route block
are only added when they are not already present, so a second run tops up any missing layer without
duplicating anything.

Exit code is `0` on success and `1` when generation fails, so it is safe to use in scripts.

## Development

```bash
docker compose up -d
docker exec -it laravel-resource-generator bash -c "composer update"
docker exec -it laravel-resource-generator bash -c "vendor/bin/phpunit"
```

## License

MIT. See [LICENSE](LICENSE).
