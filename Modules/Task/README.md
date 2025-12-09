# Task Module

## Structure DDD

```
Modules/Task/
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
   Modules\Task\Providers\TaskServiceProvider::class,
   ```

2. Exécuter:
   ```bash
   composer dump-autoload
   ```

## Routes

- API: `GET /api/task`
- Web: `GET /task`

## Test

```bash
curl http://localhost:8000/api/task
```
