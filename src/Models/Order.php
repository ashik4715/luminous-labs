<?php

declare(strict_types=1);

namespace App\Models;

use App\Database\Database;
use PDO;

class Order
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findByPaymentId(string $paymentId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE payment_id = :payment_id');
        $stmt->execute(['payment_id' => $paymentId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO orders (payment_id, customer_email, amount, currency, status, metadata)
            VALUES (:payment_id, :customer_email, :amount, :currency, :status, :metadata)
        ");
        
        $stmt->execute([
            'payment_id' => $data['payment_id'],
            'customer_email' => $data['customer_email'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'USD',
            'status' => $data['status'] ?? 'completed',
            'metadata' => json_encode($data['metadata'] ?? []),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function exists(string $paymentId): bool
    {
        return $this->findByPaymentId($paymentId) !== null;
    }
}
