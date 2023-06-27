<?php

namespace Database\Factories;

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
        return [
            'invoice_id' => rand(1, 5),
            'note' => fake()->text,
            'fix_price' => fake()->buildingNumber,
            'tag' => '#' . fake()->word()
        ];
    }
}
