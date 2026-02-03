<?php
//1:1-Kopie von Erics USerController.php
namespace SDP\Controller;

use PDO;

class PasswordController {
    private $db;

    //ALTER TABLE Benutzer ADD COLUMN reset_token VARCHAR(255) NULL, ADD COLUMN reset_token_expires DATETIME NULL;

    public function __construct($db = null) {
        if ($db) $this->db = $db;
    }

    public function requestPasswordReset($data) {
        $email = $data['email'] ?? null;
        
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['error' => 'Ungültige E-Mail']);
            return;
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $this->db->prepare("UPDATE Benutzer SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
        $stmt->execute([$token, $expires, $email]);

        if ($stmt->rowCount() > 0) {
            // E-Mail versenden mit Reset-Link
            $resetLink = "https://deine-domain.de/reset-password.html?token=" . $token;
            $this->sendResetEmail($email, $resetLink);
            
            echo json_encode(['success' => 'Link wurde gesendet']);
        } else {
            echo json_encode(['error' => 'E-Mail nicht gefunden']);
        }
    }

    public function resetPassword($data) {
        $token = $data['token'] ?? null;
        $newPassword = $data['password'] ?? null;

        if (!$token || !$newPassword) {
            echo json_encode(['error' => 'Ungültige Daten']);
            return;
        }

        $stmt = $this->db->prepare("SELECT id FROM Benutzer WHERE reset_token = ? AND reset_token_expires > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare("UPDATE Benutzer SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['id']]);
            
            echo json_encode(['success' => 'Passwort aktualisiert']);
        } else {
            echo json_encode(['error' => 'Token ungültig oder abgelaufen']);
        }
    }

    private function sendResetEmail($email, $link) {
        $subject = "Passwort zurücksetzen";
        $message = "Klicke hier zum Zurücksetzen: " . $link;
        mail($email, $subject, $message);
    }
}