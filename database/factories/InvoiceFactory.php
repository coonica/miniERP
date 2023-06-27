<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $invoiceNames = [
            'Мойка №2',
            'Пылесос салона',
            'Мойка стекол',
            'Обработка шин',
            'Обработка кожи салона',
        ];

        $board = Board::all()->first();

        return [
            'project_id' => rand(1, 20),
            'date' => now(),
            'name' => $invoiceNames[mt_rand(0, count($invoiceNames) - 1)],
            'idBoard' => $board->idBoard,
            'status_id' => mt_rand(1, 3)
        ];
    }
}
