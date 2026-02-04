<?php

namespace SDP\Controller;

use SDP\Model\UserModel;
use SDP\Model\ClassModel;

class UserController
{
    public function __construct() {}
    /**
     * Daniel
     * gibt die Daten eines eingeloggten Users aus, wichtig beim Admin-Check
     * @return void
     */
    public function getUser()
    {
        if (isset($_SESSION['benutzer_id'])) {
            echo json_encode((new UserModel())->selectBenutzer($_SESSION['benutzer_id']), JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Nicht eingeloggt']);
        }
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

        $_SESSION['benutzer_id'] = $user['benutzer_id'];
        $_SESSION['klassenname'] = (new ClassModel())->selectClass($_SESSION['benutzer_id'])[0]['klassenname'];

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Es läuft (Test)',
            'user' => [
                'id' => $user['benutzer_id'],
                'vorname' => $user['vorname'],
                'nachname' => $user['nachname'],
                'email' => $user['email'],
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

    /**
     * Florian
     * @return void
     */
    public function profile()
    {
        if (!isset($_SESSION['benutzer_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Nicht authentifiziert']);
            return;
        }

        $id = $_SESSION['benutzer_id'];
        $userModel = new UserModel();
        $classModel = new ClassModel();

        $userData = $userModel->getUserData($id);
        $classes = $classModel->selectClass();

        if ($userData) {
            echo json_encode([
                'success' => true,
                'userData' => $userData,
                'klassen' => $classes
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Benutzer nicht gefunden'
            ]);
        }
    }
    /**
     * Florian
     * @param mixed $data
     * @return void
     */
    public function updateProfile($data)
    {
        if (!isset($_SESSION['benutzer_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Nicht authentifiziert']);
            return;
        }

        $userModel = new UserModel();

        $benutzer_id = $_SESSION['benutzer_id'];

        // Validation
        $errors = [];
        if (empty($data['name']) || empty($data['vorname'])) {
            $errors[] = 'Alle Pflichtfelder müssen gefüllt sein (Name, Vorname).';
        }
        if (!empty($data['passwort']) && $data['passwort'] !== ($data['passwort_confirm'] ?? '')) {
            $errors[] = 'Die Passwörter stimmen nicht überein.';
        }
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
            return;
        }

        $name = $data['name'] ?? '';
        $vorname = $data['vorname'] ?? '';
        $klassen_id = !empty($data['klassen_id']) ? (int)$data['klassen_id'] : null;
        $email = trim($data['email'] ?? '');
        $passwort = !empty($data['passwort']) ? password_hash($data['passwort'], PASSWORD_DEFAULT) : null;

        $pd_updated = $userModel->updatePersonalData($benutzer_id, $name, $vorname, $klassen_id);
        $user_updated = $userModel->updateUser($benutzer_id, $email, $passwort);

        if ($pd_updated || $user_updated) {
            $reloadedUser = $userModel->getUserData($benutzer_id);
            $classModel = new ClassModel();
            $classes = $classModel->selectClass();
            echo json_encode([
                'success' => true,
                'message' => 'Daten erfolgreich aktualisiert!',
                'userData' => $reloadedUser,
                'klassen' => $classes
            ]);
        } else {
            echo json_encode(['success' => true, 'message' => 'Keine Änderungen vorgenommen.']);
        }
    }
}
