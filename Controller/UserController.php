<?php

namespace ppb\Controller;

use ppb\Model\UserModel;

class UserController
{
    public function __construct() {}

    /**
     * Daniel
     * Abfrage der benutzer_id und email aller User (zum Testen)
     * @return void, JSON-echo
     */
    public function getUser()
    {
        $model = new UserModel();
        $rows = $model->selectUser();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Daniel
     * gibt einen Registrierungsvorgang in Auftrag
     * @param mixed $data: alle Registrierdaten
     * @return void, JSON mit Info, ob Benutzer angelegt wurde oder bereits existiert
     */
    public function writeUser($data)
    {
        $model = new UserModel();
        $benutzerAngelegt = $model->insertUser($data);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            $benutzerAngelegt
        ], JSON_UNESCAPED_UNICODE);
    }
}
