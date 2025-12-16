<?php
namespace ppb\Controller;

use ppb\Model\UserModel;

class UserController
{
    // Метод для GET-запроса (можно тестировать через браузер)
    public function getUser($data = null)
    {
        $data = $data ?? $_GET;

        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $model = new UserModel();
        $user = $model->getUserByUsername($username);

        if (!$user) {
            echo json_encode([
                "success" => false,
                "message" => "User not found"
            ]);
            return;
        }

        if ($password !== $user['passwort']) {
            echo json_encode([
                "success" => false,
                "message" => "Wrong password"
            ]);
            return;
        }

        echo json_encode([
            "success" => true,
            "user" => [
                "id" => $user['benutzer_id'],
                "email" => $user['email']
            ]
        ]);
    }

    public function writeUser($data)
    {
        $this->getUser($data);
    }

    public function login($data = null)
    {
        $this->getUser($data);
    }
}
