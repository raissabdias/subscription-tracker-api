<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = \App\Models\User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@doe.com',
            'password' => bcrypt('password')
        ]);

        \App\Models\Subscription::factory(10)->create([
            'user_id' => $user->id
        ]);
    }
}
