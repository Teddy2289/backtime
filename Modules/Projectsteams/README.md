# Projectsteams Module

## Structure DDD

```
Modules/Projectsteams/
├── Domain/           # Business logic
├── Application/      # Use cases & services
├── Infrastructure/   # External implementations
├── Presentation/     # Controllers, routes, views
├── Data/            # Migrations, seeders
├── Tests/           # Feature & unit tests
├── Providers/       # Service providers
└── Support/         # Helpers, traits
```

## Installation

1. Ajouter au `config/app.php` (section providers):
   ```php
   Modules\Projectsteams\Providers\ProjectsteamsServiceProvider::class,
   ```

2. Exécuter:
   ```bash
   composer dump-autoload
   ```

## Routes

- API: `GET /api/projectsteams`
- Web: `GET /projectsteams`

## Test

```bash
curl http://localhost:8000/api/projectsteams
```
