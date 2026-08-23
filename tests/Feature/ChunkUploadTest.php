<?php

namespace Tests\Feature;

use App\Actions\CreateOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChunkUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = app(CreateOrganization::class)->handle('Org', 'Admin', '09120000001', 'secret123');
        $this->actingAs($admin);
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_single_chunk_upload_stores_file_and_returns_path(): void
    {
        $res = $this->post('/uploads/chunk', [
            'resumableChunkNumber' => 1,
            'resumableTotalChunks' => 1,
            'resumableIdentifier' => 'abc-123',
            'resumableFilename' => 'receipt.png',
            'resumableTotalSize' => 2048,
            'folder' => 'receipts',
            'file' => UploadedFile::fake()->create('receipt.png', 2, 'image/png'),
        ]);

        $res->assertOk()->assertJson(['done' => true]);
        $path = $res->json('path');
        $this->assertStringStartsWith('receipts/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_rejects_disallowed_folder(): void
    {
        $this->post('/uploads/chunk', [
            'resumableChunkNumber' => 1, 'resumableTotalChunks' => 1,
            'resumableIdentifier' => 'x', 'resumableFilename' => 'a.png',
            'resumableTotalSize' => 10, 'folder' => 'secret',
            'file' => UploadedFile::fake()->create('a.png', 1),
        ])->assertStatus(422);
    }

    public function test_rejects_disallowed_extension(): void
    {
        $this->post('/uploads/chunk', [
            'resumableChunkNumber' => 1, 'resumableTotalChunks' => 1,
            'resumableIdentifier' => 'y', 'resumableFilename' => 'a.exe',
            'resumableTotalSize' => 10, 'folder' => 'receipts',
            'file' => UploadedFile::fake()->create('a.exe', 1),
        ])->assertStatus(422);
    }

    public function test_test_endpoint_reports_missing_chunk(): void
    {
        $this->get('/uploads/chunk?resumableChunkNumber=1&resumableIdentifier=none')
            ->assertNoContent(); // 204 → client should upload it
    }

    public function test_upload_requires_auth(): void
    {
        auth()->logout();
        $this->post('/uploads/chunk', [])->assertRedirect('/login');
    }
}
