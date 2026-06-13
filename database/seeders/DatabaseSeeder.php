<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            RolesSeeder::class,
            AdminUserSeeder::class,
            PdtsMasterDataSeeder::class,
        ]);
    }
}
