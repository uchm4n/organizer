<?php

namespace Database\Seeders;

use App\Enums\ItemType;
use App\Enums\Role;
use App\Models\Item;
use App\Models\User;
use App\Models\Workspace;
use Database\Factories\ItemFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use LogicException;

/**
 * Seeds a workspace with a mixed item tree for an existing user or a fallback admin.
 *
 * Opt-in only — NOT called from DatabaseSeeder. Run via:
 *   php artisan db:seed --class=OrganizerDummyDataSeeder
 */
class OrganizerDummyDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = $this->resolveUser();

        $workspace = Workspace::factory()->forUser($user)->create([
            'name' => 'Workspace',
        ]);

        $this->seedType($workspace, ItemType::Note, 3, [0 => 1, 1 => 1]);
        $this->seedType($workspace, ItemType::Todo, 5, [4 => 3]);
        $this->seedType($workspace, ItemType::Spreadsheet, 1, [0 => 2]);
        $this->seedType($workspace, ItemType::TaxFiling, 2);
        $this->seedType($workspace, ItemType::Event, 3);
        $this->seedType($workspace, ItemType::Document, 2);
        $this->seedType($workspace, ItemType::Custom, 2);
    }

    private function resolveUser(): User
    {
        return User::query()->where('role', Role::Admin->value)->first()
            ?? User::query()->first()
            ?? User::factory()->admin()->create();
    }

    /**
     * @param  array<int, int>  $childrenPerRoot
     * @return Collection<int, Item>
     */
    private function seedType(
        Workspace $workspace,
        ItemType $type,
        int $rootCount,
        array $childrenPerRoot = [],
    ): Collection {
        $roots = $this
            ->factoryForType($type)
            ->forWorkspace($workspace)
            ->count($rootCount)
            ->sequence(fn (Sequence $sequence): array => [
                'title' => $type->label(),
            ])
            ->create();

        foreach ($childrenPerRoot as $rootIndex => $childCount) {
            $parent = $roots->get($rootIndex);

            if (! $parent instanceof Item) {
                throw new LogicException("Unable to seed children for {$type->name} root index {$rootIndex}.");
            }

            $this
                ->factoryForType($type)
                ->childOf($parent, $type)
                ->count($childCount)
                ->sequence(fn (Sequence $sequence): array => [
                    'title' => $type->label(),
                ])
                ->create();
        }

        return $roots;
    }

    private function factoryForType(ItemType $type): ItemFactory
    {
        return match ($type) {
            ItemType::Note        => Item::factory()->note(),
            ItemType::Todo        => Item::factory()->todo(),
            ItemType::Spreadsheet => Item::factory()->spreadsheet(),
            ItemType::TaxFiling   => Item::factory()->taxFiling(),
            ItemType::Event       => Item::factory()->event(),
            ItemType::Document    => Item::factory()->document(),
            ItemType::Custom      => Item::factory()->custom(),
        };
    }
}
