<?php

namespace App\Http\Resources;

use App\Models\ArticleComment;
use App\Models\ArticleCommentThread;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ArticleCommentThread */
class ArticleCommentThreadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ArticleCommentThread $thread */
        $thread = $this->resource;

        return [
            'id' => $thread->id,
            'anchor_text' => $thread->anchor_text,
            'resolved_at' => $thread->resolved_at?->toISOString(),
            'resolved_by' => $thread->resolved_by_id !== null && $thread->resolvedBy !== null
                ? [
                    'id' => $thread->resolvedBy->id,
                    'name' => $thread->resolvedBy->name,
                ]
                : null,
            'created_by' => [
                'id' => $thread->createdBy->id,
                'name' => $thread->createdBy->name,
            ],
            'created_at' => $thread->created_at?->toISOString(),
            'comments' => $thread->comments->map(static fn (ArticleComment $comment): array => [
                'id' => $comment->id,
                'body' => $comment->body,
                'created_at' => $comment->created_at?->toISOString(),
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                ],
            ])->all(),
        ];
    }
}
