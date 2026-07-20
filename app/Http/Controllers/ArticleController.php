<?php

namespace App\Http\Controllers;

use App\Enums\ArticleStatus;
use App\Enums\UserRole;
use App\Http\Requests\IndexArticleRequest;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Http\Resources\ArticleCommentThreadResource;
use App\Http\Resources\ArticleMediaResource;
use App\Http\Resources\ArticleWorkflowEventResource;
use App\Models\Article;
use App\Models\Publication;
use App\Models\PublicationIssue;
use App\Models\User;
use App\Services\ArticleVersionService;
use App\Support\ArticleCharacterCounter;
use App\Support\ArticleEditorSettingsResolver;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function __construct(
        private ArticleVersionService $articleVersionService,
        private ArticleCharacterCounter $articleCharacterCounter,
        private ArticleEditorSettingsResolver $editorSettingsResolver,
    ) {}

    public function index(IndexArticleRequest $request): Response
    {
        $this->authorize('viewAny', Article::class);

        $user = auth()->user();
        $filters = $request->validated();

        $search = $filters['search'] ?? null;
        $sort = $filters['sort'] ?? null;
        $direction = ($filters['direction'] ?? null) === 'desc' ? 'desc' : 'asc';
        $publicationId = isset($filters['publication_id']) ? (int) $filters['publication_id'] : null;
        $issueId = isset($filters['issue_id']) ? (int) $filters['issue_id'] : null;
        $authorId = isset($filters['author_id']) ? (int) $filters['author_id'] : null;
        $archived = $request->boolean('archived');
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;

        $applyAuthorizationScope = function ($query) use ($user): void {
            $query->when($user->role !== UserRole::Admin, fn ($scoped) => $scoped->where(
                fn ($relevant) => $relevant
                    ->where('product_manager_id', $user->id)
                    ->orWhere('author_id', $user->id)
                    ->orWhere('current_assignee_id', $user->id)
                    ->orWhereHas('participants', fn ($participants) => $participants->where('user_id', $user->id)),
            ));
        };

        $articles = Article::query()
            ->tap($applyAuthorizationScope)
            ->when(
                $archived,
                fn ($query) => $query->where('articles.status', ArticleStatus::Published),
                fn ($query) => $query->where('articles.status', '!=', ArticleStatus::Published),
            )
            ->when($search !== null, fn ($query) => $query->where('articles.title', 'like', "%{$search}%"))
            ->when($publicationId !== null, fn ($query) => $query->whereHas(
                'publicationIssue',
                fn ($issue) => $issue->where('publication_id', $publicationId),
            ))
            ->when($issueId !== null, fn ($query) => $query->where('articles.publication_issue_id', $issueId))
            ->when($authorId !== null, fn ($query) => $query->where('articles.author_id', $authorId))
            ->with([
                'author:id,name',
                'currentAssignee:id,name',
                'publicationChapter:id,title,position',
                'publicationIssue.publication',
            ]);

        if ($sort !== null) {
            match ($sort) {
                'title' => $articles->orderBy('articles.title', $direction),
                'status' => $articles->orderBy('articles.status', $direction),
                'deadline' => $articles->orderBy('articles.submission_deadline', $direction),
                'updated_at' => $articles->orderBy('articles.updated_at', $direction),
                'publication' => $articles
                    ->leftJoin('publication_issues', 'articles.publication_issue_id', '=', 'publication_issues.id')
                    ->leftJoin('publications', 'publication_issues.publication_id', '=', 'publications.id')
                    ->select('articles.*')
                    ->orderBy('publications.name', $direction),
                'assignee' => $articles
                    ->leftJoin('users', 'articles.current_assignee_id', '=', 'users.id')
                    ->select('articles.*')
                    ->orderBy('users.name', $direction),
            };
        } else {
            $articles->latest();
        }

        $articles = $articles->paginate($perPage)->withQueryString();

        return Inertia::render('articles/index', [
            'articles' => $articles,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $sort !== null ? $direction : null,
                'publication_id' => $publicationId,
                'issue_id' => $issueId,
                'author_id' => $authorId,
                'archived' => $archived,
                'per_page' => $perPage,
            ],
            'filterOptions' => $this->articleFilterOptions($applyAuthorizationScope),
        ]);
    }

    /**
     * @param  \Closure(Builder): void  $applyAuthorizationScope
     * @return array{
     *     publications: list<array{id: int, name: string}>,
     *     issues: list<array{id: int, label: string, publication_id: int}>,
     *     authors: list<array{id: int, name: string}>,
     * }
     */
    private function articleFilterOptions(\Closure $applyAuthorizationScope): array
    {
        $publications = Publication::query()
            ->whereHas('issues.articles', $applyAuthorizationScope)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (Publication $publication): array => [
                'id' => $publication->id,
                'name' => $publication->name,
            ])
            ->all();

        $issues = PublicationIssue::query()
            ->whereHas('articles', $applyAuthorizationScope)
            ->orderBy('label')
            ->get(['id', 'label', 'publication_id'])
            ->map(static fn (PublicationIssue $issue): array => [
                'id' => $issue->id,
                'label' => $issue->label,
                'publication_id' => $issue->publication_id,
            ])
            ->all();

        $authorIds = Article::query()
            ->tap($applyAuthorizationScope)
            ->whereNotNull('author_id')
            ->distinct()
            ->pluck('author_id');

        $authors = User::query()
            ->whereIn('id', $authorIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (User $author): array => [
                'id' => $author->id,
                'name' => $author->name,
            ])
            ->all();

        return [
            'publications' => $publications,
            'issues' => $issues,
            'authors' => $authors,
        ];
    }

    public function create(): RedirectResponse
    {
        $this->authorize('create', Article::class);

        return to_route('publications.index');
    }

    public function store(StoreArticleRequest $request): RedirectResponse
    {
        $this->authorize('create', Article::class);

        return to_route('publications.index');
    }

    public function edit(Article $article): Response
    {
        $this->authorize('view', $article);

        $article->load([
            'author:id,name',
            'currentAssignee:id,name',
            'editorSettingsSet',
            'publicationIssue.publication.editorSettingsSet',
            'versions' => fn ($query) => $query
                ->with('createdBy:id,name')
                ->orderByDesc('version_number'),
            'media' => fn ($query) => $query->latest(),
            'workflowEvents' => fn ($query) => $query
                ->with(['actor:id,name', 'assignee:id,name'])
                ->orderBy('created_at'),
            'commentThreads' => fn ($query) => $query
                ->with(['createdBy:id,name', 'resolvedBy:id,name', 'comments.user:id,name'])
                ->orderBy('created_at'),
        ]);

        $canManageWorkflow = Gate::allows('manageWorkflow', $article);
        $canForceStatus = Gate::allows('forceStatus', $article);

        return Inertia::render('articles/edit', [
            'article' => array_merge($article->toArray(), [
                'media' => ArticleMediaResource::collection($article->media)->resolve(),
                'current_character_count' => $this->articleCharacterCounter->count(
                    $article->title,
                    $article->content,
                ),
            ]),
            'editorSettings' => $this->editorSettingsResolver->resolve($article),
            'capabilities' => [
                'update_content' => Gate::allows('updateContent', $article),
                'submit_manuscript' => Gate::allows('submitManuscript', $article),
                'complete_editorial_work' => Gate::allows('completeEditorialWork', $article),
                'request_revision' => Gate::allows('requestRevision', $article),
                'manage_workflow' => $canManageWorkflow,
                'force_status' => $canForceStatus,
                'unpublish' => Gate::allows('unpublish', $article),
                'delete' => Gate::allows('delete', $article),
                'comment' => Gate::allows('comment', $article),
            ],
            'allowedActions' => $this->allowedWorkflowActions($article),
            'workflowEvents' => ArticleWorkflowEventResource::collection($article->workflowEvents)->resolve(),
            'commentThreads' => ArticleCommentThreadResource::collection($article->commentThreads)->resolve(),
            ...($canManageWorkflow || $canForceStatus ? [
                'authors' => $this->workflowUsers([UserRole::Author]),
                'editorialStaff' => $this->workflowUsers([UserRole::Editor, UserRole::Lector]),
            ] : []),
        ]);
    }

    /**
     * @param  list<UserRole>  $roles
     * @return list<array{id: int, name: string, role: string}>
     */
    private function workflowUsers(array $roles): array
    {
        return array_values(User::query()
            ->whereIn('role', array_map(
                static fn (UserRole $role): string => $role->value,
                $roles,
            ))
            ->orderBy('name')
            ->get(['id', 'name', 'role'])
            ->values()
            ->map(static fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role->value,
            ])
            ->all());
    }

    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        $this->authorize('update', $article);

        DB::transaction(function () use ($request, $article) {
            $article->update($request->validated());

            $this->articleVersionService->snapshot($article, $request->user());
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.articles.saved'),
        ]);

        return to_route('articles.edit', $article);
    }

    public function destroy(Article $article): RedirectResponse
    {
        $this->authorize('delete', $article);

        $article->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.articles.deleted'),
        ]);

        return to_route('articles.index');
    }

    /**
     * @return list<string>
     */
    private function allowedWorkflowActions(Article $article): array
    {
        $actions = [];

        if (Gate::allows('submitManuscript', $article)) {
            $actions[] = 'submit_manuscript';
        }

        if (Gate::allows('completeEditorialWork', $article)) {
            $actions[] = 'complete_editorial_work';
        }

        if (Gate::allows('requestRevision', $article)) {
            $actions[] = 'request_revision';
        }

        if (Gate::allows('forceStatus', $article)) {
            $actions[] = 'force_status';
        }

        if (Gate::allows('unpublish', $article)) {
            $actions[] = 'unpublish';
        }

        if (Gate::allows('recall', $article)) {
            $actions[] = 'recall';
        }

        if (Gate::allows('startProductManagerCorrection', $article)) {
            $actions[] = 'start_product_manager_correction';
        }

        if (! Gate::allows('manageWorkflow', $article)) {
            return $actions;
        }

        return [
            ...$actions,
            ...match ($article->status) {
                ArticleStatus::Planned => ['assign_author'],
                ArticleStatus::ManuscriptSubmitted, ArticleStatus::RevisionRequested => [
                    'return_to_author',
                    'assign_author',
                    'assign_editorial',
                    'mark_ready',
                ],
                ArticleStatus::ProductManagerCorrection => [
                    'complete_product_manager_correction',
                ],
                ArticleStatus::EditorialWork => ['mark_ready'],
                ArticleStatus::ReadyForPublication => [
                    'return_to_author',
                    'assign_author',
                    'assign_editorial',
                    'publish',
                ],
                default => [],
            },
        ];
    }
}
