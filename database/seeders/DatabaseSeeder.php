<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Jalankan seeder utama basis data.
     */
    public function run(): void
    {
        $this->call([
            PengaturanSeeder::class,
            AdminSeeder::class,
        ]);
    }
}
