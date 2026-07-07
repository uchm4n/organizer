<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'     => 'Personal',
            'settings' => fake()->boolean(50) ? [] : null,
        ];
    }

    /**
     * Bind the workspace to a specific user via the belongsTo relation.
     *
     * Uses Factory::for() so the foreign key is populated through Eloquent's
     * relationship resolver rather than a raw fillable assignment.
     */
    public function forUser(User $user): static
    {
        return $this->for($user, 'user');
    }
}
