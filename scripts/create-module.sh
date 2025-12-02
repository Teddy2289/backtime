#!/bin/bash

MODULE_NAME=$1
MODULE_NAME_LOWER=$(echo "$MODULE_NAME" | tr '[:upper:]' '[:lower:]')
MODULE_NAME_CAPITALIZED=$(echo "$MODULE_NAME" | awk '{print toupper(substr($0,1,1)) tolower(substr($0,2))}')

echo "🚀 Création manuelle du module: $MODULE_NAME_CAPITALIZED"

# Vérifier si le module existe déjà
if [ -d "Modules/$MODULE_NAME_CAPITALIZED" ]; then
    echo "❌ Le module $MODULE_NAME_CAPITALIZED existe déjà!"
    exit 1
fi

# 1. Créer la structure DDD COMPLÈTE (incluant Config)
echo "📁 Création de la structure DDD..."
mkdir -p Modules/$MODULE_NAME_CAPITALIZED/{Domain,Application,Infrastructure,Presentation,Data,Providers,Policies,Tests,Support,Config}
mkdir -p Modules/$MODULE_NAME_CAPITALIZED/Domain/{Entities,Enums,Interfaces,Events,Exceptions,ValueObjects}
mkdir -p Modules/$MODULE_NAME_CAPITALIZED/Application/{Services,DTOs,UseCases,Validators,Interfaces}
mkdir -p Modules/$MODULE_NAME_CAPITALIZED/Infrastructure/{Repositories,Providers,Services}
mkdir -p Modules/$MODULE_NAME_CAPITALIZED/Presentation/{Controllers,Requests,Resources,Routes,Middleware,Filters,Views}
mkdir -p Modules/$MODULE_NAME_CAPITALIZED/Data/{Migrations,Seeders,Factories}
mkdir -p Modules/$MODULE_NAME_CAPITALIZED/Tests/{Feature,Unit}
mkdir -p Modules/$MODULE_NAME_CAPITALIZED/Support/{Traits,Helpers,Constants}

# 2. Créer module.json avec plus d'options
cat > Modules/$MODULE_NAME_CAPITALIZED/module.json << EOF
{
    "name": "$MODULE_NAME_CAPITALIZED",
    "alias": "$MODULE_NAME_LOWER",
    "description": "$MODULE_NAME_CAPITALIZED management module",
    "version": "1.0.0",
    "keywords": ["$MODULE_NAME_LOWER", "ddd", "module"],
    "priority": 1000,
    "providers": [
        "Modules\\\\$MODULE_NAME_CAPITALIZED\\\\Providers\\\\${MODULE_NAME_CAPITALIZED}ServiceProvider"
    ],
    "aliases": {},
    "files": [
        "Providers/${MODULE_NAME_CAPITALIZED}ServiceProvider.php"
    ],
    "requires": []
}
EOF

# 3. Créer ServiceProvider simplifié (sans RouteServiceProvider pour éviter les conflits)
cat > Modules/$MODULE_NAME_CAPITALIZED/Providers/${MODULE_NAME_CAPITALIZED}ServiceProvider.php << EOF
<?php

namespace Modules\\$MODULE_NAME_CAPITALIZED\\Providers;

use Illuminate\Support\ServiceProvider;

class ${MODULE_NAME_CAPITALIZED}ServiceProvider extends ServiceProvider
{
    protected \$moduleName = '$MODULE_NAME_CAPITALIZED';
    protected \$moduleNameLower = '$MODULE_NAME_LOWER';

    public function register(): void
    {
        \$this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php', 
            'module_' . \$this->moduleNameLower
        );
    }

    public function boot(): void
    {
        // Charger les routes seulement si le fichier existe
        if (file_exists(__DIR__ . '/../Presentation/Routes/api.php')) {
            \$this->loadRoutesFrom(__DIR__ . '/../Presentation/Routes/api.php');
        }
        
        if (file_exists(__DIR__ . '/../Presentation/Routes/web.php')) {
            \$this->loadRoutesFrom(__DIR__ . '/../Presentation/Routes/web.php');
        }
        
        // Charger les migrations si le dossier existe
        if (is_dir(__DIR__ . '/../Data/Migrations')) {
            \$this->loadMigrationsFrom(__DIR__ . '/../Data/Migrations');
        }
        
        // Charger les vues si le dossier existe
        if (is_dir(__DIR__ . '/../Presentation/Views')) {
            \$this->loadViewsFrom(__DIR__ . '/../Presentation/Views', \$this->moduleNameLower);
        }
    }
}
EOF

# 4. Créer config détaillée
cat > Modules/$MODULE_NAME_CAPITALIZED/Config/config.php << EOF
<?php

return [
    'name' => '$MODULE_NAME_CAPITALIZED',
    'description' => '$MODULE_NAME_CAPITALIZED management module',
    'version' => '1.0.0',
    'routes' => [
        'api' => [
            'prefix' => 'api/$MODULE_NAME_LOWER',
            'middleware' => ['api'],
        ],
        'web' => [
            'prefix' => '$MODULE_NAME_LOWER',
            'middleware' => ['web'],
        ],
    ],
];
EOF

# 5. Créer un controller API Resource
cat > Modules/$MODULE_NAME_CAPITALIZED/Presentation/Controllers/${MODULE_NAME_CAPITALIZED}Controller.php << EOF
<?php

namespace Modules\\$MODULE_NAME_CAPITALIZED\\Presentation\\Controllers;

use Illuminate\\Routing\\Controller;
use Illuminate\\Http\\JsonResponse;

class ${MODULE_NAME_CAPITALIZED}Controller extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => '$MODULE_NAME_CAPITALIZED module is working!',
            'module' => '$MODULE_NAME_CAPITALIZED',
            'version' => '1.0.0',
            'status' => 'active'
        ]);
    }
    
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'module' => '$MODULE_NAME_CAPITALIZED',
            'timestamp' => now()->toISOString()
        ]);
    }
}
EOF

# 6. Créer les routes API et Web
cat > Modules/$MODULE_NAME_CAPITALIZED/Presentation/Routes/api.php << EOF
<?php

use Illuminate\\Support\\Facades\\Route;
use Modules\\$MODULE_NAME_CAPITALIZED\\Presentation\\Controllers\\${MODULE_NAME_CAPITALIZED}Controller;

Route::prefix('$MODULE_NAME_LOWER')
    ->middleware(['api'])
    ->group(function () {
        Route::get('/', [${MODULE_NAME_CAPITALIZED}Controller::class, 'index']);
        Route::get('/health', [${MODULE_NAME_CAPITALIZED}Controller::class, 'health']);
    });
EOF

cat > Modules/$MODULE_NAME_CAPITALIZED/Presentation/Routes/web.php << EOF
<?php

use Illuminate\\Support\\Facades\\Route;

Route::prefix('$MODULE_NAME_LOWER')
    ->middleware(['web'])
    ->group(function () {
        Route::get('/', function () {
            return view('$MODULE_NAME_LOWER::welcome');
        });
    });
EOF

# 7. Créer une vue de base
cat > Modules/$MODULE_NAME_CAPITALIZED/Presentation/Views/welcome.blade.php << EOF
<!DOCTYPE html>
<html>
<head>
    <title>$MODULE_NAME_CAPITALIZED Module</title>
</head>
<body>
    <h1>$MODULE_NAME_CAPITALIZED Module</h1>
    <p>This module is successfully installed and working.</p>
</body>
</html>
EOF

# 8. Créer des fichiers .gitkeep pour les dossiers vides
for dir in $(find Modules/$MODULE_NAME_CAPITALIZED -type d -empty); do
    touch "$dir/.gitkeep"
done

echo -e "\n📦 Configuration de l'autoload..."
# Vérifier si le namespace Modules existe déjà dans composer.json
if ! grep -q '"Modules\\\\\\\\": "Modules/"' composer.json; then
    echo "   ➕ Ajout de Modules\\\\ à l'autoload PSR-4..."
    
    # Créer une copie de sécurité
    cp composer.json composer.json.bak
    
    # Méthode plus robuste pour mettre à jour composer.json
    if command -v jq &> /dev/null; then
        # Utiliser jq si disponible
        jq '.autoload."psr-4"."Modules\\\\" = "Modules/"' composer.json > composer.tmp && mv composer.tmp composer.json
    else
        # Méthode sed pour Windows/Linux
        if [[ "$OSTYPE" == "darwin"* ]]; then
            # macOS
            sed -i '' '/"psr-4": {/a\
            "Modules\\\\": "Modules/",' composer.json
        else
            # Linux/Windows (Git Bash)
            sed -i '/"psr-4": {/a\        "Modules\\\\": "Modules/",' composer.json
        fi
    fi
    
    echo "   ✅ Modules\\\\ ajouté à composer.json"
    
    # 9. Dump autoload avec gestion d'erreur améliorée
    echo -e "\n🔄 Optimisation de l'autoload..."
    echo "   ⏳ Cela peut prendre quelques minutes..."
    
    # Exécuter composer dump-autoload en arrière-plan avec timeout
    timeout 300 composer dump-autoload --no-scripts 2>&1 | while IFS= read -r line; do
        if [[ $line == *"error"* ]] || [[ $line == *"Error"* ]]; then
            echo "   ⚠️  $line"
        elif [[ $line == *"Generating"* ]] || [[ $line == *"Class"* ]]; then
            echo "   🔧 $line"
        fi
    done
    
    # Vérifier si le dump a réussi
    if [ ${PIPESTATUS[0]} -eq 0 ]; then
        echo "   ✅ Autoload mis à jour avec succès"
        
        # Exécuter package:discover séparément
        echo "   🔍 Découverte des packages..."
        php artisan package:discover 2>/dev/null || true
    else
        echo "   ⚠️  Problème avec composer dump-autoload"
        echo "   💡 Essayez manuellement: composer dump-autoload"
    fi
else
    echo "   ⏭️ Modules\\\\ déjà dans l'autoload"
fi

# 10. Créer un README pour le module
cat > Modules/$MODULE_NAME_CAPITALIZED/README.md << EOF
# $MODULE_NAME_CAPITALIZED Module

## Structure DDD

\`\`\`
Modules/$MODULE_NAME_CAPITALIZED/
├── Domain/           # Business logic
├── Application/      # Use cases & services
├── Infrastructure/   # External implementations
├── Presentation/     # Controllers, routes, views
├── Data/            # Migrations, seeders
├── Tests/           # Feature & unit tests
├── Providers/       # Service providers
└── Support/         # Helpers, traits
\`\`\`

## Installation

1. Ajouter au \`config/app.php\` (section providers):
   \`\`\`php
   Modules\\$MODULE_NAME_CAPITALIZED\\Providers\\${MODULE_NAME_CAPITALIZED}ServiceProvider::class,
   \`\`\`

2. Exécuter:
   \`\`\`bash
   composer dump-autoload
   \`\`\`

## Routes

- API: \`GET /api/$MODULE_NAME_LOWER\`
- Web: \`GET /$MODULE_NAME_LOWER\`

## Test

\`\`\`bash
curl http://localhost:8000/api/$MODULE_NAME_LOWER
\`\`\`
EOF

echo -e "\n✅ Module $MODULE_NAME_CAPITALIZED créé avec succès!"
echo -e "📁 Structure: Modules/$MODULE_NAME_CAPITALIZED/"
echo -e "\n🔧 **Étapes d'installation MANUELLES si nécessaire:**"
echo -e "   1. Vérifiez que composer.json contient: \"Modules\\\\\": \"Modules/\""
echo -e "   2. Si non, ajoutez-le dans la section autoload.psr-4"
echo -e "   3. Exécutez: composer dump-autoload"
echo -e "   4. Ajoutez dans config/app.php (providers):"
echo -e "      Modules\\\\$MODULE_NAME_CAPITALIZED\\\\Providers\\\\${MODULE_NAME_CAPITALIZED}ServiceProvider::class"
echo -e "   5. Testez: curl http://localhost:8000/api/$MODULE_NAME_LOWER"
echo -e "\n⚠️  **Note importante:**"
echo -e "   Le module 'Project' existant a une structure incompatible."
echo -e "   Vous devriez soit:"
echo -e "   - Migrer le module Project vers la structure DDD"
