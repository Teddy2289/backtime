#!/bin/bash

echo "🔧 Correction des noms de classes..."

cd /Users/mbl/Desktop/timer/backtime

# TaskFiles
[ -f "Modules/TaskFiles/Providers/TaskFilesServiceProvider.php" ] && \
    sed -i '' 's/class TaskfilesServiceProvider/class TaskFilesServiceProvider/' Modules/TaskFiles/Providers/TaskFilesServiceProvider.php

[ -f "Modules/TaskFiles/Presentation/Controllers/TaskFilesController.php" ] && \
    sed -i '' 's/class TaskfilesController/class TaskFilesController/' Modules/TaskFiles/Presentation/Controllers/TaskFilesController.php

# TaskTimeLog
[ -f "Modules/TaskTimeLog/Providers/TaskTimeLogServiceProvider.php" ] && \
    sed -i '' 's/class TasktimelogServiceProvider/class TaskTimeLogServiceProvider/' Modules/TaskTimeLog/Providers/TaskTimeLogServiceProvider.php

[ -f "Modules/TaskTimeLog/Presentation/Controllers/TaskTimeLogController.php" ] && \
    sed -i '' 's/class TasktimelogController/class TaskTimeLogController/' Modules/TaskTimeLog/Presentation/Controllers/TaskTimeLogController.php

# ProjectsTeams
[ -f "Modules/ProjectsTeams/Providers/ProjectsTeamsServiceProvider.php" ] && \
    sed -i '' 's/class ProjectsteamsServiceProvider/class ProjectsTeamsServiceProvider/' Modules/ProjectsTeams/Providers/ProjectsTeamsServiceProvider.php

[ -f "Modules/ProjectsTeams/Presentation/Controllers/ProjectsTeamsController.php" ] && \
    sed -i '' 's/class ProjectsteamsController/class ProjectsTeamsController/' Modules/ProjectsTeams/Presentation/Controllers/ProjectsTeamsController.php

# TaskComment
[ -f "Modules/TaskComment/Providers/TaskCommentServiceProvider.php" ] && \
    sed -i '' 's/class TaskcommentServiceProvider/class TaskCommentServiceProvider/' Modules/TaskComment/Providers/TaskCommentServiceProvider.php

[ -f "Modules/TaskComment/Presentation/Controllers/TaskCommentController.php" ] && \
    sed -i '' 's/class TaskCommentController/class TaskCommentController/' Modules/TaskComment/Presentation/Controllers/TaskCommentController.php

echo "✅ Noms de classes corrigés!"