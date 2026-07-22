<?php

namespace App\Traits;

trait TaskAccessTrait
{
    public function verifyTaskAccess($task)
    {
        $viewTaskPermission = user()->permission('view_tasks');
        $taskUsers = $task->users->pluck('id')->toArray();
        $userId = user()->id;
        $mentionUser = $task->mentionTask->pluck('user_id')->toArray() ?? [];
        $viewUnassignedTasksPermission = user()->permission('view_unassigned_tasks');

        $hasAccess = (
            $viewTaskPermission == 'all'
            || ($viewTaskPermission == 'added' && $task->added_by == $userId)
            || ($viewTaskPermission == 'owned' && in_array($userId, $taskUsers))
            || ($viewTaskPermission == 'both' && (in_array($userId, $taskUsers) || $task->added_by == $userId))
            || ($viewTaskPermission == 'owned' && in_array('client', user_roles()) && $task->project_id && $task->project->client_id == $userId)
            || ($viewTaskPermission == 'both' && in_array('client', user_roles()) && $task->project_id && $task->project->client_id == $userId)
            || ($viewUnassignedTasksPermission == 'all' && in_array('employee', user_roles()) && count($taskUsers) == 0)
            || ($task->project_id && $task->project->project_admin == $userId)
            || (!empty($mentionUser) && in_array($userId, $mentionUser))
        );

        if (!$task->project_id || ($task->project_id && $task->project->project_admin != $userId)) {
            if ($viewUnassignedTasksPermission == 'none' && count($taskUsers) == 0 && (!in_array($userId, $mentionUser))) {
                return false;
            }
        }

        return $hasAccess;
    }
}
