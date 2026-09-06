<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\PaymentWebhookService;

class WebhookController
{
    private PaymentWebhookService $webhookService;

    public function __construct(?PaymentWebhookService $webhookService = null)
    {
        $this->webhookService = $webhookService ?? new PaymentWebhookService();
    }

    public function handlePaymentWebhook(): void
    {
        $rawPayload = file_get_contents('php://input');
        $signatureHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? $_SERVER['HTTP_X_PROVIDER_SIGNATURE'] ?? '';

        $result = $this->webhookService->processWebhook($rawPayload, $signatureHeader);

        http_response_code($result['statusCode']);
        header('Content-Type: application/json');
        echo json_encode(['message' => $result['message']]);
    }
}
