<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Admins\Database\Seeders\AdminsDatabaseSeeder;
use Modules\Settings\Database\Seeders\LocationSeeder;
use Modules\Settings\Database\Seeders\SettingsDatabaseSeeder;
use Modules\Tenancy\Database\Seeders\TenancyDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminsDatabaseSeeder::class,
            SettingsDatabaseSeeder::class,
            LocationSeeder::class,
            TenancyDatabaseSeeder::class,
            DocumentationSeeder::class,
            PlatformDocumentationSeeder::class,
        ]);
    }
}
