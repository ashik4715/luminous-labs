<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\WebhookSignatureVerifier;

class WebhookSignatureVerifierTest extends TestCase
{
    private WebhookSignatureVerifier $verifier;
    private string $testSecret = 'whsec_test12345678901234567890';

    protected function setUp(): void
    {
        $this->verifier = new WebhookSignatureVerifier($this->testSecret);
    }

    public function testValidSignatureIsAccepted(): void
    {
        $payload = '{"type":"payment.succeeded","data":{"object":{"id":"pay_123"}}}';
        $signature = hash_hmac('sha256', $payload, $this->testSecret);
        $signatureHeader = "sha256={$signature}";

        $this->assertTrue($this->verifier->verify($payload, $signatureHeader));
    }

    public function testInvalidSignatureIsRejected(): void
    {
        $payload = '{"type":"payment.succeeded","data":{"object":{"id":"pay_123"}}}';
        $signatureHeader = 'sha256=invalidsignature123456789012345678901234567890';

        $this->assertFalse($this->verifier->verify($payload, $signatureHeader));
    }

    public function testEmptySignatureHeaderIsRejected(): void
    {
        $payload = '{"type":"payment.succeeded"}';

        $this->assertFalse($this->verifier->verify($payload, ''));
    }

    public function testMissingSha256PrefixIsRejected(): void
    {
        $payload = '{"type":"payment.succeeded"}';
        $signature = hash_hmac('sha256', $payload, $this->testSecret);
        $signatureHeader = $signature; // Missing sha256= prefix

        $this->assertFalse($this->verifier->verify($payload, $signatureHeader));
    }

    public function testDifferentPayloadProducesDifferentSignature(): void
    {
        $payload1 = '{"type":"payment.succeeded","data":{"object":{"id":"pay_123"}}}';
        $payload2 = '{"type":"payment.succeeded","data":{"object":{"id":"pay_456"}}}';
        
        $sig1 = hash_hmac('sha256', $payload1, $this->testSecret);
        $sig2 = hash_hmac('sha256', $payload2, $this->testSecret);

        $this->assertNotEquals($sig1, $sig2);
    }
}
