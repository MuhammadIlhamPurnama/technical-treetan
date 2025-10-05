<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class XenditWebhookController extends Controller
{
    /**
     * Handle Xendit invoice webhook
     */
    public function handleInvoiceCallback(Request $request): JsonResponse
    {
        try {
            // Log incoming webhook for debugging
            Log::info('Xendit Invoice Webhook Received', [
                'headers' => $request->headers->all(),
                'body' => $request->all()
            ]);

            // Verify webhook signature
            if (!$this->verifyWebhookSignature($request)) {
                Log::warning('Invalid webhook signature', [
                    'headers' => $request->headers->all()
                ]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $data = $request->all();
            
            // Find payment by external_id
            $payment = Payment::where('external_id', $data['external_id'])->first();
            
            if (!$payment) {
                Log::warning('Payment not found for webhook', [
                    'external_id' => $data['external_id']
                ]);
                return response()->json(['error' => 'Payment not found'], 404);
            }

            DB::beginTransaction();

            // Update payment based on status
            $status = strtolower($data['status']);
            
            switch ($status) {
                case 'paid':
                    $this->handlePaidInvoice($payment, $data);
                    break;
                    
                case 'expired':
                    $payment->markAsExpired($data);
                    break;
                    
                case 'cancelled':
                    $payment->update([
                        'status' => 'cancelled',
                        'callback_data' => $data,
                    ]);
                    break;
                    
                default:
                    // Update payment with current status
                    $payment->update([
                        'status' => $status,
                        'callback_data' => $data,
                    ]);
                    break;
            }

            DB::commit();

            Log::info('Xendit webhook processed successfully', [
                'external_id' => $data['external_id'],
                'status' => $status,
                'payment_id' => $payment->id
            ]);

            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Xendit webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle paid invoice
     */
    private function handlePaidInvoice(Payment $payment, array $data): void
    {
        // Mark payment as paid
        $payment->markAsPaid([
            'paid_amount' => $data['paid_amount'] ?? $payment->amount,
            'payment_channel' => $data['payment_channel'] ?? null,
            'payment_method' => $data['payment_method'] ?? $payment->payment_method,
            'xendit_fee' => $data['fees'] ?? 0,
            'callback_data' => $data,
        ]);

        // Update order status
        $order = $payment->order;
        $order->markAsPaid();

        Log::info('Payment marked as paid', [
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'amount' => $data['paid_amount'] ?? $payment->amount
        ]);

        // Here you can add additional logic like:
        // - Send email notification to customer
        // - Update inventory
        // - Trigger fulfillment process
        // - Send SMS notification
        
        // Example: Send email notification (you would implement this)
        // $this->sendPaymentSuccessNotification($order, $payment);
    }

    /**
     * Verify Xendit webhook signature
     */
    private function verifyWebhookSignature(Request $request): bool
    {
        $callbackToken = config('xendit.callback_token');
        
        if (!$callbackToken) {
            // If no callback token is set, skip verification (not recommended for production)
            Log::warning('Xendit callback token not configured');
            return true;
        }

        $receivedToken = $request->header('x-callback-token');
        
        return $receivedToken === $callbackToken;
    }

    /**
     * Handle payment method specific webhooks
     */
    public function handlePaymentCallback(Request $request): JsonResponse
    {
        try {
            Log::info('Xendit Payment Callback Received', [
                'headers' => $request->headers->all(),
                'body' => $request->all()
            ]);

            // Verify webhook signature
            if (!$this->verifyWebhookSignature($request)) {
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $data = $request->all();
            
            // Handle different payment method callbacks
            $paymentMethod = $data['payment_method'] ?? 'unknown';
            
            switch ($paymentMethod) {
                case 'VIRTUAL_ACCOUNT':
                    return $this->handleVirtualAccountCallback($data);
                    
                case 'EWALLET':
                    return $this->handleEwalletCallback($data);
                    
                case 'CREDIT_CARD':
                    return $this->handleCreditCardCallback($data);
                    
                default:
                    Log::info('Unhandled payment method callback', [
                        'payment_method' => $paymentMethod,
                        'data' => $data
                    ]);
                    break;
            }

            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            Log::error('Payment callback processing failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json(['error' => 'Callback processing failed'], 500);
        }
    }

    /**
     * Handle virtual account callback
     */
    private function handleVirtualAccountCallback(array $data): JsonResponse
    {
        // Find payment by external_id or payment_id
        $payment = Payment::where('external_id', $data['external_id'] ?? '')
                         ->orWhere('payment_id', $data['id'] ?? '')
                         ->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        // Update payment status based on callback
        if (isset($data['status']) && $data['status'] === 'COMPLETED') {
            $payment->markAsPaid($data);
            $payment->order->markAsPaid();
        }

        return response()->json(['success' => true], 200);
    }

    /**
     * Handle e-wallet callback
     */
    private function handleEwalletCallback(array $data): JsonResponse
    {
        // Similar to virtual account but for e-wallet specific handling
        $payment = Payment::where('external_id', $data['external_id'] ?? '')
                         ->orWhere('payment_id', $data['id'] ?? '')
                         ->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        if (isset($data['status']) && $data['status'] === 'SUCCEEDED') {
            $payment->markAsPaid($data);
            $payment->order->markAsPaid();
        }

        return response()->json(['success' => true], 200);
    }

    /**
     * Handle credit card callback
     */
    private function handleCreditCardCallback(array $data): JsonResponse
    {
        // Handle credit card specific callback
        $payment = Payment::where('external_id', $data['external_id'] ?? '')
                         ->orWhere('payment_id', $data['id'] ?? '')
                         ->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        if (isset($data['status']) && in_array($data['status'], ['CAPTURED', 'AUTHORIZED'])) {
            $payment->markAsPaid($data);
            $payment->order->markAsPaid();
        } elseif (isset($data['status']) && in_array($data['status'], ['FAILED', 'EXPIRED'])) {
            $payment->markAsFailed($data['failure_reason'] ?? 'Payment failed', $data);
        }

        return response()->json(['success' => true], 200);
    }
}