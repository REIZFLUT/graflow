<?php

namespace Tests\Feature\Articles;

use App\Models\Article;
use App\Models\ArticleMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Tests\TestCase;

class ArticleMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['article-media.disk' => 'local']);
    }

    /**
     * @return array<string, mixed>
     */
    private function tipTapContent(string $text = 'Hello'): array
    {
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => $text],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tipTapContentWithImage(string $mediaId): array
    {
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'articleImage',
                    'attrs' => [
                        'mediaId' => $mediaId,
                        'alt' => 'Alt text',
                        'copyright' => 'Copyright holder',
                        'caption' => 'Caption',
                        'previewWebpUrl' => '/preview.webp',
                        'previewJpegUrl' => '/preview.jpg',
                    ],
                ],
            ],
        ];
    }

    private function makeTestImage(int $width = 2000, int $height = 1200): UploadedFile
    {
        $manager = new ImageManager(new Driver);
        $image = $manager->create($width, $height)->fill('ff5500');

        $path = tempnam(sys_get_temp_dir(), 'article-media-test');
        $image->toJpeg(90)->save($path);

        return new UploadedFile(
            $path,
            'test-image.jpg',
            'image/jpeg',
            null,
            true,
        );
    }

    public function test_user_can_upload_staging_media_with_metadata(): void
    {
        $user = User::factory()->create();
        $stagingToken = (string) Str::uuid();

        $response = $this->actingAs($user)->postJson(route('articles.media.staging.store'), [
            'file' => $this->makeTestImage(),
            'alt_text' => 'Ein Testbild',
            'copyright' => 'Foto: Test User',
            'caption' => 'Optionale Caption',
            'staging_token' => $stagingToken,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.alt_text', 'Ein Testbild')
            ->assertJsonPath('data.copyright', 'Foto: Test User')
            ->assertJsonPath('data.caption', 'Optionale Caption');

        $mediaId = $response->json('data.id');

        $this->assertDatabaseHas('article_media', [
            'id' => $mediaId,
            'owner_id' => $user->id,
            'staging_token' => $stagingToken,
            'article_id' => null,
        ]);

        $media = ArticleMedia::query()->findOrFail($mediaId);

        Storage::disk('local')->assertExists($media->original_path);
        Storage::disk('local')->assertExists($media->preview_webp_path);
        Storage::disk('local')->assertExists($media->preview_jpeg_path);
    }

    public function test_staging_upload_requires_alt_text_and_copyright(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('articles.media.staging.store'), [
                'file' => $this->makeTestImage(),
                'staging_token' => (string) Str::uuid(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['alt_text', 'copyright']);
    }

    public function test_staging_media_can_be_served_by_owner(): void
    {
        $user = User::factory()->create();
        $stagingToken = (string) Str::uuid();

        $upload = $this->actingAs($user)->postJson(route('articles.media.staging.store'), [
            'file' => $this->makeTestImage(),
            'alt_text' => 'Alt',
            'copyright' => 'Copyright',
            'staging_token' => $stagingToken,
        ]);

        $mediaId = $upload->json('data.id');

        $this->actingAs($user)
            ->get(route('articles.media.staging.file', [
                'media' => $mediaId,
                'variant' => 'preview-jpeg',
            ]))
            ->assertOk();
    }

    public function test_other_user_cannot_access_staging_media(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $stagingToken = (string) Str::uuid();

        $upload = $this->actingAs($owner)->postJson(route('articles.media.staging.store'), [
            'file' => $this->makeTestImage(),
            'alt_text' => 'Alt',
            'copyright' => 'Copyright',
            'staging_token' => $stagingToken,
        ]);

        $mediaId = $upload->json('data.id');

        $this->actingAs($other)
            ->get(route('articles.media.staging.file', [
                'media' => $mediaId,
                'variant' => 'preview-jpeg',
            ]))
            ->assertForbidden();
    }

    public function test_article_store_claims_staging_media(): void
    {
        $user = User::factory()->create();
        $stagingToken = (string) Str::uuid();

        $upload = $this->actingAs($user)->postJson(route('articles.media.staging.store'), [
            'file' => $this->makeTestImage(),
            'alt_text' => 'Alt',
            'copyright' => 'Copyright',
            'staging_token' => $stagingToken,
        ]);

        $mediaId = $upload->json('data.id');
        $stagingMedia = ArticleMedia::query()->findOrFail($mediaId);
        $stagingBase = "articles/staging/{$stagingToken}";

        Storage::disk('local')->assertExists($stagingMedia->original_path);

        $response = $this->actingAs($user)->post(route('articles.store'), [
            'title' => 'Artikel mit Bild',
            'content' => $this->tipTapContentWithImage($mediaId),
            'staging_token' => $stagingToken,
        ]);

        $response->assertRedirect();

        $article = Article::query()->where('title', 'Artikel mit Bild')->firstOrFail();

        $this->assertDatabaseHas('article_media', [
            'id' => $mediaId,
            'article_id' => $article->id,
            'staging_token' => null,
        ]);

        $claimedMedia = ArticleMedia::query()->findOrFail($mediaId);
        $this->assertStringStartsWith("articles/{$article->id}/", $claimedMedia->original_path);
        Storage::disk('local')->assertExists($claimedMedia->original_path);
        Storage::disk('local')->assertMissing($stagingMedia->original_path);
        $this->assertFalse(Storage::disk('local')->exists($stagingBase));
    }

    public function test_user_can_upload_media_for_existing_article(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create();

        $response = $this->actingAs($user)->postJson(route('articles.media.store', $article), [
            'file' => $this->makeTestImage(),
            'alt_text' => 'Alt',
            'copyright' => 'Copyright',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.article_id', $article->id);

        $mediaId = $response->json('data.id');
        $media = ArticleMedia::query()->findOrFail($mediaId);

        Storage::disk('local')->assertExists($media->original_path);
        $this->assertStringStartsWith("articles/{$article->id}/", $media->original_path);
    }

    public function test_preview_is_scaled_down_for_large_images(): void
    {
        $user = User::factory()->create();
        $stagingToken = (string) Str::uuid();

        $upload = $this->actingAs($user)->postJson(route('articles.media.staging.store'), [
            'file' => $this->makeTestImage(2400, 1600),
            'alt_text' => 'Alt',
            'copyright' => 'Copyright',
            'staging_token' => $stagingToken,
        ]);

        $media = ArticleMedia::query()->findOrFail($upload->json('data.id'));
        $manager = new ImageManager(new Driver);
        $preview = $manager->read(Storage::disk('local')->path($media->preview_jpeg_path));

        $this->assertSame(2400, $media->width);
        $this->assertLessThanOrEqual(config('article-media.preview_max_width'), $preview->width());
    }

    public function test_media_cannot_be_deleted_when_referenced_in_content(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create([
            'content' => $this->tipTapContent(),
        ]);

        $upload = $this->actingAs($user)->postJson(route('articles.media.store', $article), [
            'file' => $this->makeTestImage(),
            'alt_text' => 'Alt',
            'copyright' => 'Copyright',
        ]);

        $mediaId = $upload->json('data.id');

        $article->update([
            'content' => $this->tipTapContentWithImage($mediaId),
        ]);

        $this->actingAs($user)
            ->deleteJson(route('articles.media.destroy', [$article, $mediaId]))
            ->assertUnprocessable();

        $this->assertDatabaseHas('article_media', ['id' => $mediaId]);
    }

    public function test_media_can_be_deleted_when_not_referenced(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create();

        $upload = $this->actingAs($user)->postJson(route('articles.media.store', $article), [
            'file' => $this->makeTestImage(),
            'alt_text' => 'Alt',
            'copyright' => 'Copyright',
        ]);

        $mediaId = $upload->json('data.id');
        $media = ArticleMedia::query()->findOrFail($mediaId);

        $this->actingAs($user)
            ->deleteJson(route('articles.media.destroy', [$article, $mediaId]))
            ->assertNoContent();

        $this->assertDatabaseMissing('article_media', ['id' => $mediaId]);
        Storage::disk('local')->assertMissing($media->original_path);
    }

    public function test_deleting_article_removes_media_storage_directory(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create();

        $upload = $this->actingAs($user)->postJson(route('articles.media.store', $article), [
            'file' => $this->makeTestImage(),
            'alt_text' => 'Alt',
            'copyright' => 'Copyright',
        ]);

        $media = ArticleMedia::query()->findOrFail($upload->json('data.id'));
        Storage::disk('local')->assertExists($media->original_path);

        $this->actingAs($user)
            ->delete(route('articles.destroy', $article))
            ->assertRedirect(route('articles.index'));

        $this->assertFalse(Storage::disk('local')->exists("articles/{$article->id}"));
        $this->assertDatabaseMissing('article_media', ['id' => $media->id]);
    }

    public function test_user_can_update_media_metadata(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->for($user, 'owner')->create();

        $upload = $this->actingAs($user)->postJson(route('articles.media.store', $article), [
            'file' => $this->makeTestImage(),
            'alt_text' => 'Alt',
            'copyright' => 'Copyright',
        ]);

        $mediaId = $upload->json('data.id');

        $this->actingAs($user)
            ->patchJson(route('articles.media.update', [$article, $mediaId]), [
                'alt_text' => 'Neuer Alt-Text',
                'copyright' => 'Neues Copyright',
                'caption' => 'Neue Caption',
            ])
            ->assertOk()
            ->assertJsonPath('data.alt_text', 'Neuer Alt-Text')
            ->assertJsonPath('data.caption', 'Neue Caption');
    }
}
