<?php

declare(strict_types=1);

namespace App\Models;

use App\Database\Database;
use PDO;

class WebhookLog
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO webhook_logs (event_type, payment_id, payload, status, error_message)
            VALUES (:event_type, :payment_id, :payload, :status, :error_message)
        ");
        
        $stmt->execute([
            'event_type' => $data['event_type'],
            'payment_id' => $data['payment_id'] ?? null,
            'payload' => $data['payload'],
            'status' => $data['status'],
            'error_message' => $data['error_message'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findByPaymentId(string $paymentId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM webhook_logs WHERE payment_id = :payment_id ORDER BY created_at DESC');
        $stmt->execute(['payment_id' => $paymentId]);
        return $stmt->fetchAll();
    }
}
