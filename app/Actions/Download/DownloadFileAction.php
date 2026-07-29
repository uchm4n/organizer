<?php

namespace App\Actions\Download;

use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

class DownloadFileAction
{
    public function handle(string $filename): File
    {
        $sanitized = $this->sanitize($filename);

        $this->validateExtension($sanitized);

        $disk = Storage::disk('downloads');

        if (! $disk->exists($sanitized)) {
            abort(404);
        }

        $fullPath = $disk->path($sanitized);

        return new File($fullPath);
    }

    private function sanitize(string $filename): string
    {
        // Remove any path separators and null bytes
        $sanitized = str_replace(['/', '\\', "\0"], '', $filename);

        // Remove any leading dots (prevent hidden files and traversal)
        $sanitized = ltrim($sanitized, '.');

        if ($sanitized === '') {
            abort(404);
        }

        return $sanitized;
    }

    private function validateExtension(string $filename): void
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $allowed = config('filesystems.disks.downloads.allowed_extensions', []);

        if (! in_array($extension, $allowed, true)) {
            abort(404);
        }
    }

}
