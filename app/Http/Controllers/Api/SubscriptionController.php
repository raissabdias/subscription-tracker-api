<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription; 
use App\Http\Resources\SubscriptionResource;
use App\Http\Requests\StoreSubscriptionRequest;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subscriptions = Subscription::all();
        
        return SubscriptionResource::collection($subscriptions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubscriptionRequest $request)
    {
        $data = $request->validated();

        // Propriedadas mais amigáveis 
        $data['price_in_cents'] = (int) ($request->price * 100);
        $data['next_billing_date'] = $request->next_payment;

        $subscription = Subscription::create($data);

        return new SubscriptionResource($subscription);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
