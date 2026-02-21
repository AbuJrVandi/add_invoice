<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $invoiceDate = $this->faker->dateTimeBetween('-60 days', 'now');
        $dueDate = (clone $invoiceDate)->modify('+14 days');
        
        $subtotal = $this->faker->randomFloat(2, 100, 5000);
        $tax = $subtotal * 0.15; // 15% tax
        $total = $subtotal + $tax;

        return [
            'invoice_number' => 'INV-' . $this->faker->unique()->numerify('#####'),
            'customer_name' => $this->faker->company(),
            'organization' => $this->faker->company(),
            'bill_to' => $this->faker->address(),
            'ship_to' => $this->faker->address(),
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'po_number' => 'PO-' . $this->faker->numerify('#####'),
            'requested_by' => $this->faker->name(),
            'delivery_method' => $this->faker->randomElement(['pickup', 'delivery', 'courier']),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'created_by_user_id' => User::factory(),
            'status' => 'pending',
            'amount_paid' => 0,
            'balance_remaining' => $total,
            'paid_at' => null,
            'pdf_path' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'amount_paid' => $attributes['total'],
                'balance_remaining' => 0,
                'paid_at' => $this->faker->dateTimeBetween($attributes['invoice_date'], 'now'),
            ];
        });
    }

    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'due',
                'due_date' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
            ];
        });
    }
}
