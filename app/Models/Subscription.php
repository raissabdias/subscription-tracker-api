<?php

namespace App\Models;

use App\Enums\BillingCycle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'price_in_cents',
        'category',
        'billing_cycle',
        'next_billing_date',
        'status',
        'notes'
    ];

    protected $casts = [
        'next_billing_date' => 'date',
        'price_in_cents' => 'integer',
        'billing_cycle' => BillingCycle::class
    ];
}