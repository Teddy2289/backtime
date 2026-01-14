#!/bin/bash

echo "🔧 Correction de la casse des modules..."

cd /Users/mbl/Desktop/timer/backtime

# Liste des corrections
declare -A corrections=(
    ["Taskfiles"]="TaskFiles"
    ["Projectsteams"]="ProjectsTeams" 
    ["Tasktimelog"]="TaskTimeLog"
    ["Taskcomment"]="TaskComment"
)

# Renommer les dossiers
for wrong in "${!corrections[@]}"; do
    right="${corrections[$wrong]}"
    
    if [ -d "Modules/$wrong" ]; then
        echo "Renommage dossier: $wrong → $right"
        mv "Modules/$wrong" "Modules/$right"
    fi
done

# Renommer les fichiers à l'intérieur
echo ""
echo "📁 Correction des fichiers..."

# TaskFiles
if [ -d "Modules/TaskFiles" ]; then
    mv Modules/TaskFiles/Providers/TaskfilesServiceProvider.php Modules/TaskFiles/Providers/TaskFilesServiceProvider.php 2>/dev/null || true
    mv Modules/TaskFiles/Presentation/Controllers/TaskfilesController.php Modules/TaskFiles/Presentation/Controllers/TaskFilesController.php 2>/dev/null || true
fi

# TaskTimeLog
if [ -d "Modules/TaskTimeLog" ]; then
    mv Modules/TaskTimeLog/Providers/TasktimelogServiceProvider.php Modules/TaskTimeLog/Providers/TaskTimeLogServiceProvider.php 2>/dev/null || true
    mv Modules/TaskTimeLog/Presentation/Controllers/TasktimelogController.php Modules/TaskTimeLog/Presentation/Controllers/TaskTimeLogController.php 2>/dev/null || true
fi

# ProjectsTeams
if [ -d "Modules/ProjectsTeams" ]; then
    mv Modules/ProjectsTeams/Providers/ProjectsteamsServiceProvider.php Modules/ProjectsTeams/Providers/ProjectsTeamsServiceProvider.php 2>/dev/null || true
    mv Modules/ProjectsTeams/Presentation/Controllers/ProjectsteamsController.php Modules/ProjectsTeams/Presentation/Controllers/ProjectsTeamsController.php 2>/dev/null || true
fi

# TaskComment
if [ -d "Modules/TaskComment" ]; then
    mv Modules/TaskComment/Providers/TaskcommentServiceProvider.php Modules/TaskComment/Providers/TaskCommentServiceProvider.php 2>/dev/null || true
    mv Modules/TaskComment/Presentation/Controllers/TaskCommentController.php Modules/TaskComment/Presentation/Controllers/TaskCommentController.php 2>/dev/null || true
fi

echo "✅ Correction terminée!"