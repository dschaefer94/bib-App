<?php

namespace ppb\Controller;

use ppb\Controller\Florian_Persoenliche_datenController;
use ppb\Model\Florian_BenutzerModel;

/**
 * Florian_BenutzerController
 *
 * Verantwortlichkeiten:
 * - Orchestriert die Controller `Florian_persoehnliche_datenController` und `Florian_BenutzerModel`.
 * - Stellt die Hauptmethoden zum Laden und Speichern von kombinierten Benutzerdaten bereit.
 * - Führt Validierungen durch und liefert konsistente Antwort-Arrays.
 * - Stellt die API-Endpunkte für Benutzer bereit.
 */
class Florian_BenutzerController {

    private $benutzerModel;
    private $persDatenController;

    public function __construct()
    {
        $this->benutzerModel = new Florian_BenutzerModel();
        $this->persDatenController = new Florian_Persoenliche_datenController();
    }

    // -------------------- Benutzer-spezifische MVC-Methoden --------------------
    /**
     * Lädt alle benötigten Benutzerdaten (persönliche Daten + Login-Daten + Klassenname)
     *
     * @param int $benutzer_id
     * @return array ['success' => bool, 'data' => array|null, 'error' => string|null]
     */
    public function loadUser(int $benutzer_id): array {
        try {
            $personalDataResult = $this->persDatenController->getPersonalData($benutzer_id);
            if (!$personalDataResult['success']) {
                return $personalDataResult; // Fehler weitergeben
            }

            $benutzerData = $this->benutzerModel->getUserById($benutzer_id);
            if (!$benutzerData) {
                return ['success' => false, 'error' => "Keine Login-Daten für Benutzer_id: $benutzer_id gefunden."];
            }

            // Mische die Daten zusammen. Das Passwort wird aus Sicherheitsgründen nicht zurückgegeben.
            $userData = array_merge($personalDataResult['data'], [
                'email' => $benutzerData['email'] ?? ''
            ]);

            return ['success' => true, 'data' => $userData];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Fehler beim Laden: ' . $e->getMessage()];
        }
    }

    /**
     * Speichert Benutzer- und persönliche Daten.
     * Führt Validierung durch und ruft Model-Methoden für Updates auf.
     *
     * @param array $formData Daten aus dem Formular ($_POST oder API)
     * @return array ['success' => bool, 'message' => string|null, 'error' => string|null]
     */
    public function saveUser(array $formData): array {
        try {
            $validation = $this->validateUserData($formData);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => $validation['error']];
            }

            $benutzer_id = (int)$formData['benutzer_id'];
            $name = $formData['name'] ?? '';
            $vorname = $formData['vorname'] ?? '';
            $klassen_id = !empty($formData['klassen_id']) ? (int)$formData['klassen_id'] : null;
            $email = trim($formData['email'] ?? '');
            $passwort = !empty($formData['passwort']) ? $formData['passwort'] : null;

            $pd_updated = $this->persDatenController->updatePersonalData($benutzer_id, $name, $vorname, $klassen_id);
            $user_updated = $this->benutzerModel->updateUser($benutzer_id, $email, $passwort);

            if ($pd_updated || $user_updated) {
                return ['success' => true, 'message' => 'Daten erfolgreich aktualisiert!'];
            }

            return ['success' => true, 'message' => 'Keine Änderungen vorgenommen.'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Fehler beim Speichern: ' . $e->getMessage()];
        }
    }

    /**
     * Validiert die vollständigen Benutzerdaten vor dem Speichern. Sammelt alle Fehler.
     *
     * @param array $data
     * @return array ['valid' => bool, 'error' => string|null]
     */
    private function validateUserData(array $data): array {
        $errors = [];

        if (empty($data['benutzer_id']) || empty($data['name']) || empty($data['vorname'])) {
            $errors[] = 'Alle Pflichtfelder müssen gefüllt sein (Name, Vorname).';
        }

        if (strlen($data['name'] ?? '') < 2 || strlen($data['vorname'] ?? '') < 2) {
            $errors[] = 'Name und Vorname müssen mindestens 2 Zeichen lang sein.';
        }

        $email = trim($data['email'] ?? '');
        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
            } elseif (!str_ends_with($email, '@bib.de')) { // PHP 8+
                $errors[] = 'Bitte nur Bib E-Mail Adressen eingeben.';
            }
        }

        // Passwort-Validierung
        if (!empty($data['passwort'])) {
            if ($data['passwort'] !== ($data['passwort_confirm'] ?? '')) {
                $errors[] = 'Die Passwörter stimmen nicht überein.';
            }
        }

        if (!empty($errors)) {
            return ['valid' => false, 'error' => implode(' ', $errors)];
        }

        return ['valid' => true, 'error' => null];
    }

    // -------------------- REST API Endpunkte --------------------

    /**
     * Sendet eine JSON-Antwort mit entsprechendem HTTP-Statuscode.
     *
     * @param array $data
     * @param int $statusCode
     */
    private function sendJsonResponse(array $data, int $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * GET /api/benutzer - Gibt alle Benutzer als JSON aus
     */
    public function getBenutzerApi() {
        $data = $this->benutzerModel->selectBenutzer();
        $this->sendJsonResponse(['success' => true, 'data' => $data]);
    }

    /**
     * GET /api/benutzer/{id} - Gibt einen Benutzer mit persönlichen Daten aus
     *
     * @param int $id Benutzer_id
     */
    public function getBenutzerByIdApi(int $id) {
        $result = $this->loadUser($id);
        $statusCode = $result['success'] ? 200 : 404; // 404, wenn Benutzer nicht gefunden wurde
        $this->sendJsonResponse($result, $statusCode);
    }

    /**
     * API-Endpunkt (POST/PUT): Erstellt oder aktualisiert einen Benutzer.
     *
     * @param int|null $id Die ID des Benutzers für PUT-Requests.
     */
    public function saveBenutzerApi(int $id = null) {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Ungültige JSON-Daten'], 400);
            return;
        }

        if ($id !== null) {
            $data['benutzer_id'] = $id;
        }

        $result = $this->saveUser($data);
        $statusCode = $result['success'] ? 200 : 400; // 400 bei Validierungs- oder Speicherfehler
        $this->sendJsonResponse($result, $statusCode);
    }

    /**
     * DELETE /api/benutzer/{id} - Löscht einen Benutzer
     *
     * @param int $id Benutzer_id
     */
    public function deleteBenutzerApi(int $id) {
        try {
            $deleted = $this->benutzerModel->deleteUser($id);
            if ($deleted) {
                $this->sendJsonResponse(['success' => true, 'message' => 'Benutzer erfolgreich gelöscht.']);
            } else {
                $this->sendJsonResponse(['success' => false, 'error' => 'Benutzer nicht gefunden.'], 404);
            }
        } catch (\Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Serverfehler: ' . $e->getMessage()], 500);
        }
    }
}