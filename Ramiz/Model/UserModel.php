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

    // Den User anhand der E-Mail-Adresse abrufen
    public function getUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM benutzer WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $user ?: null;
    }

  
}
