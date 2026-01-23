<?php

namespace ppb\Controller;

use ppb\Model\PersonalDataModel;
use ppb\Model\ClassModel;

class PersonalDataController
{
    private $personalDataModel;

    public function __construct()
    {
        $this->personalDataModel = new PersonalDataModel();
    }

    /**
     * Florian
     * Lädt das Benutzerprofil für den aktuell angemeldeten Benutzer.
     * Holt Benutzerdaten und eine Liste aller verfügbaren Klassen.
     * @return void
     */
    public function loadProfile()
    {
        $response = [
            'success' => false,
            'error' => null,
            'userData' => null,
            'klassen' => []
        ];

        error_log('PersonalDataController::loadProfile called');
        error_log('Session benutzer_id: ' . ($_SESSION['benutzer_id'] ?? 'NOT SET'));

        if (!isset($_SESSION['benutzer_id'])) {
            $response['error'] = 'Benutzer ist nicht angemeldet.';
            http_response_code(401); // Unauthorized
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }

        $benutzer_id = $_SESSION['benutzer_id'];
        error_log('Getting user data for benutzer_id: ' . $benutzer_id);
        
        $userData = $this->personalDataModel->getUserData($benutzer_id);
        
        error_log('User data result: ' . ($userData ? 'found' : 'NOT FOUND'));

        if ($userData) {
            $response['success'] = true;
            $response['userData'] = $userData;
            $response['klassen'] = $this->personalDataModel->getAllClasses();
        } else {
            $response['error'] = 'Benutzer nicht gefunden.';
            http_response_code(404); // Not Found
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Florian
     * Aktualisiert die Daten des aktuell angemeldeten Benutzers.
     * @param array $postData Die vom Client gesendeten Formulardaten.
     * @return void
     */
    public function updateProfile($postData)
    {
        $response = [
            'success' => false,
            'message' => '',
            'error' => null
        ];

        if (!isset($_SESSION['benutzer_id'])) {
            $response['error'] = 'Benutzer ist nicht angemeldet.';
            http_response_code(401); // Unauthorized
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }

        $benutzer_id = $_SESSION['benutzer_id'];

        // Validate required fields
        if (!isset($postData['name']) || !isset($postData['vorname']) || !isset($postData['email'])) {
            $response['error'] = 'Name, Vorname und E-Mail sind Pflichtfelder.';
            http_response_code(400);
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            // Update personal data
            $klassen_id = !empty($postData['klassen_id']) ? (int)$postData['klassen_id'] : null;
            $this->personalDataModel->updatePersonalData($benutzer_id, $postData['name'], $postData['vorname'], $klassen_id);

            // Update user account (email and password)
            $passwort = !empty($postData['passwort']) ? password_hash($postData['passwort'], PASSWORD_BCRYPT) : null;
            $this->personalDataModel->updateUser($benutzer_id, $postData['email'], $passwort);
            
            $response['success'] = true;
            $response['message'] = 'Profil erfolgreich aktualisiert.';
            
            // Reload user data to send back the latest state
            $response['userData'] = $this->personalDataModel->getUserData($benutzer_id);
            $response['klassen'] = $this->personalDataModel->getAllClasses();

        } catch (\Exception $e) {
            $response['error'] = "Ein Fehler ist beim Speichern der Daten aufgetreten: " . $e->getMessage();
            http_response_code(500);
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Gibt alle verfügbaren Klassen zurück
     */
    public function getAllClasses()
    {
        try {
            ob_clean(); // Clear any output buffer
            $classModel = new ClassModel();
            $klassen = $classModel->getAllClasses();
            
            if (!is_array($klassen)) {
                $klassen = [];
            }
            
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($klassen, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Exception $e) {
            ob_clean();
            error_log('Error in getAllClasses: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
