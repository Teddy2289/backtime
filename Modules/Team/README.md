# Team Module

## Structure DDD

```
Modules/Team/
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
   Modules\Team\Providers\TeamServiceProvider::class,
   ```

2. Exécuter:
   ```bash
   composer dump-autoload
   ```

## Routes

- API: `GET /api/team`
- Web: `GET /team`

## Test

```bash
curl http://localhost:8000/api/team
```
