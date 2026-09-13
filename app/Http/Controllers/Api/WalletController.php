<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use App\Models\Payment;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class WalletController extends Controller
{
    private $razorpayId;
    private $razorpayKey;

    public function __construct(private WalletService $walletService)
    {
        $this->razorpayId = config('services.razorpay.key');
        $this->razorpayKey = config('services.razorpay.secret');
    }

    /**
     * Current credit balance for the authenticated user.
     */
    public function balance(Request $request)
    {
        $wallet = $this->walletService->getOrCreateWallet($request->user());

        return response()->json([
            'balance_credits' => $wallet->balance_credits,
            'lifetime_purchased_credits' => $wallet->lifetime_purchased_credits,
            'lifetime_spent_credits' => $wallet->lifetime_spent_credits,
        ]);
    }

    /**
     * Create a Razorpay order for a credit package top-up.
     */
    public function createOrder(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:mcredit_packages,id',
        ]);

        try {
            $package = CreditPackage::where('is_active', true)->findOrFail($request->package_id);
            $amount = $package->price;

            $api = new Api($this->razorpayId, $this->razorpayKey);

            $orderData = [
                'receipt'         => 'rcpt_' . time(),
                'amount'          => $amount * 100,
                'currency'        => 'INR',
                'payment_capture' => 1,
            ];

            $razorpayOrder = $api->order->create($orderData);

            Payment::create([
                'user_id' => $request->user()->id,
                'credit_package_id' => $package->id,
                'amount' => $amount,
                'order_id' => $razorpayOrder['id'],
                'status' => 'pending',
            ]);

            return response()->json([
                'order_id' => $razorpayOrder['id'],
                'amount' => $amount,
                'key' => $this->razorpayId,
                'user' => [
                    'name' => $request->user()->name,
                    'phone' => $request->user()->phone,
                    'email' => $request->user()->email,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Razorpay Wallet Order Error: ' . $e->getMessage());
            return response()->json(['message' => 'Could not create order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Verify payment signature and credit the wallet.
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

            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ]);

            $payment = Payment::where('order_id', $request->razorpay_order_id)
                ->whereNotNull('credit_package_id')
                ->firstOrFail();

            if ($payment->status === 'success') {
                return response()->json(['message' => 'Payment already processed']);
            }

            $payment->update([
                'payment_id' => $request->razorpay_payment_id,
                'status' => 'success',
            ]);

            $this->walletService->credit(
                $request->user(),
                $payment->creditPackage->credits,
                'topup',
                referenceType: 'credit_package',
                referenceId: $payment->creditPackage->id,
                paymentId: $payment->id,
            );

            return response()->json(['message' => 'Wallet credited successfully']);

        } catch (\Exception $e) {
            Log::error('Razorpay Wallet Verification Error: ' . $e->getMessage());
            return response()->json(['message' => 'Payment verification failed'], 400);
        }
    }
}
