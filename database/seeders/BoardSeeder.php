<?php

namespace Database\Seeders;

use App\Models\Board;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BoardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        return Board::create([
          'idBoard' => 1,
          'name' => 'test',
        ]);
    }
}
