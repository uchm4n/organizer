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
        $types = array_filter(
            ItemType::cases(),
            static fn (ItemType $t) => $t !== ItemType::Custom,
        );

        return [
            'workspace_id' => Workspace::factory(),
            'parent_id'    => null,
            'type'         => fake()->randomElement($types),
            'title'        => fake()->words(3, true),
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
        return $this->state(fn (array $attributes) => [
            'workspace_id' => $parent->workspace_id,
            'parent_id'    => $parent->id,
            'type'         => $type ?? $parent->type,
        ]);
    }

    /**
     * Force a specific ItemType (no DB enum, just sets the discriminator).
     */
    public function ofType(ItemType $type): static
    {
        return $this->state(['type' => $type]);
    }

    public function note(): static
    {
        return $this->state([
            'type' => ItemType::Note,
            'data' => [
                'body'   => fake()->paragraph(),
                'format' => 'markdown',
            ],
        ]);
    }

    public function todo(): static
    {
        $priorities = ['low', 'medium', 'high'];

        return $this->state([
            'type' => ItemType::Todo,
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

        return $this->state([
            'type' => ItemType::Spreadsheet,
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

        return $this->state([
            'type' => ItemType::TaxFiling,
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

        return $this->state([
            'type' => ItemType::Event,
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
        return $this->state([
            'type' => ItemType::Document,
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
        return $this->state([
            'type' => ItemType::Custom,
            'data' => [
                'arbitrary' => fake()->words(3, true),
            ],
        ]);
    }
}
