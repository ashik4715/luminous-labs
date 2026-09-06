<?php

declare(strict_types=1);

namespace App\Models;

use App\Database\Database;
use PDO;

class Event
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findUpcoming(int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM events 
            WHERE event_date >= datetime('now') 
            ORDER BY event_date ASC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO events (title, description, event_date, location, capacity)
            VALUES (:title, :description, :event_date, :location, :capacity)
        ");
        
        $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'event_date' => $data['event_date'],
            'location' => $data['location'] ?? null,
            'capacity' => $data['capacity'] ?? 0,
        ]);

        return (int) $this->db->lastInsertId();
    }
}
