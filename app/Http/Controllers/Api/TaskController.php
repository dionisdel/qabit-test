<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $tasks = Task::query()
            ->orderBy('id')
            ->paginate();

        return TaskResource::collection($tasks);
    }

    public function show(Task $task): TaskResource
    {
        return new TaskResource($task);
    }
}
