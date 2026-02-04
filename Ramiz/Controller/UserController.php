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

        $data = json_decode(file_get_contents('php://input'), true);

        // если json пустой или неверный — делаем массив
        if (!is_array($data)) {
            $data = [];
        }

        if (empty($data['email']) || empty($data['passwort'])) {
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

        if ($data['passwort'] !== $user['passwort']) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Password falsch'
            ]);
            return;
        }

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
