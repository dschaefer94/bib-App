<?php

namespace ppb\Controller;

use ppb\Model\Florian_BenutzerModel;
use ppb\Model\Florian_persoehnliche_datenModel;
use ppb\Model\Florian_KlassenModel;

/**
 * Florian_BenutzerController
 *
 * Dieser zusammengeführte Controller enthält: 
 * - Helper-Methoden für JSON-Ausgaben (Legacy)
 * - MVC-Methoden zum Laden und Speichern von Benutzerdaten
 * - Methoden zur Verwaltung der persönlichen Daten (Persoenliche_Daten)
 *
 * Verantwortlichkeiten:
 * - Orchestriert die Models `Florian_BenutzerModel`, `Florian_persoehnliche_datenModel` und `Florian_KlassenModel`
 * - Führt Validierungen durch und liefert konsistente Antwort-Arrays
 */
class Florian_BenutzerController {

    private $benutzerModel;
    private $persModel;
    private $klassenModel;

    public function __construct()
    {
        $this->benutzerModel = new Florian_BenutzerModel();
        $this->persModel = new Florian_persoehnliche_datenModel();
        $this->klassenModel = new Florian_KlassenModel();
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
            // Rufe die konsolidierte Methode für persönliche Daten ab
            $personalDataResult = $this->getPersonalData($benutzer_id);
            if (!$personalDataResult['success']) {
                return $personalDataResult; // Fehler weitergeben
            }

            $benutzerData = $this->benutzerModel->getUserById($benutzer_id);
            if (!$benutzerData) {
                // Dies sollte nicht passieren, wenn persönliche Daten existieren, aber als Sicherheitsnetz
                return [
                    'success' => false,
                    'error' => "Keine Login-Daten für Benutzer_id: $benutzer_id gefunden."
                ];
            }

            // Mische die Daten zusammen
            $userData = array_merge($personalDataResult['data'], [
                'email' => $benutzerData['email'] ?? '',
                'passwort' => $benutzerData['passwort'] ?? ''
            ]);

            return [
                'success' => true,
                'data' => $userData
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Fehler beim Laden: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Speichert Benutzer- und persönliche Daten.
     * Führt Validierung durch und ruft Model-Methoden für Updates auf.
     *
     * @param array $formData Daten aus dem Formular ($_POST oder API)
     * @return array ['success' => bool, 'message' => string]
     */
    public function saveUser(array $formData): array {
        try {
            // Schritt 1: Validierung der Formulardaten
            $validation = $this->validateUserData($formData);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['error']];
            }

            // Schritt 2: Daten extrahieren und vorbereiten
            $benutzer_id = (int)$formData['benutzer_id'];
            $name = $formData['name'] ?? '';
            $vorname = $formData['vorname'] ?? '';
            $klassen_id = !empty($formData['klassen_id']) ? (int)$formData['klassen_id'] : null;
            $email = trim($formData['email'] ?? '');
            
            // Passwort nur aktualisieren, wenn es explizit übergeben wird
            $passwort = null;
            if (isset($formData['passwort']) && $formData['passwort'] !== '') {
                $passwort = $formData['passwort'];
            }

            // Schritt 3: Daten in der Datenbank aktualisieren
            $pd_updated = $this->persModel->updatePersonalData($benutzer_id, $name, $vorname, $klassen_id);
            $user_updated = $this->benutzerModel->updateUser($benutzer_id, $email, $passwort);

            if ($pd_updated || $user_updated) {
                return ['success' => true, 'message' => 'Daten erfolgreich aktualisiert!'];
            }

            // Fall, wenn keine Änderungen vorgenommen wurden, aber auch kein Fehler auftrat
            return ['success' => true, 'message' => 'Keine Änderungen vorgenommen.'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Fehler beim Speichern: ' . $e->getMessage()];
        }
    }

    /**
     * Liefert alle Klassen (für Dropdowns in Views).
     *
     * @return array Liste von Klassen oder leeres Array bei Fehler
     */
    public function getAllClasses(): array {
        try {
            return $this->klassenModel->getAllClasses();
        } catch (\Exception $e) {
            // Im Fehlerfall ein leeres Array zurückgeben, um die UI nicht zu brechen
            return [];
        }
    }

    /**
     * Validiert die vollständigen Benutzerdaten vor dem Speichern.
     *
     * @param array $data
     * @return array ['valid' => bool, 'error' => string|null]
     */
    private function validateUserData(array $data): array {
        $benutzer_id = $data['benutzer_id'] ?? null;
        $name = $data['name'] ?? '';
        $vorname = $data['vorname'] ?? '';
        $email = trim($data['email'] ?? '');

        // Pflichtfelder
        if (!$benutzer_id || !$name || !$vorname) {
            return ['valid' => false, 'error' => 'Alle Pflichtfelder müssen gefüllt sein (Name, Vorname).'];
        }

        // Namenslänge prüfen
        if (strlen($name) < 2 || strlen($vorname) < 2) {
            return ['valid' => false, 'error' => 'Name und Vorname müssen mindestens 2 Zeichen lang sein.'];
        }

        // E-Mail-Validierung
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'error' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.'];
        }

        return ['valid' => true, 'error' => null];
    }

    // -------------------- Methoden für persönliche Daten --------------------
    /**
     * Liefert persönliche Daten für eine Benutzer-ID (inkl. Klassenname).
     *
     * @param int $benutzer_id
     * @return array ['success'=>bool, 'data'=>array|null, 'error'=>string|null]
     */
    public function getPersonalData(int $benutzer_id): array {
        try {
            $personalData = $this->persModel->getPersonalDataByUserId($benutzer_id);
            if (!$personalData) {
                return [ 'success' => false, 'error' => "Keine persönlichen Daten für Benutzer_id $benutzer_id gefunden." ];
            }

            $klassenName = '';
            if (!empty($personalData['klassen_id'])) {
                $classData = $this->klassenModel->getClassById((int)$personalData['klassen_id']);
                $klassenName = $classData['klassenname'] ?? '';
            }

            $personalData['klassenname'] = $klassenName;

            return [ 'success' => true, 'data' => $personalData ];
        } catch (\Exception $e) {
            return [ 'success' => false, 'error' => 'Fehler beim Laden: ' . $e->getMessage() ];
        }
    }

    // -------------------- REST API Endpunkte --------------------
    /**
     * GET /api/benutzer - Gibt alle Benutzer als JSON aus
     *
     * @return void
     */
    public function getBenutzerApi() {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $this->benutzerModel->selectBenutzer()
        ], JSON_PRETTY_PRINT);
    }

    /**
     * GET /api/benutzer/{id} - Gibt einen Benutzer mit persönlichen Daten aus
     *
     * @param int $id Benutzer_id
     * @return void
     */
    public function getBenutzerByIdApi(int $id) {
        header('Content-Type: application/json');
        $result = $this->loadUser($id);
        echo json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * POST /api/benutzer
     * PUT /api/benutzer/{id} - Erstellt oder aktualisiert einen Benutzer
     *
     * @param int|null $id
     * @return void
     */
    public function saveBenutzerApi(int $id = null) {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ungültige JSON-Daten']);
            return;
        }

        // Wenn eine ID in der URL übergeben wird (PUT-Request), hat diese Vorrang
        if ($id !== null) {
            $data['benutzer_id'] = $id;
        }

        $result = $this->saveUser($data);
        http_response_code($result['success'] ? 200 : 400);
        echo json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * DELETE /api/benutzer/{id} - Löscht einen Benutzer
     *
     * @param int $id Benutzer_id
     * @return void
     */
    public function deleteBenutzerApi(int $id) {
        header('Content-Type: application/json');
        
        try {
            $deleted = $this->benutzerModel->deleteUser($id);
            if ($deleted) {
                echo json_encode(['success' => true, 'message' => 'Benutzer erfolgreich gelöscht.']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Benutzer nicht gefunden oder konnte nicht gelöscht werden.']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Serverfehler: ' . $e->getMessage()]);
        }
    }
}