<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoicePdfSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'issuer_name',
        'logo_path',
        'signature_path',
    ];
}
