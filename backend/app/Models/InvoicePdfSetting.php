<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoicePdfSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'company_address',
        'company_phone',
        'company_email',
        'company_website',
        'tax_id',
        'currency_code',
        'contact_person',
        'issuer_name',
        'payment_instructions',
        'terms_conditions',
        'logo_path',
        'signature_path',
        'stamp_path',
    ];
}
