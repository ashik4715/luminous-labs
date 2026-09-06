<?php

declare(strict_types=1);

namespace App\Services;

class WebhookSignatureVerifier
{
    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function verify(string $payload, string $signatureHeader): bool
    {
        if (empty($signatureHeader)) {
            return false;
        }

        $signature = $this->extractSignature($signatureHeader);
        if ($signature === null) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $this->secret);
        
        return hash_equals($expectedSignature, $signature);
    }

    private function extractSignature(string $header): ?string
    {
        if (str_starts_with($header, 'sha256=')) {
            return substr($header, 7);
        }
        return null;
    }
}
