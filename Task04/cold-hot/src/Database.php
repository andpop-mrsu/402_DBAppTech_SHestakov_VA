<?php

namespace ColdHot;

class Database
{
    private $pdo;

    public function __construct(string $databasePath)
    {
        $this->pdo = new \PDO("sqlite:" . $databasePath);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->createTables();
    }

    private function createTables(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS games (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                player_name TEXT NOT NULL,
                secret_number TEXT NOT NULL,
                is_completed BOOLEAN DEFAULT 0,
                is_won BOOLEAN DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                game_id INTEGER,
                attempt_number INTEGER,
                guess TEXT NOT NULL,
                hints TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (game_id) REFERENCES games (id)
            )
        ");
    }

    public function getPdo(): \PDO
    {
        return $this->pdo;
    }
}