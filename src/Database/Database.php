<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dbPath = $_ENV['DATABASE_PATH'] ?? './data/database.sqlite';
            
            if (!file_exists($dbPath)) {
                touch($dbPath);
            }
            
            self::$instance = new PDO(
                "sqlite:{$dbPath}",
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        }

        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}
