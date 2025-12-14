<?php
namespace ppb\Model;

use PDO;

class PasswordModel {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM Benutzer WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function setResetToken(string $email, string $tokenHash, string $expires): bool {
        $stmt = $this->db->prepare("UPDATE Benutzer SET reset_token_hash = ?, reset_token_expires = ? WHERE email = ?");
        $stmt->execute([$tokenHash, $expires, $email]);
        return $stmt->rowCount() > 0;
    }

    public function findByResetTokenHash(string $tokenHash): ?array {
        $stmt = $this->db->prepare("SELECT * FROM Benutzer WHERE reset_token_hash = ? AND reset_token_expires > NOW() LIMIT 1");
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updatePassword(int $id, string $passwordHash): bool {
        $stmt = $this->db->prepare("UPDATE Benutzer SET password = ?, reset_token_hash = NULL, reset_token_expires = NULL WHERE id = ?");
        $stmt->execute([$passwordHash, $id]);
        return $stmt->rowCount() > 0;
    }
}