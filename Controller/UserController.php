<?php

namespace ppb\Controller;

use ppb\Model\UserModel;

class UserController
{
    public function __construct() {}

    public function getUser()
    {
        $model = new UserModel();
        $rows = $model->selectUser();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    }

    public function writeUser($data)
    {
        $model = new UserModel();
        $newId = $model->insertUser($data);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'benutzer_id' => $newId,          // UUID oder null
            'email' => $data['email'] ?? null
        ], JSON_UNESCAPED_UNICODE);
    }
}
