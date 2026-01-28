<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_column_id' => $this->task_column_id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'starts_at' => $this->starts_at?->toDateString(),
            'ends_at' => $this->ends_at?->toDateString(),
            'sort_order' => $this->sort_order,
            'is_completed' => $this->is_completed,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'active_entry' => $this->whenLoaded('activeTimeEntry', function () {
                if (! $this->activeTimeEntry) {
                    return null;
                }

                return [
                    'id' => $this->activeTimeEntry->id,
                    'started_at' => $this->activeTimeEntry->started_at?->toIso8601String(),
                ];
            }),
            'column' => new TaskColumnResource($this->whenLoaded('taskColumn')),
            'labels' => TaskLabelResource::collection(
                $this->whenLoaded('labels'),
            ),
            'tags' => TaskTagResource::collection(
                $this->whenLoaded('tags'),
            ),
        ];
    }
}
