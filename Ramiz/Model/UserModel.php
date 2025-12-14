<?php
namespace ppb\Model;

use ppb\Database;

class UserModel {
    private $pdo;

    public function __construct() {
        $this->pdo = (new Database())->linkDB();
    }

    public function getUserByUsername(string $username): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM bib_users_test WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $user ?: null;
    }
}
