<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;

class PaymentController extends Controller
{
    protected $invoiceApi;

    public function __construct()
    {
        // Initialize Xendit configuration
        Configuration::setXenditKey(config('services.xendit.secret_key'));
        $this->invoiceApi = new InvoiceApi();
    }

    /**
     * Create payment for an order
     */
    public function createPayment(Request $request, Order $order): JsonResponse
    {
        try {
            // Validate request
            $request->validate([
                'payment_method' => 'required|in:virtual_account,credit_card,ewallet,retail_outlet,qr_code',
                'payment_channel' => 'nullable|string',
            ]);

            // Check if order belongs to authenticated user
            if ($order->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to order'
                ], 403);
            }

            // Check if order already has successful payment
            if ($order->hasSuccessfulPayment()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order already has successful payment'
                ], 400);
            }

            DB::beginTransaction();

            // Create payment record
            $externalId = Payment::generateExternalId();
            $payment = Payment::create([
                'order_id' => $order->id,
                'external_id' => $externalId,
                'payment_method' => $request->payment_method,
                'payment_channel' => $request->payment_channel,
                'amount' => $order->total_amount,
                'currency' => 'IDR',
                'expired_at' => now()->addHours(24), // 24 hours expiry
            ]);

            // Prepare invoice request for Xendit
            $invoiceRequest = new CreateInvoiceRequest([
                'external_id' => $externalId,
                'amount' => $order->total_amount,
                'description' => "Payment for Order #{$order->order_number}",
                'invoice_duration' => 86400, // 24 hours in seconds
                'customer' => [
                    'given_names' => $order->user->name,
                    'email' => $order->user->email,
                ],
                'customer_notification_preference' => [
                    'invoice_created' => ['email'],
                    'invoice_reminder' => ['email'],
                    'invoice_paid' => ['email'],
                    'invoice_expired' => ['email'],
                ],
                'success_redirect_url' => config('app.frontend_url') . "/orders/{$order->id}/success",
                'failure_redirect_url' => config('app.frontend_url') . "/orders/{$order->id}/failed",
                'currency' => 'IDR',
                'items' => $this->buildInvoiceItems($order),
            ]);

            // Add payment method specific configurations
            if ($request->payment_method === 'virtual_account' && $request->payment_channel) {
                $invoiceRequest->setPaymentMethods(['BANK_TRANSFER']);
            } elseif ($request->payment_method === 'ewallet' && $request->payment_channel) {
                $invoiceRequest->setPaymentMethods([strtoupper($request->payment_channel)]);
            }

            // Create invoice with Xendit
            $xenditInvoice = $this->invoiceApi->createInvoice($invoiceRequest);

            // Update payment with Xendit response
            $payment->update([
                'payment_id' => $xenditInvoice['id'],
                'payment_url' => $xenditInvoice['invoice_url'],
                'xendit_response' => $xenditInvoice,
                'status' => strtolower($xenditInvoice['status']),
            ]);

            DB::commit();

            // Load payment with order relationship
            $payment->load('order');

            return response()->json([
                'success' => true,
                'message' => 'Payment created successfully',
                'data' => new PaymentResource($payment)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment details
     */
    public function getPayment(Request $request, Payment $payment): JsonResponse
    {
        try {
            // Check if payment belongs to authenticated user's order
            if ($payment->order->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to payment'
                ], 403);
            }

            $payment->load('order');

            return response()->json([
                'success' => true,
                'message' => 'Payment details retrieved successfully',
                'data' => new PaymentResource($payment)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all payments for user's orders
     */
    public function getUserPayments(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $query = Payment::whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->with('order');

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by payment method
            if ($request->has('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            // Sort payments
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $payments = $query->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'message' => 'Payments retrieved successfully',
                'data' => PaymentResource::collection($payments),
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                    'last_page' => $payments->lastPage(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel payment
     */
    public function cancelPayment(Request $request, Payment $payment): JsonResponse
    {
        try {
            // Check if payment belongs to authenticated user's order
            if ($payment->order->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to payment'
                ], 403);
            }

            // Check if payment can be cancelled
            if (!$payment->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment cannot be cancelled in current status'
                ], 400);
            }

            DB::beginTransaction();

            // Expire invoice in Xendit
            if ($payment->payment_id) {
                try {
                    $this->invoiceApi->expireInvoice($payment->payment_id);
                } catch (\Exception $e) {
                    Log::warning('Failed to expire Xendit invoice', [
                        'payment_id' => $payment->payment_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Update payment status
            $payment->update([
                'status' => 'cancelled',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment cancelled successfully',
                'data' => new PaymentResource($payment->fresh(['order']))
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build invoice items from order
     */
    private function buildInvoiceItems(Order $order): array
    {
        $items = [];

        foreach ($order->orderItems as $item) {
            $items[] = [
                'name' => $item->product->name,
                'quantity' => $item->quantity,
                'price' => $item->unit_price,
                'category' => $item->product->category ?? 'General',
            ];
        }

        // Add tax item if exists
        if ($order->tax_amount > 0) {
            $items[] = [
                'name' => 'Tax',
                'quantity' => 1,
                'price' => $order->tax_amount,
                'category' => 'Tax',
            ];
        }

        // Add shipping item if exists
        if ($order->shipping_amount > 0) {
            $items[] = [
                'name' => 'Shipping',
                'quantity' => 1,
                'price' => $order->shipping_amount,
                'category' => 'Shipping',
            ];
        }

        return $items;
    }
}