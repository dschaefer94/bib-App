<?php
namespace ppb\Model;

use ppb\Database;

class UserModel
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = (new Database())->linkDB();
    }

    public function getUserByUsername(string $username): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM benutzer WHERE email = :email");
            $stmt->execute(['email' => $username]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (\PDOException $e) {
            echo json_encode([
                "success" => false,
                "message" => "DB query error: " . $e->getMessage()
            ]);
            exit;
        }
    }
}