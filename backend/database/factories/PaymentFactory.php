<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'receipt_number' => 'REC-' . $this->faker->unique()->numerify('#####'),
            'amount_paid' => $this->faker->randomFloat(2, 50, 2000),
            'payment_method' => $this->faker->randomElement(['cash', 'transfer', 'mobile_money', 'card']),
            'paid_at' => $this->faker->dateTimeBetween('-60 days', 'now'),
            'created_by' => User::factory(),
            'notes' => $this->faker->sentence(),
        ];
    }
}
