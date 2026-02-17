<?php

namespace App\Models;

use LogicException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'payment_id',
        'total_cost_price',
        'total_sale_price',
        'profit',
    ];

    protected function casts(): array
    {
        return [
            'total_cost_price' => 'decimal:2',
            'total_sale_price' => 'decimal:2',
            'profit'           => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Sale $sale): void {
            $immutableColumns = [
                'invoice_id',
                'payment_id',
                'total_cost_price',
                'total_sale_price',
                'profit',
            ];

            if ($sale->isDirty($immutableColumns)) {
                throw new LogicException('Sale snapshots are immutable and cannot be edited.');
            }
        });

        static::deleting(function (): void {
            throw new LogicException('Sale snapshots are immutable and cannot be deleted.');
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
