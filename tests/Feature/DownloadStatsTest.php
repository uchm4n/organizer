<?php

use App\Models\DownloadLog;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('admin can list all download stats', function () {
    DownloadLog::create([
        'filename' => 'a.pdf', 'ip' => '1.1.1.1', 'user_agent' => 'Chrome',
    ]);
    DownloadLog::create([
        'filename' => 'b.pdf', 'ip' => '2.2.2.2', 'user_agent' => 'Safari',
    ]);
    DownloadLog::create([
        'filename' => 'b.pdf', 'ip' => '3.3.3.3', 'user_agent' => 'Firefox',
    ]);

    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this->getJson(route('api.v1.download-stats.index'))
        ->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.filename', 'b.pdf')
        ->assertJsonPath('data.0.download_count', 2)
        ->assertJsonPath('data.1.filename', 'a.pdf')
        ->assertJsonPath('data.1.download_count', 1);
});

test('admin can filter stats by filename', function () {
    DownloadLog::create([
        'filename' => 'a.pdf', 'ip' => '1.1.1.1', 'user_agent' => 'Chrome',
    ]);
    DownloadLog::create([
        'filename' => 'b.pdf', 'ip' => '2.2.2.2', 'user_agent' => 'Safari',
    ]);

    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this->getJson(route('api.v1.download-stats.index').'?filename=a.pdf')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.filename', 'a.pdf')
        ->assertJsonPath('data.0.download_count', 1);
});

test('response contains correct stats shape', function () {
    DownloadLog::create([
        'filename' => 'report.pdf', 'ip' => '1.1.1.1', 'user_agent' => 'Chrome',
        'referer'  => 'https://example.com',
    ]);
    DownloadLog::create([
        'filename' => 'report.pdf', 'ip' => '2.2.2.2', 'user_agent' => 'Firefox',
    ]);

    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this->getJson(route('api.v1.download-stats.index').'?filename=report.pdf')
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [[
                'filename',
                'download_count',
                'unique_ips',
                'first_downloaded_at',
                'last_downloaded_at',
                'last_referer',
            ]],
        ])
        ->assertJsonPath('data.0.filename', 'report.pdf')
        ->assertJsonPath('data.0.download_count', 2)
        ->assertJsonPath('data.0.unique_ips', 2);
});

test('empty state returns empty collection', function () {
    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this->getJson(route('api.v1.download-stats.index'))
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

test('non-admin user receives 403', function () {
    Sanctum::actingAs(User::factory()->create(), ['*']);

    $this->getJson(route('api.v1.download-stats.index'))
        ->assertForbidden()
        ->assertJsonPath('status', 403);
});

test('unauthenticated request receives 401', function () {
    $this->getJson(route('api.v1.download-stats.index'))
        ->assertUnauthorized();
});

test('stats are sorted by download count descending', function () {
    DownloadLog::create([
        'filename' => 'rare.pdf', 'ip' => '1.1.1.1', 'user_agent' => 'Chrome',
    ]);
    DownloadLog::create([
        'filename' => 'popular.pdf', 'ip' => '1.1.1.1', 'user_agent' => 'Chrome',
    ]);
    DownloadLog::create([
        'filename' => 'popular.pdf', 'ip' => '2.2.2.2', 'user_agent' => 'Safari',
    ]);
    DownloadLog::create([
        'filename' => 'popular.pdf', 'ip' => '3.3.3.3', 'user_agent' => 'Firefox',
    ]);

    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this->getJson(route('api.v1.download-stats.index'))
        ->assertSuccessful()
        ->assertJsonPath('data.0.filename', 'popular.pdf')
        ->assertJsonPath('data.0.download_count', 3)
        ->assertJsonPath('data.1.filename', 'rare.pdf')
        ->assertJsonPath('data.1.download_count', 1);
});

test('filtering by non-existent filename returns empty', function () {
    DownloadLog::create([
        'filename' => 'a.pdf', 'ip' => '1.1.1.1', 'user_agent' => 'Chrome',
    ]);

    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this->getJson(route('api.v1.download-stats.index').'?filename=nonexistent.pdf')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});
