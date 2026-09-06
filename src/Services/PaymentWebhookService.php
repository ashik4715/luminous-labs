<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\WebhookLog;

class PaymentWebhookService
{
    private Order $orderModel;
    private WebhookLog $webhookLogModel;
    private WebhookSignatureVerifier $signatureVerifier;

    public function __construct(
        ?Order $orderModel = null,
        ?WebhookLog $webhookLogModel = null,
        ?WebhookSignatureVerifier $signatureVerifier = null
    ) {
        $this->orderModel = $orderModel ?? new Order();
        $this->webhookLogModel = $webhookLogModel ?? new WebhookLog();
        $this->signatureVerifier = $signatureVerifier ?? new WebhookSignatureVerifier(
            $_ENV['PAYMENT_PROVIDER_SECRET'] ?? ''
        );
    }

    public function processWebhook(string $rawPayload, string $signatureHeader): array
    {
        $logData = [
            'event_type' => 'unknown',
            'payload' => $rawPayload,
            'status' => 'pending',
        ];

        try {
            // Step 1: Verify signature
            if (!$this->signatureVerifier->verify($rawPayload, $signatureHeader)) {
                $logData['status'] = 'rejected';
                $logData['error_message'] = 'Invalid webhook signature';
                $this->webhookLogModel->create($logData);
                
                return [
                    'success' => false,
                    'message' => 'Invalid signature',
                    'statusCode' => 401,
                ];
            }

            $payload = json_decode($rawPayload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON payload');
            }

            $eventType = $payload['type'] ?? 'unknown';
            $logData['event_type'] = $eventType;

            // Step 2: Handle payment.succeeded event
            if ($eventType !== 'payment.succeeded') {
                $logData['status'] = 'ignored';
                $logData['error_message'] = "Event type '{$eventType}' not handled";
                $this->webhookLogModel->create($logData);
                
                return [
                    'success' => true,
                    'message' => 'Event type not handled',
                    'statusCode' => 200,
                ];
            }

            $paymentData = $payload['data']['object'] ?? null;
            if ($paymentData === null) {
                throw new \InvalidArgumentException('Missing payment data');
            }

            $paymentId = $paymentData['id'] ?? null;
            if ($paymentId === null) {
                throw new \InvalidArgumentException('Missing payment ID');
            }

            $logData['payment_id'] = $paymentId;

            // Step 3: Check idempotency - don't process if already exists
            if ($this->orderModel->exists($paymentId)) {
                $logData['status'] = 'duplicate';
                $this->webhookLogModel->create($logData);
                
                return [
                    'success' => true,
                    'message' => 'Order already exists',
                    'statusCode' => 200,
                ];
            }

            // Step 4: Create order
            $orderData = [
                'payment_id' => $paymentId,
                'customer_email' => $paymentData['billing_details']['email'] ?? 'unknown@example.com',
                'amount' => $paymentData['amount'] ?? 0,
                'currency' => $paymentData['currency'] ?? 'USD',
                'status' => 'completed',
                'metadata' => $paymentData['metadata'] ?? [],
            ];

            $this->orderModel->create($orderData);

            $logData['status'] = 'success';
            $this->webhookLogModel->create($logData);

            return [
                'success' => true,
                'message' => 'Order created successfully',
                'statusCode' => 200,
            ];

        } catch (\Exception $e) {
            $logData['status'] = 'error';
            $logData['error_message'] = $e->getMessage();
            $this->webhookLogModel->create($logData);

            return [
                'success' => false,
                'message' => 'Processing error: ' . $e->getMessage(),
                'statusCode' => 500,
            ];
        }
    }
}
