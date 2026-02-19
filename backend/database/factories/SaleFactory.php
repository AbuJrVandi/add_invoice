<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $salePrice = $this->faker->randomFloat(2, 100, 5000);
        $costPrice = $salePrice * $this->faker->randomFloat(2, 0.4, 0.8); // 40-80% of sale price
        $profit = $salePrice - $costPrice;

        return [
            'invoice_id' => Invoice::factory(),
            'payment_id' => Payment::factory(),
            'total_cost_price' => $costPrice,
            'total_sale_price' => $salePrice,
            'profit' => $profit,
        ];
    }
}
