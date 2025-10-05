<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XenditWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_processes_payment_successfully()
    {
        // Create test data
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'total_amount' => 100000]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_id' => 'pay_test123',
            'external_id' => 'payment_test_external_123',
            'payment_method' => 'BANK_TRANSFER',
            'status' => 'pending',
            'amount' => 100000,
            'currency' => 'IDR',
            'payment_url' => 'https://test.com',
            'xendit_response' => json_encode(['test' => true]),
            'expired_at' => now()->addDay()
        ]);

        // Prepare webhook data
        $webhookData = [
            'id' => 'invoice_123',
            'external_id' => $payment->external_id,
            'status' => 'PAID',
            'amount' => 100000,
            'paid_amount' => 100000,
            'payment_method' => 'BANK_TRANSFER',
            'payment_channel' => 'BCA',
            'payment_id' => 'payment_abc123',
            'paid_at' => '2025-10-05T12:15:00.000Z'
        ];

        // Send webhook request
        $response = $this->post('/api/webhooks/xendit/invoice', $webhookData, [
            'x-callback-token' => config('xendit.callback_token')
        ]);

        // Assert response
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        // Assert payment was updated
        $payment->refresh();
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals(100000, $payment->paid_amount);

        // Assert order was updated
        $order->refresh();
        $this->assertEquals('paid', $order->status);
    }

    public function test_webhook_fails_with_invalid_token()
    {
        $webhookData = [
            'id' => 'invoice_123',
            'external_id' => 'payment_test_123',
            'status' => 'PAID'
        ];

        $response = $this->post('/api/webhooks/xendit/invoice', $webhookData, [
            'x-callback-token' => 'invalid_token'
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    public function test_webhook_fails_with_nonexistent_payment()
    {
        $webhookData = [
            'id' => 'invoice_123',
            'external_id' => 'nonexistent_payment_id',
            'status' => 'PAID'
        ];

        $response = $this->post('/api/webhooks/xendit/invoice', $webhookData, [
            'x-callback-token' => config('xendit.callback_token')
        ]);

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Payment not found']);
    }
}