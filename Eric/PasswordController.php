<?php
namespace ppb\Controller;

use ppb\Model\PasswordModel;
use PDO;

class PasswordController {
    private ?PDO $db = null;
    private PasswordModel $passwordModel;
    private bool $debug = false;
    private bool $useSessionFallback = false;

    public function __construct(PDO $db = null) {
        $this->debug = (bool) getenv('DEBUG_RESET_LINK');

        if ($db) {
            $this->db = $db;
        } else {
            try {
                require_once __DIR__ . '/../Database.php';
                $this->db = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (\Throwable $e) {
                $this->db = null;
                $this->useSessionFallback = true;
                if (session_status() === PHP_SESSION_NONE) session_start();
            }
        }

        if ($this->db) {
            $this->passwordModel = new PasswordModel($this->db);
        } else {
            // create a tiny stub model that will not be used (we use session fallback in controller)
            $this->passwordModel = new PasswordModel(new PDO('sqlite::memory:')); // not used
        }
    }

    // requestPasswordReset: expects ['email'=>...] or ?email=...
    public function requestPasswordReset($data = null) {
        header('Content-Type: application/json');
        if (empty($data)) $data = $_REQUEST;
        $email = trim($data['email'] ?? '');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['error' => 'Ungültige E-Mail']);
            return;
        }

        $response = ['success' => 'Wenn die E-Mail existiert, wurde ein Link gesendet.'];

        // Check user existence
        $user = null;
        if ($this->db) {
            try {
                $user = $this->passwordModel->findByEmail($email);
            } catch (\Throwable $e) {
                $user = null;
            }
        } else {
            // session fallback: treat as if user exists (for local testing)
            $user = ['email' => $email, 'id' => 0];
        }

        if (!$user) {
            echo json_encode($response);
            return;
        }

        // create token (send plain, store hash)
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stored = false;
        if ($this->db) {
            try {
                $stored = $this->passwordModel->setResetToken($email, $tokenHash, $expires);
            } catch (\Throwable $e) {
                $stored = false;
            }
        }

        if (!$stored && $this->useSessionFallback) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['password_resets'][$tokenHash] = ['email' => $email, 'expires' => $expires];
            $stored = true;
        }

        // create reset link (relative for local)
        $resetLink = 'passwordaendern.html?token=' . $token;

        // send mail (may fail if no MTA)
        $mailSent = $this->sendResetEmail($email, $resetLink);

        if ($this->debug || !$mailSent) {
            $response['debug_link'] = $resetLink;
            $response['mail_sent'] = $mailSent ? true : false;
        }

        echo json_encode($response);
    }

    // resetPassword: expects ['token'=>..., 'password'=>...]
    public function resetPassword($data = null) {
        header('Content-Type: application/json');
        if (empty($data)) $data = $_REQUEST;
        $token = trim($data['token'] ?? '');
        $newPassword = trim($data['password'] ?? '');

        if (!$token || !$newPassword) {
            echo json_encode(['error' => 'Ungültige Daten']);
            return;
        }

        if (strlen($newPassword) < 8) {
            echo json_encode(['error' => 'Passwort zu kurz (mind. 8 Zeichen)']);
            return;
        }

        $tokenHash = hash('sha256', $token);
        $user = null;

        if ($this->db) {
            try {
                $user = $this->passwordModel->findByResetTokenHash($tokenHash);
            } catch (\Throwable $e) {
                $user = null;
            }
        }

        // session fallback check
        if (!$user && $this->useSessionFallback) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $entry = $_SESSION['password_resets'][$tokenHash] ?? null;
            if ($entry && strtotime($entry['expires']) > time()) {
                // simulate a user record with id = 0 and email
                $user = ['id' => 0, 'email' => $entry['email']];
            }
        }

        if (!$user) {
            echo json_encode(['error' => 'Token ungültig oder abgelaufen']);
            return;
        }

        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $updated = false;

        if ($this->db && isset($user['id']) && (int)$user['id'] > 0) {
            try {
                $updated = $this->passwordModel->updatePassword((int)$user['id'], $passwordHash);
            } catch (\Throwable $e) {
                $updated = false;
            }
        } elseif ($this->useSessionFallback) {
            // store hashed password in session (local test only)
            $_SESSION['passwords'][$user['email']] = $passwordHash;
            // remove token
            unset($_SESSION['password_resets'][$tokenHash]);
            $updated = true;
        }

        if ($updated) {
            echo json_encode(['success' => 'Passwort aktualisiert']);
        } else {
            echo json_encode(['error' => 'Fehler beim Aktualisieren des Passworts']);
        }
    }

    private function sendResetEmail(string $email, string $link): bool {
        $subject = "Passwort zurücksetzen";
        $message = "Um dein Passwort zurückzusetzen, klicke auf den Link:\n\n" . $link . "\n\nWenn du das nicht angefordert hast, ignoriere diese E-Mail.";
        $headers = "From: no-reply@localhost\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        return @mail($email, $subject, $message, $headers);
    }
}