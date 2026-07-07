<?php

namespace Database\Factories;

use App\Enums\ItemType;
use App\Models\Item;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * The default item is a root-level item (no parent) of a random non-Custom
     * type, with an empty data payload. Use the type-specific state methods
     * (note(), todo(), ...) to populate the matching JSON shape.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = array_values(array_filter(
            ItemType::cases(),
            static fn (ItemType $t) => $t !== ItemType::Custom,
        ));
        $type = fake()->randomElement($types);

        return [
            'workspace_id' => Workspace::factory(),
            'parent_id'    => null,
            'type'         => $type,
            'title'        => $type->label(),
            'data'         => [],
            'sort_order'   => 0,
        ];
    }

    /**
     * Bind the item to a specific workspace (root-level).
     */
    public function forWorkspace(Workspace $workspace): static
    {
        return $this->state(fn (array $attributes) => [
            'workspace_id' => $workspace->id,
            'parent_id'    => null,
        ]);
    }

    /**
     * Bind the item as a child of an existing parent (inherits workspace).
     */
    public function childOf(Item $parent, ?ItemType $type = null): static
    {
        return $this->state(function (array $attributes) use ($parent, $type): array {
            $resolvedType = $type ?? $parent->type;

            return [
                'workspace_id' => $parent->workspace_id,
                'parent_id'    => $parent->id,
                'type'         => $resolvedType,
                'title'        => $resolvedType->label(),
            ];
        });
    }

    /**
     * Force a specific ItemType (no DB enum, just sets the discriminator).
     */
    public function ofType(ItemType $type): static
    {
        return $this->state([
            'type'  => $type,
            'title' => $type->label(),
        ]);
    }

    public function note(): static
    {
        return $this->ofType(ItemType::Note)->state([
            'data' => [
                'body'   => fake()->paragraph(),
                'format' => 'markdown',
            ],
        ]);
    }

    public function todo(): static
    {
        $priorities = ['low', 'medium', 'high'];

        return $this->ofType(ItemType::Todo)->state([
            'data' => [
                'due_at'    => fake()->dateTimeThisMonth()->format('Y-m-d\TH:i:s\Z'),
                'completed' => fake()->boolean(40),
                'priority'  => fake()->randomElement($priorities),
            ],
        ]);
    }

    public function spreadsheet(): static
    {
        $columns = ['A', 'B', 'C'];
        $rows    = array_map(
            static fn (int $i) => [
                (string) fake()->randomNumber(2),
                fake()->word(),
                (string) fake()->randomNumber(3),
            ],
            range(1, 4),
        );

        return $this->ofType(ItemType::Spreadsheet)->state([
            'data' => [
                'columns' => $columns,
                'rows'    => $rows,
            ],
        ]);
    }

    public function taxFiling(): static
    {
        $lines = array_map(
            static fn (int $i) => [
                'label'  => fake()->words(2, true),
                'amount' => fake()->randomFloat(2, 10, 5000),
            ],
            range(1, 3),
        );

        return $this->ofType(ItemType::TaxFiling)->state([
            'data' => [
                'year'         => fake()->numberBetween(2018, 2025),
                'jurisdiction' => fake()->countryCode(),
                'lines'        => $lines,
            ],
        ]);
    }

    public function event(): static
    {
        $starts = Carbon::instance(fake()->dateTimeThisMonth());

        return $this->ofType(ItemType::Event)->state([
            'data' => [
                'starts_at' => $starts->format('Y-m-d\TH:i:s\Z'),
                'ends_at'   => $starts->copy()->addHours(2)->format('Y-m-d\TH:i:s\Z'),
                'rrule'     => fake()->boolean(30) ? 'FREQ=WEEKLY;BYDAY=MO' : null,
                'location'  => fake()->boolean(50) ? fake()->city() : null,
            ],
        ]);
    }

    public function document(): static
    {
        return $this->ofType(ItemType::Document)->state([
            'data' => [
                'file_path' => fake()->filePath(),
                'mime'      => fake()->mimeType(),
                'size'      => fake()->randomNumber(5),
                'checksum'  => fake()->sha256(),
            ],
        ]);
    }

    public function custom(): static
    {
        return $this->ofType(ItemType::Custom)->state([
            'data' => [
                'arbitrary' => fake()->words(3, true),
            ],
        ]);
    }
}
