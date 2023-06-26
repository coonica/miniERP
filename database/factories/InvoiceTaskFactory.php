<?php

namespace Database\Factories;

use App\Models\InvoiceTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceTask>
 */
class InvoiceTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $invoiceTaskNames = [
            'мойка',
            'пылесос',
            'салон',
            'шины',
            'химчистка',
        ];

        if (InvoiceTask::count() == 0) {
            return [
                'invoice_id' => mt_rand(1, 5),
                'note' => fake()->sentence(),
//                'tag' => '#' . fake()->unique()->$invoiceTaskNames[mt_rand(0, count($invoiceTaskNames) - 1)],
                'tag' => '#' . $invoiceTaskNames[fake()->unique()->numberBetween(0, count($invoiceTaskNames) - 1)],
            ];
        }

        return [];
    }
}
