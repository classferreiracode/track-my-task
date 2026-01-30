<?php

namespace App\Http\Controllers;

use App\Events\TaskActivityCreated;
use App\Events\TaskCommentCreated;
use App\Http\Requests\TaskCommentStoreRequest;
use App\Models\Task;
use App\Notifications\TaskMentioned;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TaskCommentController extends Controller
{
    public function index(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $task->loadMissing(['comments.user', 'comments.mentions', 'activities.user']);

        $comments = $task->comments()
            ->with(['user', 'mentions'])
            ->orderBy('created_at')
            ->get()
            ->map(fn ($comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'created_at' => $comment->created_at?->toIso8601String(),
                'user' => [
                    'id' => $comment->user?->id,
                    'name' => $comment->user?->name,
                    'email' => $comment->user?->email,
                ],
                'mentions' => $comment->mentions->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])->values(),
            ]);

        $activities = $task->activities()
            ->with('user')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($activity) => [
                'id' => $activity->id,
                'type' => $activity->type,
                'meta' => $activity->meta,
                'created_at' => $activity->created_at?->toIso8601String(),
                'user' => $activity->user ? [
                    'id' => $activity->user->id,
                    'name' => $activity->user->name,
                    'email' => $activity->user->email,
                ] : null,
            ]);

        return response()->json([
            'comments' => $comments,
            'activities' => $activities,
        ]);
    }

    public function store(TaskCommentStoreRequest $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $body = $request->string('body')->trim()->toString();

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $body,
        ]);

        $mentions = $this->resolveMentions($task, $body);

        if ($mentions->isNotEmpty()) {
            $comment->mentions()->sync($mentions->pluck('id')->all());
            $mentions->each(fn ($user) => $user->notify(
                new TaskMentioned($task, $comment, $request->user()),
            ));
        }

        $activity = $task->activities()->create([
            'user_id' => $request->user()->id,
            'type' => 'commented',
            'meta' => [
                'comment_id' => $comment->id,
                'mentions' => $mentions->pluck('id')->all(),
            ],
        ]);

        $comment->loadMissing(['user', 'mentions']);
        $activity->loadMissing('user');

        TaskCommentCreated::dispatch($comment);
        TaskActivityCreated::dispatch($activity);

        return response()->json([
            'comment' => [
                'id' => $comment->id,
                'body' => $comment->body,
                'created_at' => $comment->created_at?->toIso8601String(),
                'user' => [
                    'id' => $comment->user?->id,
                    'name' => $comment->user?->name,
                    'email' => $comment->user?->email,
                ],
                'mentions' => $comment->mentions->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])->values(),
            ],
            'activity' => [
                'id' => $activity->id,
                'type' => $activity->type,
                'meta' => $activity->meta,
                'created_at' => $activity->created_at?->toIso8601String(),
                'user' => $activity->user ? [
                    'id' => $activity->user->id,
                    'name' => $activity->user->name,
                    'email' => $activity->user->email,
                ] : null,
            ],
        ]);
    }

    /**
     * @return Collection<int, \App\Models\User>
     */
    protected function resolveMentions(Task $task, string $body): Collection
    {
        if (! preg_match_all('/@([\\pL\\pN._-]+)/u', $body, $matches)) {
            return collect();
        }

        $tokens = collect($matches[1])
            ->filter()
            ->map(fn ($token) => Str::lower($token))
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            return collect();
        }

        $task->loadMissing('taskColumn.board.workspace');

        $workspace = $task->taskColumn?->board?->workspace;

        if (! $workspace) {
            return collect();
        }

        $members = $workspace->members()
            ->get(['users.id', 'users.name', 'users.email']);

        return $members->filter(function ($member) use ($tokens) {
            $name = Str::lower($member->name);
            $email = Str::lower($member->email);

            return $tokens->contains(fn ($token) => Str::startsWith($name, $token) || Str::startsWith($email, $token));
        })->values();
    }
}
