<?php

namespace App\Http\Resources;

use App\Models\ArticleWorkflowEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ArticleWorkflowEvent */
class ArticleWorkflowEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ArticleWorkflowEvent $event */
        $event = $this->resource;

        return [
            'id' => $event->id,
            'from_status' => $event->from_status?->value,
            'to_status' => $event->to_status->value,
            'reason' => $event->reason,
            'created_at' => $event->created_at->toISOString(),
            'actor' => [
                'id' => $event->actor->id,
                'name' => $event->actor->name,
            ],
            'assignee' => $event->assignee !== null
                ? [
                    'id' => $event->assignee->id,
                    'name' => $event->assignee->name,
                ]
                : null,
        ];
    }
}
