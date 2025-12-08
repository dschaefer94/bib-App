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
        $newId = $model->insertUser($data); // gibt jetzt UUID zurück
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'benutzer_id' => $newId,
            'email'       => $data['email'] ?? null
        ], JSON_UNESCAPED_UNICODE);
    }
}
