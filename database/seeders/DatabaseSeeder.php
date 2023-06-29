<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Console\Commands\ConnectTrello;
use App\Models\Booker;
use App\Models\Invoice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UserSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(BookerSeeder::class);
        $this->call(ProjectSeeder::class);
        $this->call(StatusSeeder::class);
        Artisan::call('sync:trello', ['target' => 'c']);
        $this->call(InvoiceSeeder::class);
        $this->call(InvoiceTaskSeeder::class);

    }
}
