<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    use HasFactory;

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PENDING   = 'pending';
    public const STATUS_DUE       = 'due';
    public const STATUS_PAID      = 'paid';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'invoice_number',
        'customer_name',
        'organization',
        'bill_to',
        'ship_to',
        'invoice_date',
        'due_date',
        'po_number',
        'requested_by',
        'delivery_method',
        'subtotal',
        'tax',
        'total',
        'pdf_path',
        'created_by_user_id',
        'status',
        'amount_paid',
        'balance_remaining',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date'      => 'date',
            'due_date'          => 'date',
            'paid_at'           => 'datetime',
            'subtotal'          => 'decimal:2',
            'tax'               => 'decimal:2',
            'total'             => 'decimal:2',
            'amount_paid'       => 'decimal:2',
            'balance_remaining' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function sale(): HasOne
    {
        return $this->hasOne(Sale::class);
    }

    /**
     * Whether this invoice has been fully paid (sale registered).
     */
    public function isFullyPaid(): bool
    {
        return in_array($this->status, [self::STATUS_PAID, self::STATUS_COMPLETED], true);
    }

    /**
     * Whether a sale record already exists (prevents duplicate sales).
     */
    public function hasSale(): bool
    {
        return $this->sale()->exists();
    }
}
