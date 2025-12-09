# Taskfiles Module

## Structure DDD

```
Modules/Taskfiles/
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
   Modules\Taskfiles\Providers\TaskfilesServiceProvider::class,
   ```

2. Exécuter:
   ```bash
   composer dump-autoload
   ```

## Routes

- API: `GET /api/taskfiles`
- Web: `GET /taskfiles`

## Test

```bash
curl http://localhost:8000/api/taskfiles
```
