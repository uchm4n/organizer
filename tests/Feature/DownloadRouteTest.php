<?php

use App\Models\DownloadLog;
use Illuminate\Support\Facades\Storage;

test('download route returns 404 for missing file', function () {
    $this->get(route('downloads', 'nonexistent.txt'))
        ->assertNotFound();
});

test('download route returns 404 for path traversal attempt', function () {
    $this->get(route('downloads', '../../etc/passwd'))
        ->assertNotFound();
});

test('download route returns 404 for blocked extension', function () {
    Storage::disk('downloads')->put('test.php', '<?php echo "hacked"; ?>');

    $this->get(route('downloads', 'test.php'))
        ->assertNotFound();
});

test('download route returns 404 for executable extension', function () {
    Storage::disk('downloads')->put('script.sh', '#!/bin/bash\necho "hacked"');

    $this->get(route('downloads', 'script.sh'))
        ->assertNotFound();
});

test('download route serves allowed file type', function () {
    Storage::disk('downloads')->put('test.txt', 'Hello World');

    $this->get(route('downloads', 'test.txt'))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8');
});

test('download route serves pdf file', function () {
    Storage::disk('downloads')->put('document.pdf', '%PDF-1.4 fake pdf content');

    $this->get(route('downloads', 'document.pdf'))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('download route serves image file', function () {
    Storage::disk('downloads')->put('image.png', 'fake png content');

    $this->get(route('downloads', 'image.png'))
        ->assertSuccessful();
});

test('download route serves zip file', function () {
    Storage::disk('downloads')->put('archive.zip', 'PK fake zip content');

    $this->get(route('downloads', 'archive.zip'))
        ->assertSuccessful();
});

test('download route rejects hidden files', function () {
    Storage::disk('downloads')->put('.hidden', 'secret');

    $this->get(route('downloads', '.hidden'))
        ->assertNotFound();
});

test('download route rejects empty filename', function () {
    $this->get('/downloads/')
        ->assertNotFound();
});

test('successful download creates a download log entry', function () {
    Storage::disk('downloads')->put('tracked.pdf', 'fake pdf content');

    $this->get(route('downloads', 'tracked.pdf'))
        ->assertSuccessful();

    $log = DownloadLog::first();
    expect($log)->not->toBeNull()
        ->and($log->filename)->toBe('tracked.pdf')
        ->and($log->ip)->toBe('127.0.0.1')
        ->and($log->user_agent)->not->toBeEmpty();
});

test('404 download does not create a download log entry', function () {
    $this->get(route('downloads', 'nonexistent.txt'))
        ->assertNotFound();

    expect(DownloadLog::count())->toBe(0);
});

test('download log captures referer header', function () {
    Storage::disk('downloads')->put('refered.txt', 'content');

    $this->get(route('downloads', 'refered.txt'), [
        'HTTP_REFERER' => 'https://example.com/page',
    ])->assertSuccessful();

    $log = DownloadLog::first();
    expect($log->referer)->toBe('https://example.com/page');
});

test('download log stores null referer when header is absent', function () {
    Storage::disk('downloads')->put('no-refer.txt', 'content');

    $this->get(route('downloads', 'no-refer.txt'))
        ->assertSuccessful();

    $log = DownloadLog::first();
    expect($log->referer)->toBeNull();
});

test('download log truncates long user agent to 512 chars', function () {
    Storage::disk('downloads')->put('long-ua.txt', 'content');

    $longUA = str_repeat('A', 600);

    $this->get(route('downloads', 'long-ua.txt'), [
        'HTTP_USER_AGENT' => $longUA,
    ])->assertSuccessful();

    $log = DownloadLog::first();
    expect(strlen($log->user_agent))->toBeLessThanOrEqual(512);
});

test('download log captures filename correctly', function () {
    Storage::disk('downloads')->put('report-2026.pdf', 'pdf content');

    $this->get(route('downloads', 'report-2026.pdf'))
        ->assertSuccessful();

    $log = DownloadLog::first();
    expect($log->filename)->toBe('report-2026.pdf');
});
