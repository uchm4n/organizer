<?php

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
