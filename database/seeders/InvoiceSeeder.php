<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Status;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(Invoice::count() == 0) {
            $projects_id = Project::all()->pluck('id')->toArray();
            $statuses_id = Status::all()->pluck('id')->toArray();
            $board = Board::all()->first();
            for ($i = 1; $i <= 5; $i++ ){
                $projKeyID = array_rand($projects_id);
                Invoice::create([
                    'project_id' => $projects_id[$projKeyID],
                    'date' => now(),
                    'name' => 'Invoice '.$i,
                    'idBoard' => $board->idBoard,
                    'status_id' => $statuses_id[array_rand($statuses_id)],
                ]);
                // Удаляем элемент массива чтобы он не повторялся
                unset($projects_id[$projKeyID]);
            }
        }
    }
}
