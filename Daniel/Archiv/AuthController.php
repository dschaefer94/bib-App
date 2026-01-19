<?php
namespace ppb\Controller;

use ppb\Library\Msg;

class AuthController
{
    // POST /restapi.php/auth
    public function writeAuth(array $data)
    {
        // Beispiel: einfache Demo-Validierung
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        // TODO: Hier gegen DB prüfen (gehashtes Passwort: password_hash / password_verify)
        if ($email === 'daniel@example.com' && $password === '1234') {
            $_SESSION['user_id'] = 'u-123';
            $_SESSION['email']   = $email;
            $_SESSION['role']    = 'student';

            $this->json(200, ['message' => 'Login erfolgreich']);
            return;
        }
        $this->json(401, ['error' => 'Invalid credentials']);
    }

    // GET /restapi.php/auth/me
    public function me()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->json(401, ['error' => 'Nicht eingeloggt']);
            return;
        }
        $this->json(200, [
            'id'    => $_SESSION['user_id'],
            'email' => $_SESSION['email'] ?? null,
            'role'  => $_SESSION['role'] ?? null
        ]);
    }

    // POST /restapi.php/auth/logout
    public function logout()
    {
        // Session leeren und Cookie invalidieren
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        $this->json(200, ['message' => 'Logged out']);
    }

    // Optional: GET /restapi.php/auth
    public function getAuth()
    {
        $this->me();
    }

    private function json(int $code, array $payload)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
