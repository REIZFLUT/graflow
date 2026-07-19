<?php

namespace App\Http\Controllers;

use App\Enums\ArticleStatus;
use App\Enums\UserRole;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Http\Resources\ArticleMediaResource;
use App\Http\Resources\ArticleWorkflowEventResource;
use App\Models\Article;
use App\Models\User;
use App\Services\ArticleVersionService;
use App\Support\ArticleCharacterCounter;
use App\Support\ArticleEditorSettingsResolver;
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

    public function index(): Response
    {
        $this->authorize('viewAny', Article::class);

        $user = auth()->user();
        $articles = Article::query()
            ->when($user->role !== UserRole::Admin, fn ($query) => $query->where(
                fn ($relevant) => $relevant
                    ->where('product_manager_id', $user->id)
                    ->orWhere('author_id', $user->id)
                    ->orWhere('current_assignee_id', $user->id)
                    ->orWhereHas('participants', fn ($participants) => $participants->where('user_id', $user->id)),
            ))
            ->with([
                'author:id,name',
                'currentAssignee:id,name',
                'publicationChapter:id,title,position',
                'publicationIssue.publication',
            ])
            ->latest()
            ->paginate(15);

        return Inertia::render('articles/index', [
            'articles' => $articles,
        ]);
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
                'delete' => Gate::allows('delete', $article),
            ],
            'allowedActions' => $this->allowedWorkflowActions($article),
            'workflowEvents' => ArticleWorkflowEventResource::collection($article->workflowEvents)->resolve(),
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

        if (! Gate::allows('manageWorkflow', $article)) {
            return $actions;
        }

        return [
            ...$actions,
            ...match ($article->status) {
                ArticleStatus::Planned => ['assign_author'],
                ArticleStatus::ManuscriptSubmitted, ArticleStatus::RevisionRequested => [
                    'start_product_manager_correction',
                    'assign_author',
                    'assign_editorial',
                    'mark_ready',
                ],
                ArticleStatus::ProductManagerCorrection => [
                    'complete_product_manager_correction',
                ],
                ArticleStatus::EditorialWork => ['recall', 'mark_ready'],
                ArticleStatus::ReadyForPublication => ['assign_author', 'assign_editorial', 'publish'],
                default => [],
            },
        ];
    }
}
