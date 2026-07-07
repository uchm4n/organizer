<?php

use App\Enums\ItemType;
use App\Models\Item;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\OrganizerDummyDataSeeder;

describe('seeder shape', function () {
    beforeEach(function (): void {
        $this->seed(OrganizerDummyDataSeeder::class);
    });

    test('creates exactly one demo user and one workspace 1:1', function (): void {
        expect(User::count())->toBe(1)
            ->and(User::first()->email)->toBe('demo@organizer.test')
            ->and(Workspace::count())->toBe(1)
            ->and(Workspace::first()->user_id)->toBe(User::first()->id);
    });

    test('all items belong to the single workspace', function (): void {
        $workspaceId = Workspace::first()->id;

        Item::each(function (Item $item) use ($workspaceId): void {
            expect($item->workspace_id)->toBe($workspaceId);
        });
    });

    $expected = [
        [ItemType::Note, 5],
        [ItemType::Todo, 8],
        [ItemType::Spreadsheet, 3],
        [ItemType::TaxFiling, 2],
        [ItemType::Event, 3],
        [ItemType::Document, 2],
        [ItemType::Custom, 2],
    ];

    foreach ($expected as [$type, $count]) {
        test("{$type->name}: {$count} rows", function () use ($type, $count): void {
            expect(Item::where('type', $type)->count())->toBe($count);
        });
    }

    test('csv/enum count adds up to total item count', function (): void {
        $byType = Item::pluck('type')->countBy(fn (ItemType $t) => $t->value);

        expect($byType->sum())->toBe(Item::count());
    });

    test('sub-notes link to their parents and roots have no parent', function (): void {
        $rootNotes  = Item::where('type', ItemType::Note)->whereNull('parent_id')->get();
        $childNotes = Item::where('type', ItemType::Note)->whereNotNull('parent_id')->get();

        expect($rootNotes)->toHaveCount(3)
            ->and($childNotes)->toHaveCount(2)
            ->and($childNotes->every(fn (Item $i) => $i->parent_id !== null))->toBeTrue();
    });

    test('Project Alpha todo has exactly 3 children', function (): void {
        $parent = Item::where('title', 'Project Alpha')->first();

        expect($parent)->not->toBeNull()
            ->and($parent->children()->count())->toBe(3)
            ->and($parent->children->pluck('type'))->each->toBe(ItemType::Todo);
    });

    test('Accounts 2026 spreadsheet has exactly 2 spreadsheet children', function (): void {
        $root = Item::where('title', 'Accounts 2026')->first();

        expect($root)->not->toBeNull()
            ->and($root->children()->count())->toBe(2)
            ->and($root->children->every(fn (Item $c) => $c->type === ItemType::Spreadsheet))->toBeTrue();
    });

    test('items data payloads follow the per-type contract', function (): void {
        $todo  = Item::where('type', ItemType::Todo)->whereNull('parent_id')->first();
        $event = Item::where('type', ItemType::Event)->first();
        $doc   = Item::where('type', ItemType::Document)->first();

        expect($todo->data)->toHaveKeys(['due_at', 'completed', 'priority'])
            ->and($event->data)->toHaveKeys(['starts_at', 'ends_at', 'rrule', 'location'])
            ->and($doc->data)->toHaveKeys(['file_path', 'mime', 'size', 'checksum']);
    });
});

describe('factories in isolation', function () {
    test('workspace factory 1:1 binds via forUser state', function (): void {
        $user      = User::factory()->create();
        $workspace = Workspace::factory()->forUser($user)->create();

        expect($workspace->user_id)->toBe($user->id)
            ->and($workspace->name)->toBe('Personal');
    });

    test('item factory childOf inherits workspace from parent', function (): void {
        $user      = User::factory()->create();
        $workspace = Workspace::factory()->forUser($user)->create();
        $parent    = Item::factory()->forWorkspace($workspace)->note()->create();

        $child = Item::factory()->childOf($parent)->create();

        expect($child->workspace_id)->toBe($parent->workspace_id)
            ->and($child->parent_id)->toBe($parent->id)
            ->and($child->type)->toBe($parent->type);
    });

    test('item factory explicit custom state sets type Custom', function (): void {
        $user      = User::factory()->create();
        $workspace = Workspace::factory()->forUser($user)->create();
        $item      = Item::factory()->forWorkspace($workspace)->custom()->make();

        expect($item->type)->toBe(ItemType::Custom)
            ->and($item->data)->toHaveKey('arbitrary');
    });
});
