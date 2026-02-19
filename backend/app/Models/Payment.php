<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'receipt_number',
        'amount_paid',
        'payment_method',
        'paid_at',
        'created_by',
        'notes',
    ];

    protected $appends = ['receipt_url', 'receipt_pdf_url'];

    protected function casts(): array
    {
        return [
            'amount_paid'  => 'decimal:2',
            'paid_at'      => 'datetime',
        ];
    }

    public function getReceiptUrlAttribute(): string
    {
        return \Illuminate\Support\Facades\URL::signedRoute('payments.receipt', ['payment' => $this->id]);
    }

    public function getReceiptPdfUrlAttribute(): string
    {
        return \Illuminate\Support\Facades\URL::signedRoute('payments.receipt.pdf', ['payment' => $this->id]);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate a unique receipt number.
     */
    public static function generateReceiptNumber(): string
    {
        do {
            $candidate = 'RCP-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::query()->where('receipt_number', $candidate)->exists());

        return $candidate;
    }
}
