# TheaterLink Commands

Common local commands for the TheaterLink backend.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

## Development

```bash
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
php artisan pail
```

## Database

```bash
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed
php artisan db:seed --class="Modules\Tenancy\Database\Seeders\TenancyDatabaseSeeder"
```

## Tests and Static Analysis

```bash
php artisan test
# Tests use the theaterlink_testing MySQL database.
php artisan test --filter=TenantAuthTest
php artisan test --filter=TenantTeamTest
vendor/bin/phpstan analyse app/Services/Tenancy app/Traits/BelongsToTenant.php Modules/Tenancy/app Modules/Tenancy/routes --memory-limit=1G
```

## Modules

```bash
php artisan module:list
php artisan module:make Tenancy
```

## Routes

```bash
php artisan route:list --path=api/v1/tenant
```

## Documentation

The full phase plan is in [`README.md`](README.md). The architecture analysis is in [`phase-1-technical-analysis.md`](phase-1-technical-analysis.md).
