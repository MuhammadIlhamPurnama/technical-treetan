<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    /**
     * Create a new order (checkout)
     */
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $request->user();
            $items = $request->items;
            $shippingAddress = $request->shipping_address;

            // Calculate totals
            $subtotal = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Check if product is available
                if (!$product->isAvailable()) {
                    throw new \Exception("Product '{$product->name}' is not available");
                }

                // Check stock
                if (!$product->hasStock($item['quantity'])) {
                    throw new \Exception("Insufficient stock for product '{$product->name}'. Available: {$product->stock}");
                }

                $itemTotal = $product->price * $item['quantity'];
                $subtotal += $itemTotal;

                $orderItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'total_price' => $itemTotal,
                ];
            }

            // Calculate tax and shipping
            $taxRate = 0.10; // 10% tax
            $taxAmount = $subtotal * $taxRate;
            $shippingAmount = $request->get('shipping_amount', 15.00); // Default shipping
            $totalAmount = $subtotal + $taxAmount + $shippingAmount;

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'shipping_address' => $shippingAddress,
                'payment_method' => $request->get('payment_method', 'pending'),
                'payment_status' => 'pending',
                'notes' => $request->get('notes'),
            ]);

            // Create order items and update stock
            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ]);

                // Decrease product stock
                $item['product']->decreaseStock($item['quantity']);
            }

            DB::commit();

            // Load relationships for response
            $order->load(['orderItems.product', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => new OrderResource($order)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Checkout failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get user's orders
     */
    public function orders(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = $user->orders()->with(['orderItems.product']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by payment status
            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            // Sort orders
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $orders = $query->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'message' => 'Orders retrieved successfully',
                'data' => OrderResource::collection($orders),
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
                'message' => 'Failed to retrieve orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific order details
     */
    public function orderDetails(Request $request, Order $order): JsonResponse
    {
        try {
            // Check if order belongs to authenticated user
            if ($order->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to order'
                ], 403);
            }

            $order->load(['orderItems.product', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Order details retrieved successfully',
                'data' => new OrderResource($order)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(Request $request, Order $order): JsonResponse
    {
        try {
            // Check if order belongs to authenticated user
            if ($order->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to order'
                ], 403);
            }

            // Check if order can be cancelled
            if (!$order->canBeCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot be cancelled in current status'
                ], 400);
            }

            DB::beginTransaction();

            // Restore product stock
            foreach ($order->orderItems as $item) {
                $product = $item->product;
                $product->stock += $item->quantity;
                $product->save();
            }

            // Update order status
            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'data' => new OrderResource($order->fresh(['orderItems.product', 'user']))
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate checkout totals before creating order
     */
    public function calculateTotals(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
            ]);

            $items = $request->items;
            $subtotal = 0;
            $itemsData = [];

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Check availability
                if (!$product->isAvailable()) {
                    throw new \Exception("Product '{$product->name}' is not available");
                }

                // Check stock
                if (!$product->hasStock($item['quantity'])) {
                    throw new \Exception("Insufficient stock for product '{$product->name}'. Available: {$product->stock}");
                }

                $itemTotal = $product->price * $item['quantity'];
                $subtotal += $itemTotal;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'total_price' => $itemTotal,
                ];
            }

            // Calculate tax and shipping
            $taxRate = 0.10;
            $taxAmount = $subtotal * $taxRate;
            $shippingAmount = $request->get('shipping_amount', 15.00);
            $totalAmount = $subtotal + $taxAmount + $shippingAmount;

            return response()->json([
                'success' => true,
                'message' => 'Totals calculated successfully',
                'data' => [
                    'items' => $itemsData,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'tax_rate' => $taxRate,
                    'shipping_amount' => $shippingAmount,
                    'total_amount' => $totalAmount,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate totals',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}