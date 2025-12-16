<?php
namespace ppb;

use PDO;
use PDOException;

class Database {
    private $dbName = "pbd2h24ani_stundenplan_db"; // твоя база
    private $linkName = "mysql.pb.bib.de";
    private $user = "pbd2h24ani";
    private $pw = "M4gajX3TjNvy";

    public function linkDB() {
        try {
            $pdo = new PDO(
                "mysql:host={$this->linkName};dbname={$this->dbName};charset=utf8mb4",
                $this->user,
                $this->pw
            );
            // Включаем режим выброса исключений
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            // Возвращаем JSON вместо die
            echo json_encode([
                "success" => false,
                "message" => "DB error: " . $e->getMessage()
            ], JSON_PRETTY_PRINT);
            exit; // прерываем выполнение скрипта
        }
    }
}
