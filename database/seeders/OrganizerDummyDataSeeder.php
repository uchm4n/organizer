<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a single demo user with a workspace and a mixed item tree.
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
        $user = User::factory()->create([
            'name'     => 'Demo User',
            'email'    => 'demo@organizer.test',
            'password' => Hash::make('password'),
        ]);

        $workspace = Workspace::factory()->forUser($user)->create([
            'name' => 'Workspace',
        ]);

        $this->seedNotes($workspace);
        $this->seedTodos($workspace);
        $this->seedSpreadsheets($workspace);
        $this->seedTaxFilings($workspace);
        $this->seedEvents($workspace);
        $this->seedDocuments($workspace);
        $this->seedCustom($workspace);
    }

    private function seedNotes(Workspace $workspace): void
    {
        $roots = Item::factory()
            ->forWorkspace($workspace)
            ->note()
            ->count(3)
            ->create();

        $roots->take(2)->each(function (Item $note) use ($workspace): void {
            Item::factory()
                ->forWorkspace($workspace)
                ->childOf($note)
                ->note()
                ->create([
                    'title' => 'Sub-note of '.$note->title,
                ]);
        });
    }

    private function seedTodos(Workspace $workspace): void
    {
        Item::factory()
            ->forWorkspace($workspace)
            ->todo()
            ->count(4)
            ->create();

        $project = Item::factory()
            ->forWorkspace($workspace)
            ->todo()
            ->create(['title' => 'Project Alpha']);

        Item::factory()
            ->forWorkspace($workspace)
            ->childOf($project)
            ->todo()
            ->count(3)
            ->create();
    }

    private function seedSpreadsheets(Workspace $workspace): void
    {
        $root = Item::factory()
            ->forWorkspace($workspace)
            ->spreadsheet()
            ->create(['title' => 'Accounts 2026']);

        Item::factory()
            ->forWorkspace($workspace)
            ->childOf($root)
            ->spreadsheet()
            ->count(2)
            ->create();
    }

    private function seedTaxFilings(Workspace $workspace): void
    {
        Item::factory()
            ->forWorkspace($workspace)
            ->taxFiling()
            ->count(2)
            ->create();
    }

    private function seedEvents(Workspace $workspace): void
    {
        Item::factory()
            ->forWorkspace($workspace)
            ->event()
            ->count(3)
            ->create();
    }

    private function seedDocuments(Workspace $workspace): void
    {
        Item::factory()
            ->forWorkspace($workspace)
            ->document()
            ->count(2)
            ->create();
    }

    private function seedCustom(Workspace $workspace): void
    {
        Item::factory()
            ->forWorkspace($workspace)
            ->custom()
            ->count(2)
            ->create();
    }
}
