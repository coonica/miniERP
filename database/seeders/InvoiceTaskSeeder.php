<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\InvoiceTask;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use PHPUnit\Framework\MockObject\Invocation;

class InvoiceTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(InvoiceTask::count() == 0) {
            $invoices_id = Invoice::all()->pluck('id')->toArray();
            for ($i = 1; $i <= 10; $i++){
                InvoiceTask::create([
                    'invoice_id' => $invoices_id[array_rand($invoices_id)],
                    'note' => 'InvoiceTask '.$i,
                    'fix_price' => rand(10, 30),
                    'tag' => '#tag '.$i
                ]);
            }
        }
    }
}
