<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Client::count() == 0) {
            for ($i = 1; $i <= 5; $i++ ){
                Client::create([
                    'name' => 'Client '.$i,
                ]);
            }
        }
    }
}
