<?php

namespace ppb\Controller;

use PDO;

class UserController {
    private $db;

    // ALTER TABLE Benutzer ADD COLUMN reset_token VARCHAR(255) NULL, ADD COLUMN reset_token_expires DATETIME NULL;

    public function __construct($db = null) {
        if ($db) {
            $this->db = $db;
            return;
        }

        // Wenn restapi.php keine DB übergibt, baut der Controller selbst eine Verbindung auf.
        // TODO: DB-Zugangsdaten anpassen (Host, DB_NAME, DB_USER, DB_PASS)
        $host = 'localhost';
        $dbName = 'DB_NAME';
        $user = 'DB_USER';
        $pass = 'DB_PASS';

        try {
            $this->db = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (\Exception $e) {
            // Minimal: JSON-Fehlerausgabe und Abbruch
            echo json_encode(['error' => 'DB connection error: ' . $e->getMessage()]);
            exit;
        }
    }

    // Falls $data nicht übergeben wird (GET via restapi), verwende $_REQUEST
    public function requestPasswordReset($data = null) {
        if (empty($data)) $data = $_REQUEST;
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
            $resetLink = "reset-password.html?token=" . $token; // relativer Link, lokal testbar
            $this->sendResetEmail($email, $resetLink);

            // Minimal: JSON-Antwort. 'debug_link' nur für lokale Tests (entfernen in Prod).
            echo json_encode(['success' => 'Link wurde gesendet', 'debug_link' => $resetLink]);
        } else {
            echo json_encode(['error' => 'E-Mail nicht gefunden']);
        }
    }

    public function resetPassword($data = null) {
        if (empty($data)) $data = $_REQUEST;
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
        // Minimal: sende per mail() (funktioniert nur, wenn Server MTA hat).
        // Für lokale Tests oder falls kein Mailserver vorhanden: debug_link wird in JSON zurückgegeben.
        $subject = "Passwort zurücksetzen";
        $message = "Klicke hier zum Zurücksetzen: " . $link;
        @mail($email, $subject, $message);
    }
}