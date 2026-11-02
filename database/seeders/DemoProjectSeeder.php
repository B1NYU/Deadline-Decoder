<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoProjectSeeder extends Seeder
{
    private const DEMO_EMAIL = 'demo@deadline-decoder.test';

    /**
     * Seed a clearly labelled DEMO account and projects for local use only.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('Seeder demo tidak dijalankan di production.');

            return;
        }

        $user = User::query()->where('email', self::DEMO_EMAIL)->first()
            ?? User::factory()->create([
                'name' => 'Pengguna Demo',
                'email' => self::DEMO_EMAIL,
            ]);

        if ($user->projects()->exists()) {
            return;
        }

        Project::factory()->for($user)->create([
            'title' => '[DEMO] Proyek Basis Data',
            'description' => 'Data demo untuk mencoba tampilan. Bukan instruksi dosen yang sebenarnya.',
        ]);

        Project::factory()->for($user)->create([
            'title' => '[DEMO] Makalah Etika Profesi',
            'description' => 'Data demo untuk mencoba tampilan. Bukan instruksi dosen yang sebenarnya.',
        ]);
    }
}
