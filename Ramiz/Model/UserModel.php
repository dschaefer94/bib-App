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

    // Neuen Benutzer einfügen
public function insertUser(array $data): bool
{
    $existing = $this->getUserByEmail($data['email'] ?? '');
    if ($existing) return false;

    $stmt = $this->pdo->prepare("
        INSERT INTO benutzer (email, passwort)
        VALUES (:email, :passwort)
    ");
    return $stmt->execute([
        'email' => $data['email'],
        'passwort' => $data['password']
    ]);
}

    // Для теста / getUser (список)
    public function selectUser(): array
    {
        $stmt = $this->pdo->query("SELECT benutzer_id, email FROM benutzer");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
