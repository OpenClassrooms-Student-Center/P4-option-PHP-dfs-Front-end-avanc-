<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonResponse;
use App\Core\Request;
use App\Repositories\ProjectRepository;
use App\Repositories\TaskRepository;
use App\Support\TaskSorter;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly ProjectRepository $projects,
    ) {
    }

    /** GET /dashboard/assigned-tasks : tâches assignées, les plus urgentes d'abord */
    public function assignedTasks(Request $request): JsonResponse
    {
        $tasks = $this->tasks->findAssignedTo($request->user()['id']);

        return JsonResponse::success('Tâches assignées récupérées', ['tasks' => TaskSorter::byUrgency($tasks)]);
    }

    /** GET /dashboard/projects-with-tasks : projets où l'utilisateur a des tâches assignées */
    public function projectsWithTasks(Request $request): JsonResponse
    {
        $tasksByProject = [];
        foreach ($this->tasks->findAssignedTo($request->user()['id']) as $task) {
            $tasksByProject[$task['projectId']][] = $task;
        }

        $projects = $this->projects->findSummariesByIds(array_keys($tasksByProject));
        foreach ($projects as &$project) {
            $project['tasks'] = TaskSorter::byUrgency($tasksByProject[$project['id']]);
        }

        return JsonResponse::success('Projets avec tâches récupérés', ['projects' => $projects]);
    }

    /** GET /dashboard/stats */
    public function stats(Request $request): JsonResponse
    {
        return JsonResponse::success('Statistiques du tableau de bord récupérées', [
            'stats' => $this->tasks->statsForAssignee($request->user()['id']),
        ]);
    }
}
