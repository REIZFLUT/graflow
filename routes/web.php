<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ArticleMediaController;
use App\Http\Controllers\ArticleMetadataController;
use App\Http\Controllers\ArticlePdfController;
use App\Http\Controllers\ArticleVersionController;
use App\Http\Controllers\ArticleWorkflowController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EditorSettingsSetController;
use App\Http\Controllers\PublicationCategoryController;
use App\Http\Controllers\PublicationChapterController;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\PublicationIssueController;
use App\Http\Controllers\PublicationIssuePlanningController;
use App\Http\Controllers\PublicationIssueReaderController;
use App\Http\Controllers\SpellCheckController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::post('spellcheck', [SpellCheckController::class, 'check'])
        ->name('spellcheck.check');

    Route::resource('articles', ArticleController::class)->except(['show']);
    Route::get('articles/{article}/metadata', [ArticleMetadataController::class, 'edit'])
        ->name('articles.metadata.edit');
    Route::patch('articles/{article}/metadata', [ArticleMetadataController::class, 'update'])
        ->name('articles.metadata.update');
    Route::post('articles/{article}/versions/{version}/restore', [ArticleVersionController::class, 'restore'])
        ->name('articles.versions.restore');
    Route::prefix('articles/{article}/workflow')->name('articles.workflow.')->group(function () {
        Route::post('assign-author', [ArticleWorkflowController::class, 'assignAuthor'])
            ->name('assign-author');
        Route::post('assign-editorial', [ArticleWorkflowController::class, 'assignEditorial'])
            ->name('assign-editorial');
        Route::post('submit-manuscript', [ArticleWorkflowController::class, 'submitManuscript'])
            ->name('submit-manuscript');
        Route::post('complete-editorial-work', [ArticleWorkflowController::class, 'completeEditorialWork'])
            ->name('complete-editorial-work');
        Route::post('force-status', [ArticleWorkflowController::class, 'forceStatus'])
            ->name('force-status');
        Route::post('request-revision', [ArticleWorkflowController::class, 'requestRevision'])
            ->name('request-revision');
        Route::post('start-product-manager-correction', [ArticleWorkflowController::class, 'startProductManagerCorrection'])
            ->name('start-product-manager-correction');
        Route::post('complete-product-manager-correction', [ArticleWorkflowController::class, 'completeProductManagerCorrection'])
            ->name('complete-product-manager-correction');
        Route::post('recall', [ArticleWorkflowController::class, 'recall'])
            ->name('recall');
        Route::post('mark-ready', [ArticleWorkflowController::class, 'markReady'])
            ->name('mark-ready');
        Route::post('publish', [ArticleWorkflowController::class, 'publish'])
            ->name('publish');
    });

    Route::prefix('articles/{article}/pdfs')->name('articles.pdfs.')->group(function () {
        Route::get('/', [ArticlePdfController::class, 'index'])->name('index');
        Route::post('/', [ArticlePdfController::class, 'store'])->name('store');
        Route::get('{pdf}', [ArticlePdfController::class, 'show'])->name('show');
        Route::get('{pdf}/file', [ArticlePdfController::class, 'file'])->name('file');
        Route::post('{pdf}/annotated', [ArticlePdfController::class, 'storeAnnotated'])->name('annotated.store');
    });

    Route::resource('editor-settings-sets', EditorSettingsSetController::class)->except(['show']);

    Route::resource('users', UserController::class)->except(['show']);

    Route::resource('publications', PublicationController::class)->except(['show']);
    Route::post('publications/{publication}/issues', [PublicationIssueController::class, 'store'])
        ->name('publications.issues.store');
    Route::patch('publications/{publication}/issues/{issue}', [PublicationIssueController::class, 'update'])
        ->name('publications.issues.update');
    Route::delete('publications/{publication}/issues/{issue}', [PublicationIssueController::class, 'destroy'])
        ->name('publications.issues.destroy');
    Route::get(
        'publications/{publication}/issues/{issue}/planning',
        [PublicationIssuePlanningController::class, 'show'],
    )->name('publications.issues.planning.show');
    Route::get(
        'publications/{publication}/issues/{issue}/reader',
        [PublicationIssueReaderController::class, 'show'],
    )->name('publications.issues.reader.show');
    Route::post(
        'publications/{publication}/issues/{issue}/planning/articles',
        [PublicationIssuePlanningController::class, 'store'],
    )->name('publications.issues.planning.articles.store');
    Route::post(
        'publications/{publication}/issues/{issue}/chapters',
        [PublicationChapterController::class, 'store'],
    )->name('publications.issues.chapters.store');
    Route::patch(
        'publications/{publication}/issues/{issue}/chapters/{chapter}',
        [PublicationChapterController::class, 'update'],
    )->name('publications.issues.chapters.update');
    Route::delete(
        'publications/{publication}/issues/{issue}/chapters/{chapter}',
        [PublicationChapterController::class, 'destroy'],
    )->name('publications.issues.chapters.destroy');
    Route::post('publications/{publication}/categories', [PublicationCategoryController::class, 'store'])
        ->name('publications.categories.store');
    Route::patch('publications/{publication}/categories/{category}', [PublicationCategoryController::class, 'update'])
        ->name('publications.categories.update');
    Route::delete('publications/{publication}/categories/{category}', [PublicationCategoryController::class, 'destroy'])
        ->name('publications.categories.destroy');

    Route::prefix('articles/media/staging')->name('articles.media.staging.')->group(function () {
        Route::get('/', [ArticleMediaController::class, 'indexStaging'])->name('index');
        Route::post('/', [ArticleMediaController::class, 'storeStaging'])->name('store');
        Route::patch('{media}', [ArticleMediaController::class, 'updateStaging'])->name('update');
        Route::delete('{media}', [ArticleMediaController::class, 'destroyStaging'])->name('destroy');
        Route::get('{media}/file/{variant}', [ArticleMediaController::class, 'serveStagingFile'])
            ->whereIn('variant', ['original', 'preview-webp', 'preview-jpeg'])
            ->name('file');
    });

    Route::prefix('articles/{article}/media')->name('articles.media.')->group(function () {
        Route::get('/', [ArticleMediaController::class, 'index'])->name('index');
        Route::post('/', [ArticleMediaController::class, 'store'])->name('store');
        Route::patch('{media}', [ArticleMediaController::class, 'update'])->name('update');
        Route::delete('{media}', [ArticleMediaController::class, 'destroy'])->name('destroy');
        Route::get('{media}/file/{variant}', [ArticleMediaController::class, 'serveFile'])
            ->whereIn('variant', ['original', 'preview-webp', 'preview-jpeg'])
            ->name('file');
    });
});

require __DIR__.'/settings.php';
