<?php

test('the application bootstrap is free of inertia web integration', function () {
    $bootstrap = file_get_contents(base_path('bootstrap/app.php'));

    expect($bootstrap)
        ->not->toContain('HandleInertiaRequests')
        ->not->toContain('AddLinkHeadersForPreloadedAssets');

    expect(file_exists(app_path('Http/Middleware/HandleInertiaRequests.php')))->toBeFalse();
    expect(file_exists(config_path('inertia.php')))->toBeFalse();
    expect(file_exists(resource_path('views/app.blade.php')))->toBeFalse();
});

test('the repository no longer ships node based frontend tooling', function () {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
    $lock = json_decode(file_get_contents(base_path('composer.lock')), true, flags: JSON_THROW_ON_ERROR);
    $boost = json_decode(file_get_contents(base_path('boost.json')), true, flags: JSON_THROW_ON_ERROR);
    $envExample = file_get_contents(base_path('.env.example'));
    $agentsGuidance = file_get_contents(base_path('AGENTS.md'));
    // $claudeGuidance = file_get_contents(base_path('CLAUDE.md'));
    $testsWorkflow = file_get_contents(base_path('.github/workflows/tests.yml'));
    $lintWorkflow = file_get_contents(base_path('.github/workflows/lint.yml'));

    expect(array_key_exists('inertiajs/inertia-laravel', $composer['require']))->toBeFalse();
    expect(array_key_exists('laravel/wayfinder', $composer['require']))->toBeFalse();
    expect(array_key_exists('dev', $composer['scripts']))->toBeFalse();
    expect(collect($composer['scripts']['setup'])->contains('npm install'))->toBeFalse();
    expect(collect($composer['scripts']['setup'])->contains('npm run build'))->toBeFalse();
    expect(collect($composer['scripts']['ci:check'])->contains('npm run lint:check'))->toBeFalse();
    expect(collect($composer['scripts']['ci:check'])->contains('npm run format:check'))->toBeFalse();
    expect(collect($lock['packages'])->pluck('name')->contains('inertiajs/inertia-laravel'))->toBeFalse();
    expect(collect($lock['packages'])->pluck('name')->contains('laravel/wayfinder'))->toBeFalse();

    expect($boost['skills'])->not->toContain('wayfinder-development');
    expect($boost['skills'])->not->toContain('inertia-vue-development');
    expect($boost['skills'])->not->toContain('tailwindcss-development');

    expect($envExample)->not->toContain('VITE_APP_NAME');

    foreach ([$agentsGuidance, /*$claudeGuidance*/] as $guidance) {
        expect($guidance)
            ->not->toContain('inertiajs/inertia-laravel')
            ->not->toContain('laravel/wayfinder')
            ->not->toContain('@inertiajs/vue3')
            ->not->toContain('tailwindcss')
            ->not->toContain('vue')
            ->not->toContain('@laravel/vite-plugin-wayfinder')
            ->not->toContain('=== inertia-laravel/core rules ===')
            ->not->toContain('=== wayfinder/core rules ===')
            ->not->toContain('=== inertia-vue/core rules ===')
            ->not->toContain('npm run build')
            ->not->toContain('npm run dev')
            ->not->toContain('composer run dev');
    }

    expect(file_exists(base_path('package.json')))->toBeFalse();
    expect(file_exists(base_path('package-lock.json')))->toBeFalse();
    expect(file_exists(base_path('vite.config.ts')))->toBeFalse();
    expect(file_exists(base_path('eslint.config.js')))->toBeFalse();
    expect(file_exists(base_path('tsconfig.json')))->toBeFalse();
    expect(file_exists(base_path('pnpm-workspace.yaml')))->toBeFalse();
    expect(file_exists(base_path('.npmrc')))->toBeFalse();
    expect(file_exists(base_path('.prettierrc')))->toBeFalse();
    expect(file_exists(base_path('.prettierignore')))->toBeFalse();
    expect(file_exists(resource_path('css/app.css')))->toBeFalse();
    expect(file_exists(resource_path('js/app.ts')))->toBeFalse();
    expect(file_exists(resource_path('js/lib/utils.ts')))->toBeFalse();
    expect(file_exists(resource_path('js/pages/Welcome.vue')))->toBeFalse();
    expect(file_exists(resource_path('js/types/auth.ts')))->toBeFalse();
    expect(file_exists(resource_path('js/types/global.d.ts')))->toBeFalse();
    expect(file_exists(resource_path('js/types/index.ts')))->toBeFalse();
    expect(file_exists(resource_path('js/types/vue-shims.d.ts')))->toBeFalse();
    expect(file_exists(base_path('.agents/skills/inertia-vue-development/SKILL.md')))->toBeFalse();
    expect(file_exists(base_path('.agents/skills/tailwindcss-development/SKILL.md')))->toBeFalse();
    expect(file_exists(base_path('.agents/skills/wayfinder-development/SKILL.md')))->toBeFalse();
    expect(file_exists(base_path('.claude/skills/inertia-vue-development/SKILL.md')))->toBeFalse();
    expect(file_exists(base_path('.claude/skills/tailwindcss-development/SKILL.md')))->toBeFalse();
    expect(file_exists(base_path('.claude/skills/wayfinder-development/SKILL.md')))->toBeFalse();
    expect(file_exists(base_path('.junie/skills/inertia-vue-development/SKILL.md')))->toBeFalse();
    expect(file_exists(base_path('.junie/skills/tailwindcss-development/SKILL.md')))->toBeFalse();
    expect(file_exists(base_path('.junie/skills/wayfinder-development/SKILL.md')))->toBeFalse();
    expect(file_exists(resource_path('js')))->toBeFalse();

    expect($testsWorkflow)
        ->not->toContain("'8.3'")
        ->not->toContain("'8.4'")
        ->not->toContain('setup-node')
        ->not->toContain('npm ')
        ->not->toContain('Build Assets')
        ->toContain("php-version: ['8.5']");

    expect($lintWorkflow)
        ->not->toContain('npm ')
        ->not->toContain('Format Frontend')
        ->not->toContain('Lint Frontend')
        ->toContain("php-version: '8.5'");
});
