<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (Project::count() == 0) {
            $clients_id = Client::all()->pluck('id')->toArray();
            for ($i = 1; $i <= 10; $i++ ){
                Project::create([
                    'name' => 'Project '.$i,
                    'client_id' => $clients_id[array_rand($clients_id)],
                ]);
            }
        }
    }
}
