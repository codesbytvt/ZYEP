<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionPackage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    private $razorpayId;
    private $razorpayKey;

    public function __construct()
    {
        $this->razorpayId = config('services.razorpay.key');
        $this->razorpayKey = config('services.razorpay.secret');
    }

    /**
     * Create a Razorpay Order for a Subscription Package.
     */
    public function createOrder(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:subscription_packages,id',
            'provider_id' => 'nullable|exists:mproviders,id',
        ]);

        if ($request->provider_id) {
            $owned = Provider::where('id', $request->provider_id)
                ->where('user_id', $request->user()->id)
                ->exists();

            if (!$owned) {
                return response()->json(['message' => 'This provider profile does not belong to you.'], 403);
            }
        }

        try {
            $package = SubscriptionPackage::findOrFail($request->package_id);
            $amount = $package->price;

            $api = new Api($this->razorpayId, $this->razorpayKey);

            $orderData = [
                'receipt'         => 'rcpt_' . time(),
                'amount'          => $amount * 100, 
                'currency'        => 'INR',
                'payment_capture' => 1
            ];

            $razorpayOrder = $api->order->create($orderData);

            $payment = Payment::create([
                'user_id' => $request->user()->id,
                'amount' => $amount,
                'order_id' => $razorpayOrder['id'],
                'status' => 'pending'
            ]);

            // Create a pending subscription record to track the link
            Subscription::create([
                'user_id' => $request->user()->id,
                'package_id' => $package->id,
                'provider_id' => $request->provider_id,
                'status' => 'pending',
                'payment_id' => $razorpayOrder['id']
            ]);

            return response()->json([
                'order_id' => $razorpayOrder['id'],
                'amount' => $amount,
                'key' => $this->razorpayId,
                'user' => [
                    'name' => $request->user()->name,
                    'phone' => $request->user()->phone,
                    'email' => $request->user()->email
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Razorpay Order Error: ' . $e->getMessage());
            return response()->json(['message' => 'Could not create order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Verify Payment Signature and Activate Subscription.
     */
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'razorpay_payment_id' => 'required',
            'razorpay_order_id' => 'required',
            'razorpay_signature' => 'required',
        ]);

        try {
            $api = new Api($this->razorpayId, $this->razorpayKey);

            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature
            ];

            $api->utility->verifyPaymentSignature($attributes);

            DB::transaction(function () use ($request) {
                // Update Payment
                Payment::where('order_id', $request->razorpay_order_id)
                    ->where('user_id', $request->user()->id)
                    ->update([
                        'payment_id' => $request->razorpay_payment_id,
                        'status' => 'success'
                    ]);

                // Activate Subscription
                $sub = Subscription::where('payment_id', $request->razorpay_order_id)
                    ->where('user_id', $request->user()->id)
                    ->first();
                if ($sub) {
                    $package = $sub->package;
                    $startsAt = Carbon::now();
                    $expiresAt = null;

                    if ($package->interval === 'monthly') {
                        $expiresAt = $startsAt->copy()->addMonth();
                    } elseif ($package->interval === 'yearly') {
                        $expiresAt = $startsAt->copy()->addYear();
                    }

                    $sub->update([
                        'status' => 'active',
                        'starts_at' => $startsAt,
                        'expires_at' => $expiresAt,
                        'payment_id' => $request->razorpay_payment_id
                    ]);

                    // Auto-approve provider if policy is 'paid only'
                    if (Setting::get('provider_auto_approval') === 'paid only' && $sub->provider_id) {
                        DB::table('mproviders')->where('id', $sub->provider_id)->update(['status' => 1]);
                    }
                }
            });

            return response()->json(['message' => 'Subscription activated successfully']);

        } catch (\Exception $e) {
            Log::error('Razorpay Verification Error: ' . $e->getMessage());
            return response()->json(['message' => 'Payment verification failed'], 400);
        }
    }
}
