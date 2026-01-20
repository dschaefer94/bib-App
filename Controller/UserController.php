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
     * gibt nach Vollständigkeitsprüfung einen Registrierungsvorgang in Auftrag
     * @param mixed $data: alle übergebenen Registrierdaten
     * @return void, JSON mit Info, ob Benutzer angelegt wurde oder nicht
     */
    public function writeUser($data)
    {
        if (isset($data['benutzer_id']) && isset($data['passwort']) && isset($data['email']) && isset($data['name']) && isset($data['vorname']) && isset($data['klassenname'])) {
            echo json_encode((new UserModel())->insertUser($data), JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['benutzerAngelegt' => false,'grund' => 'Fehlende Registrierungsdaten'], JSON_UNESCAPED_UNICODE);
        }
    }
}
