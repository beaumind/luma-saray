<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chunked, resumable file uploads for resumable.js.
 *
 * - GET  /uploads/chunk  → "test" request: 200 if the chunk is already stored
 *   (lets the client skip it and resume), 204 if not yet uploaded.
 * - POST /uploads/chunk  → stores one chunk; once every chunk has arrived the
 *   file is reassembled onto the PUBLIC disk under an allow-listed folder and
 *   the stored path/URL is returned.
 *
 * Chunks live in the private disk (storage/app/chunks) keyed by a per-upload
 * identifier; they are removed after a successful merge.
 */
class UploadController extends Controller
{
    /** Folders a file may be stored into, and their allowed extensions. */
    private const FOLDERS = [
        'receipts' => ['jpg', 'jpeg', 'png', 'pdf'],
        'expenses' => ['jpg', 'jpeg', 'png', 'pdf'],
    ];

    private const MAX_BYTES = 15 * 1024 * 1024; // 15 MB per file

    public function test(Request $request): Response
    {
        $path = $this->chunkPath($request, (int) $request->query('resumableChunkNumber'));

        return $path && Storage::disk('local')->exists($path)
            ? response('', 200)
            : response('', 204);
    }

    public function store(Request $request): Response
    {
        $folder = $this->folder($request);

        $file = $request->file('file');
        if (! $file || ! $file->isValid()) {
            return response()->json(['message' => 'فایل نامعتبر است.'], 422);
        }

        $totalSize = (int) $request->input('resumableTotalSize');
        if ($totalSize > self::MAX_BYTES) {
            return response()->json(['message' => 'حجم فایل بیش از حد مجاز است (۱۵ مگابایت).'], 422);
        }

        $ext = strtolower((string) pathinfo((string) $request->input('resumableFilename'), PATHINFO_EXTENSION));
        if (! in_array($ext, self::FOLDERS[$folder], true)) {
            return response()->json(['message' => 'نوع فایل مجاز نیست.'], 422);
        }

        $chunkNumber = (int) $request->input('resumableChunkNumber');
        $totalChunks = (int) $request->input('resumableTotalChunks');
        $identifier = $this->safeIdentifier($request);

        $file->storeAs("chunks/{$identifier}", "chunk.{$chunkNumber}", 'local');

        // Not all chunks in yet — acknowledge and wait for the rest.
        if (! $this->allChunksReceived($identifier, $totalChunks)) {
            return response()->json(['done' => false], 200);
        }

        $path = $this->assemble($identifier, $totalChunks, $folder, $ext);

        return response()->json([
            'done' => true,
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ], 200);
    }

    private function folder(Request $request): string
    {
        $folder = (string) $request->input('folder');
        abort_unless(array_key_exists($folder, self::FOLDERS), 422, 'مقصد نامعتبر است.');

        return $folder;
    }

    /** A filesystem-safe per-upload key, scoped to the current user. */
    private function safeIdentifier(Request $request): string
    {
        $raw = (string) $request->input('resumableIdentifier');

        return (string) auth()->id().'-'.preg_replace('/[^A-Za-z0-9_-]/', '', $raw);
    }

    private function chunkPath(Request $request, int $chunkNumber): ?string
    {
        $identifier = $this->safeIdentifier($request);

        return $chunkNumber > 0 ? "chunks/{$identifier}/chunk.{$chunkNumber}" : null;
    }

    private function allChunksReceived(string $identifier, int $totalChunks): bool
    {
        for ($i = 1; $i <= $totalChunks; $i++) {
            if (! Storage::disk('local')->exists("chunks/{$identifier}/chunk.{$i}")) {
                return false;
            }
        }

        return true;
    }

    private function assemble(string $identifier, int $totalChunks, string $folder, string $ext): string
    {
        $disk = Storage::disk('local');
        $public = Storage::disk('public');

        $relative = $folder.'/'.Str::random(40).'.'.$ext;
        $public->put($relative, ''); // ensure the folder/file exists on the public disk
        $absolute = $public->path($relative);
        $out = fopen($absolute, 'wb');

        for ($i = 1; $i <= $totalChunks; $i++) {
            $chunk = $disk->path("chunks/{$identifier}/chunk.{$i}");
            $in = fopen($chunk, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);

        $disk->deleteDirectory("chunks/{$identifier}");

        return $relative;
    }
}
