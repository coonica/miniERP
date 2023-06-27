<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\Invoice;
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
        if (Invoice::all()->count() == 0) {
            Invoice::factory(5)->create();
        }
    }
}
