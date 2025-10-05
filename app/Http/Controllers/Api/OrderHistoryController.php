<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderHistoryController extends Controller
{
    /**
     * Get comprehensive order history for user
     */
    public function getOrderHistory(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = $user->orders()->with(['orderItems.product', 'payments']);

            // Advanced filtering options
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            // Date range filtering
            if ($request->has('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            // Search by order number
            if ($request->has('search')) {
                $query->where('order_number', 'like', '%' . $request->search . '%');
            }

            // Amount range filtering
            if ($request->has('min_amount')) {
                $query->where('total_amount', '>=', $request->min_amount);
            }

            if ($request->has('max_amount')) {
                $query->where('total_amount', '<=', $request->max_amount);
            }

            // Sort orders
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $orders = $query->paginate($request->get('per_page', 10));

            // Calculate summary statistics
            $summary = $this->calculateOrderSummary($user);

            return response()->json([
                'success' => true,
                'message' => 'Order history retrieved successfully',
                'data' => OrderResource::collection($orders),
                'summary' => $summary,
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order statistics and summary
     */
    public function getOrderSummary(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $summary = $this->calculateOrderSummary($user);

            return response()->json([
                'success' => true,
                'message' => 'Order summary retrieved successfully',
                'data' => $summary
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get orders by specific status
     */
    public function getOrdersByStatus(Request $request, string $status): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Validate status
            $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid order status'
                ], 400);
            }

            $query = $user->orders()
                         ->where('status', $status)
                         ->with(['orderItems.product', 'payments']);

            // Additional filtering
            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            if ($request->has('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $orders = $query->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'message' => "Orders with status '{$status}' retrieved successfully",
                'data' => OrderResource::collection($orders),
                'meta' => [
                    'status' => $status,
                    'total_orders' => $orders->total(),
                ],
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve orders by status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent orders (last 30 days)
     */
    public function getRecentOrders(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $orders = $user->orders()
                          ->where('created_at', '>=', now()->subDays(30))
                          ->with(['orderItems.product', 'payments'])
                          ->orderBy('created_at', 'desc')
                          ->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'message' => 'Recent orders retrieved successfully',
                'data' => OrderResource::collection($orders),
                'meta' => [
                    'period' => 'Last 30 days',
                    'total_orders' => $orders->total(),
                ],
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recent orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Track specific order with detailed status history
     */
    public function trackOrder(Request $request, Order $order): JsonResponse
    {
        try {
            // Check if order belongs to authenticated user
            if ($order->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to order'
                ], 403);
            }

            $order->load(['orderItems.product', 'payments', 'user']);

            // Build tracking timeline
            $timeline = $this->buildOrderTimeline($order);

            return response()->json([
                'success' => true,
                'message' => 'Order tracking information retrieved successfully',
                'data' => [
                    'order' => new OrderResource($order),
                    'timeline' => $timeline,
                    'estimated_delivery' => $this->calculateEstimatedDelivery($order),
                    'can_be_cancelled' => $order->canBeCancelled(),
                    'has_successful_payment' => $order->hasSuccessfulPayment(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order tracking information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate order summary statistics
     */
    private function calculateOrderSummary($user): array
    {
        $orders = $user->orders();

        return [
            'total_orders' => $orders->count(),
            'completed_orders' => $orders->where('status', 'delivered')->count(),
            'pending_orders' => $orders->where('status', 'pending')->count(),
            'processing_orders' => $orders->where('status', 'processing')->count(),
            'shipped_orders' => $orders->where('status', 'shipped')->count(),
            'cancelled_orders' => $orders->where('status', 'cancelled')->count(),
            'total_spent' => $orders->where('payment_status', 'paid')->sum('total_amount'),
            'average_order_value' => $orders->where('payment_status', 'paid')->avg('total_amount') ?? 0,
            'recent_orders_count' => $orders->where('created_at', '>=', now()->subDays(30))->count(),
            'orders_by_status' => [
                'pending' => $orders->where('status', 'pending')->count(),
                'processing' => $orders->where('status', 'processing')->count(),
                'shipped' => $orders->where('status', 'shipped')->count(),
                'delivered' => $orders->where('status', 'delivered')->count(),
                'cancelled' => $orders->where('status', 'cancelled')->count(),
            ],
            'orders_by_payment_status' => [
                'pending' => $orders->where('payment_status', 'pending')->count(),
                'paid' => $orders->where('payment_status', 'paid')->count(),
                'failed' => $orders->where('payment_status', 'failed')->count(),
                'refunded' => $orders->where('payment_status', 'refunded')->count(),
            ],
        ];
    }

    /**
     * Build order timeline
     */
    private function buildOrderTimeline(Order $order): array
    {
        $timeline = [];

        // Order created
        $timeline[] = [
            'status' => 'Order Created',
            'description' => 'Order has been placed successfully',
            'timestamp' => $order->created_at,
            'is_completed' => true,
        ];

        // Payment status
        if ($order->hasSuccessfulPayment()) {
            $payment = $order->payments()->where('status', 'paid')->first();
            $timeline[] = [
                'status' => 'Payment Confirmed',
                'description' => 'Payment has been received and confirmed',
                'timestamp' => $payment->paid_at ?? $payment->updated_at,
                'is_completed' => true,
            ];
        } else {
            $timeline[] = [
                'status' => 'Awaiting Payment',
                'description' => 'Waiting for payment confirmation',
                'timestamp' => null,
                'is_completed' => false,
            ];
        }

        // Processing
        if (in_array($order->status, ['processing', 'shipped', 'delivered'])) {
            $timeline[] = [
                'status' => 'Processing',
                'description' => 'Order is being prepared for shipment',
                'timestamp' => $order->updated_at,
                'is_completed' => true,
            ];
        } else {
            $timeline[] = [
                'status' => 'Processing',
                'description' => 'Order will be processed after payment confirmation',
                'timestamp' => null,
                'is_completed' => false,
            ];
        }

        // Shipped
        if (in_array($order->status, ['shipped', 'delivered'])) {
            $timeline[] = [
                'status' => 'Shipped',
                'description' => 'Order has been shipped',
                'timestamp' => $order->shipped_at,
                'is_completed' => true,
            ];
        } else {
            $timeline[] = [
                'status' => 'Shipped',
                'description' => 'Order will be shipped after processing',
                'timestamp' => null,
                'is_completed' => false,
            ];
        }

        // Delivered
        if ($order->status === 'delivered') {
            $timeline[] = [
                'status' => 'Delivered',
                'description' => 'Order has been delivered successfully',
                'timestamp' => $order->delivered_at,
                'is_completed' => true,
            ];
        } else {
            $timeline[] = [
                'status' => 'Delivered',
                'description' => 'Order will be delivered soon',
                'timestamp' => null,
                'is_completed' => false,
            ];
        }

        // Handle cancelled orders
        if ($order->status === 'cancelled') {
            $timeline[] = [
                'status' => 'Cancelled',
                'description' => 'Order has been cancelled',
                'timestamp' => $order->updated_at,
                'is_completed' => true,
            ];
        }

        return $timeline;
    }

    /**
     * Calculate estimated delivery date
     */
    private function calculateEstimatedDelivery(Order $order): ?string
    {
        if ($order->status === 'delivered') {
            return null; // Already delivered
        }

        if ($order->status === 'cancelled') {
            return null; // Cancelled
        }

        // Simple estimation based on order status
        $baseDate = $order->shipped_at ?? $order->created_at;
        
        switch ($order->status) {
            case 'pending':
                return $baseDate->addDays(7)->format('Y-m-d');
            case 'processing':
                return $baseDate->addDays(5)->format('Y-m-d');
            case 'shipped':
                return $baseDate->addDays(3)->format('Y-m-d');
            default:
                return $baseDate->addDays(7)->format('Y-m-d');
        }
    }
}