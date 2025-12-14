<?php
namespace ppb;

use PDO;
use PDOException;

class Database {
    private $dbName = "pbd2h24ani_taskit";
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
            return $pdo;
        } catch (PDOException $e) {
            die("DB error: " . $e->getMessage());
        }
    }
}
