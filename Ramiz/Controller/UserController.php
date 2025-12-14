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

        // Сравнение plain-text пароля
        if ($password !== $user['password_hash']) {
            echo json_encode([
                "success" => false,
                "message" => "Wrong password"
            ]);
            return;
        }

        echo json_encode([
            "success" => true,
            "user" => [
                "id" => $user['id'],
                "username" => $user['username']
            ]
        ]);
    }

    // Метод для POST-запроса через REST API
    public function writeUser($data)
    {
        // Просто переиспользуем getUser
        $this->getUser($data);
    }

    // Legacy alias support: allow calling ?action=login or /user/login
    public function login($data = null)
    {
        $this->getUser($data);
    }
}
?>
