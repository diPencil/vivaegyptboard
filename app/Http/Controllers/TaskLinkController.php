<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Models\Task;
use App\Models\TaskLink;
use Illuminate\Http\Request;
use App\Traits\TaskAccessTrait;

class TaskLinkController extends AccountBaseController
{
    use TaskAccessTrait;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.tasks';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('tasks', $this->user->modules));
            return $next($request);
        });
    }

    public function store(Request $request, int $taskId)
    {
        $task = Task::findOrFail($taskId);
        abort_403(!$this->verifyTaskAccess($task));

        $addPermission = user()->permission('add_task_links');
        $taskUsers = $task->users->pluck('id')->toArray();
        $userId = user()->id;

        abort_403(!(
            $addPermission == 'all'
            || ($addPermission == 'added' && $task->added_by == $userId)
            || ($addPermission == 'owned' && in_array($userId, $taskUsers))
            || ($addPermission == 'both' && (in_array($userId, $taskUsers) || $task->added_by == $userId))
        ));

        $request->validate([
            'link_name' => 'required|string|max:255',
            'link_url' => 'required|url:http,https|max:2048',
            'description' => 'nullable|string|max:5000',
        ]);

        $link = new TaskLink();
        $link->task_id = $task->id;
        $link->link_name = trim($request->link_name);
        $link->link_url = trim($request->link_url);
        $link->description = trim_editor($request->description);
        $link->save();

        $this->logTaskActivity($task->id, $userId, 'linkAdded', $task->board_column_id);

        $this->task = $task;
        $view = view('tasks.ajax.links', $this->data)->render();

        return Reply::successWithData(__('modules.tasks.linkAddedSuccessfully'), ['view' => $view]);
    }

    public function edit(int $taskId, int $linkId)
    {
        $this->task = Task::findOrFail($taskId);
        abort_403(!$this->verifyTaskAccess($this->task));

        $this->link = $this->task->links()->whereKey($linkId)->firstOrFail();
        
        $editPermission = user()->permission('edit_task_links');
        $taskUsers = $this->task->users->pluck('id')->toArray();
        $userId = user()->id;

        abort_403(!(
            $editPermission == 'all'
            || ($editPermission == 'added' && $this->link->added_by == $userId)
            || ($editPermission == 'owned' && $this->link->added_by == $userId && in_array($userId, $taskUsers))
            || ($editPermission == 'both' && $this->link->added_by == $userId && (in_array($userId, $taskUsers) || $this->task->added_by == $userId))
        ));

        return view('tasks.ajax.edit_link', $this->data);
    }

    public function update(Request $request, int $taskId, int $linkId)
    {
        $task = Task::findOrFail($taskId);
        abort_403(!$this->verifyTaskAccess($task));

        $link = $task->links()->whereKey($linkId)->firstOrFail();

        $editPermission = user()->permission('edit_task_links');
        $taskUsers = $task->users->pluck('id')->toArray();
        $userId = user()->id;

        abort_403(!(
            $editPermission == 'all'
            || ($editPermission == 'added' && $link->added_by == $userId)
            || ($editPermission == 'owned' && $link->added_by == $userId && in_array($userId, $taskUsers))
            || ($editPermission == 'both' && $link->added_by == $userId && (in_array($userId, $taskUsers) || $task->added_by == $userId))
        ));

        $request->validate([
            'link_name' => 'required|string|max:255',
            'link_url' => 'required|url:http,https|max:2048',
            'description' => 'nullable|string|max:5000',
        ]);

        $link->link_name = trim($request->link_name);
        $link->link_url = trim($request->link_url);
        $link->description = trim_editor($request->description);
        $link->save();

        $this->logTaskActivity($task->id, $userId, 'linkUpdated', $task->board_column_id);

        $this->task = $task;
        $view = view('tasks.ajax.links', $this->data)->render();

        return Reply::successWithData(__('modules.tasks.linkUpdatedSuccessfully'), ['view' => $view]);
    }

    public function destroy(int $taskId, int $linkId)
    {
        $task = Task::findOrFail($taskId);
        abort_403(!$this->verifyTaskAccess($task));

        $link = $task->links()->whereKey($linkId)->firstOrFail();

        $deletePermission = user()->permission('delete_task_links');
        $taskUsers = $task->users->pluck('id')->toArray();
        $userId = user()->id;

        abort_403(!(
            $deletePermission == 'all'
            || ($deletePermission == 'added' && $link->added_by == $userId)
            || ($deletePermission == 'owned' && $link->added_by == $userId && in_array($userId, $taskUsers))
            || ($deletePermission == 'both' && $link->added_by == $userId && (in_array($userId, $taskUsers) || $task->added_by == $userId))
        ));

        $this->logTaskActivity($task->id, $userId, 'linkDeleted', $task->board_column_id);
        $link->delete();

        $this->task = $task;
        $view = view('tasks.ajax.links', $this->data)->render();

        return Reply::successWithData(__('modules.tasks.linkDeletedSuccessfully'), ['view' => $view]);
    }
}
