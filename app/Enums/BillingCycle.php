<?php

namespace App\Enums;

enum BillingCycle: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
}