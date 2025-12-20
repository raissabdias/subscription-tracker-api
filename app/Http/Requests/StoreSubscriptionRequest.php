<?php

namespace App\Http\Requests;

use App\Enums\BillingCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:1',
            'category' => ['nullable', 'string', 'max:50'],
            'billing_cycle' => ['required', Rule::enum(BillingCycle::class)],
            'next_payment' => 'required|date',
            'status' => 'nullable|in:active,inactive',
            'notes' => 'nullable|string'
        ];
    }
}
