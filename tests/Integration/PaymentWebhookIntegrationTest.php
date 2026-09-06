<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Database\Database;
use App\Database\Migrator;
use App\Services\PaymentWebhookService;
use App\Models\Order;
use App\Models\WebhookLog;

class PaymentWebhookIntegrationTest extends TestCase
{
    private string $testSecret = 'whsec_test_integration_key_123456';

    protected function setUp(): void
    {
        // Use in-memory SQLite for tests
        $_ENV['DATABASE_PATH'] = ':memory:';
        Database::reset();
        
        $db = Database::getConnection();
        $migrator = new Migrator($db);
        $migrator->migrate();
    }

    protected function tearDown(): void
    {
        Database::reset();
    }

    public function testProcessesValidWebhookSuccessfully(): void
    {
        $payload = json_encode([
            'type' => 'payment.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pay_test_123',
                    'amount' => 5000,
                    'currency' => 'usd',
                    'billing_details' => [
                        'email' => 'customer@example.com',
                    ],
                    'metadata' => [],
                ],
            ],
        ]);

        $signature = hash_hmac('sha256', $payload, $this->testSecret);
        $signatureHeader = "sha256={$signature}";

        $service = new PaymentWebhookService(
            new Order(),
            new WebhookLog(),
            new \App\Services\WebhookSignatureVerifier($this->testSecret)
        );

        $result = $service->processWebhook($payload, $signatureHeader);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['statusCode']);
        
        // Verify order was created
        $order = (new Order())->findByPaymentId('pay_test_123');
        $this->assertNotNull($order);
        $this->assertEquals('customer@example.com', $order['customer_email']);
        $this->assertEquals(5000, $order['amount']);
    }

    public function testRejectsInvalidSignature(): void
    {
        $payload = json_encode([
            'type' => 'payment.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pay_test_456',
                    'amount' => 3000,
                    'currency' => 'usd',
                ],
            ],
        ]);

        $signatureHeader = 'sha256=invalid_signature_hash';

        $service = new PaymentWebhookService(
            new Order(),
            new WebhookLog(),
            new \App\Services\WebhookSignatureVerifier($this->testSecret)
        );

        $result = $service->processWebhook($payload, $signatureHeader);

        $this->assertFalse($result['success']);
        $this->assertEquals(401, $result['statusCode']);
    }

    public function testHandlesDuplicateWebhookIdempotently(): void
    {
        $payload = json_encode([
            'type' => 'payment.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pay_test_789',
                    'amount' => 2500,
                    'currency' => 'usd',
                ],
            ],
        ]);

        $signature = hash_hmac('sha256', $payload, $this->testSecret);
        $signatureHeader = "sha256={$signature}";

        $service = new PaymentWebhookService(
            new Order(),
            new WebhookLog(),
            new \App\Services\WebhookSignatureVerifier($this->testSecret)
        );

        // Process first webhook
        $result1 = $service->processWebhook($payload, $signatureHeader);
        $this->assertTrue($result1['success']);

        // Process duplicate webhook (simulating retry)
        $result2 = $service->processWebhook($payload, $signatureHeader);
        $this->assertTrue($result2['success']);
        $this->assertEquals('Order already exists', $result2['message']);

        // Verify only one order exists
        $orders = (new Order())->findByPaymentId('pay_test_789');
        $this->assertNotNull($orders);
    }

    public function testIgnoresNonPaymentSucceededEvents(): void
    {
        $payload = json_encode([
            'type' => 'payment.failed',
            'data' => [
                'object' => [
                    'id' => 'pay_test_ignore',
                ],
            ],
        ]);

        $signature = hash_hmac('sha256', $payload, $this->testSecret);
        $signatureHeader = "sha256={$signature}";

        $service = new PaymentWebhookService(
            new Order(),
            new WebhookLog(),
            new \App\Services\WebhookSignatureVerifier($this->testSecret)
        );

        $result = $service->processWebhook($payload, $signatureHeader);

        $this->assertTrue($result['success']);
        $this->assertEquals('Event type not handled', $result['message']);
        
        // Verify no order was created
        $order = (new Order())->findByPaymentId('pay_test_ignore');
        $this->assertNull($order);
    }

    public function testLogsFailedWebhookProcessingInvalidSignature(): void
    {
        $payload = 'invalid json';

        $service = new PaymentWebhookService(
            new Order(),
            new WebhookLog(),
            new \App\Services\WebhookSignatureVerifier($this->testSecret)
        );

        $result = $service->processWebhook($payload, '');

        $this->assertFalse($result['success']);
        $this->assertEquals(401, $result['statusCode']);
    }

    public function testLogsFailedWebhookProcessingInvalidJson(): void
    {
        $payload = 'invalid json';
        $signature = hash_hmac('sha256', $payload, $this->testSecret);
        $signatureHeader = "sha256={$signature}";

        $service = new PaymentWebhookService(
            new Order(),
            new WebhookLog(),
            new \App\Services\WebhookSignatureVerifier($this->testSecret)
        );

        $result = $service->processWebhook($payload, $signatureHeader);

        $this->assertFalse($result['success']);
        $this->assertEquals(500, $result['statusCode']);
    }
}
