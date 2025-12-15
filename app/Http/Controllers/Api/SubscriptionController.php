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
     * Store a newly created resource in storage. (POST)
     */
    public function store(StoreSubscriptionRequest $request)
    {
        $data = $request->validated();

        // Propriedadas mais amigáveis 
        $data['price_in_cents'] = (int) round($request->price * 100);
        $data['next_billing_date'] = $request->next_payment;

        $subscription = Subscription::create($data);

        return new SubscriptionResource($subscription);
    }

    /**
     * Display the specified resource. (GET)
     */
    public function show(Subscription $subscription)
    {
        return new SubscriptionResource($subscription);
    }

    /**
     * Update the specified resource in storage. (PUT/PATCH)
     */
    public function update(StoreSubscriptionRequest $request, Subscription $subscription)
    {
        $data = $request->validated();

        // Converter preços para centavos
        if ($request->has('price')) {
            $data['price_in_cents'] = (int) (int) round($request->price * 100);
        }

        if ($request->has('next_payment')) {
            $data['next_billing_date'] = $request->next_payment;
        }

        $subscription->update($data);

        return new SubscriptionResource($subscription);
    }

    /**
     * Remove the specified resource from storage. (DELETE)
     */
    public function destroy(Subscription $subscription)
    {
        $subscription->delete();

        return response()->noContent();
    }
}
