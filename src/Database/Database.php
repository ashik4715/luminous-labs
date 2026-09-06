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
            $projectRoot = dirname(__DIR__, 2);
            $dbPath = $_ENV['DATABASE_PATH'] ?? $projectRoot . '/data/database.sqlite';
            
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
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
