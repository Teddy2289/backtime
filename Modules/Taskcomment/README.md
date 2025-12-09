# Taskcomment Module

## Structure DDD

```
Modules/Taskcomment/
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
   Modules\Taskcomment\Providers\TaskcommentServiceProvider::class,
   ```

2. Exécuter:
   ```bash
   composer dump-autoload
   ```

## Routes

- API: `GET /api/taskcomment`
- Web: `GET /taskcomment`

## Test

```bash
curl http://localhost:8000/api/taskcomment
```
