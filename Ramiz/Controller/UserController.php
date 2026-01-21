<?php
namespace ppb\Controller;

use ppb\Model\UserModel;

class UserController
{
    private $model;

    public function __construct()
    {
        $this->model = new UserModel();
    }

    
    public function login()
    {
        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Email und Passwort sind erforderlich'
            ]);
            return;
        }

        $user = $this->model->getUserByEmail($data['email']);

        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Benutzer nicht gefunden'
            ]);
            return;
        }

 
        // Kurze Lösung. Verglecih direkt den Password ohne HASH
        // ───────────────────────────────
        if ($data['password'] !== $user['passwort']) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Password falsch '
            ]);
            return;
        }

        //Session
        $_SESSION['user_id'] = $user['benutzer_id'];

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Es läuft (Test)',
            'user' => [
                'id' => $user['benutzer_id'],
                'email' => $user['email']
            ]
        ]);
    }
}