<?php

namespace ppb\Controller;

use ppb\Model\UserModel;
use ppb\Model\ClassModel;

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
        if (!empty($data['passwort']) && !empty($data['email']) && !empty($data['name']) && !empty($data['vorname']) && !empty($data['klassenname'])) {
            echo json_encode((new UserModel())->insertUser($data), JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['benutzerAngelegt' => false, 'grund' => 'Fehlende Registrierungsdaten'], JSON_UNESCAPED_UNICODE);
        }
    }
    /**
     * Ramiz & Daniel
     * @return void
     */
    public function login($data)
    {
        $user = (new UserModel())->getUserByEmail($data['email']);

        if (empty($data['email']) || empty($data['passwort'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Email und Passwort sind erforderlich'
            ]);
            return;
        }

        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Benutzer nicht gefunden'
            ]);
            return;
        }

        if (!password_verify($data['passwort'], $user['passwort'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Login fehlgeschlagen']);
            return;
        }

        $_SESSION['user_id'] = $user['benutzer_id'];
        $_SESSION['klassenname'] = (new ClassModel())->selectClass($_SESSION['user_id'])[0]['klassenname'];

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Es läuft (Test)',
            'user' => [
                'id' => $user['benutzer_id'],
                'klassenname' => $_SESSION['klassenname'],
            ]
        ]);
    }
    /**
     * Daniel
     * Loggt den Benutzer aus, indem die Session zerstört wird
     * @return void
     */
    public function logout()
    {
        session_unset();
        session_destroy();
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Erfolgreich ausgeloggt']);
    }
}
