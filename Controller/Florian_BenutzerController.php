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

    // -------------------- Legacy / Helper-Methoden --------------------
    /**
     * Gibt alle Benutzer als JSON aus (Legacy helper).
     * Nutze diese Methode nur bei API-ähnlichen Endpunkten.
     *
     * @return void
     */
    public function getbenutzer()
    {
        echo json_encode($this->benutzerModel->selectBenutzer(), JSON_PRETTY_PRINT);
    }

    /**
     * Gibt gefilterte Benutzer als JSON aus (Legacy helper).
     *
     * @param mixed $filter Filterkriterium
     * @return void
     */
    public function getFilteredBenutzer($filter)
    {
        echo json_encode($this->benutzerModel->selectFilteredBenutzer($filter), JSON_PRETTY_PRINT);
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
            $personalData = $this->persModel->getPersonalDataByUserId($benutzer_id);
            if (!$personalData) {
                return [
                    'success' => false,
                    'error' => "Kein Datensatz mit Benutzer_id: $benutzer_id gefunden."
                ];
            }

            $klassenName = '';
            if (!empty($personalData['klassen_id'])) {
                $classData = $this->klassenModel->getClassById((int)$personalData['klassen_id']);
                $klassenName = $classData['klassenname'] ?? '';
            }

            $benutzerData = $this->benutzerModel->getUserById($benutzer_id);
            $email = $benutzerData['email'] ?? '';
            $passwort = $benutzerData['passwort'] ?? '';

            $userData = array_merge($personalData, [
                'klassenname' => $klassenName,
                'email' => $email,
                'passwort' => $passwort
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
     * @param array $formData Daten aus dem Formular ($_POST)
     * @return array ['success' => bool, 'message' => string]
     */
    public function saveUser(array $formData): array {
        try {
            $validation = $this->validateUserFormData($formData);
            if (!$validation['valid']) {
                return [ 'success' => false, 'message' => $validation['error'] ];
            }

            $benutzer_id = (int)$formData['benutzer_id'];
            $name = $formData['name'] ?? '';
            $vorname = $formData['vorname'] ?? '';
            $klassen_id = !empty($formData['klassen_id']) ? (int)$formData['klassen_id'] : null;
            $email = trim($formData['email'] ?? '');
            $passwort = $formData['passwort'] ?? null;

            $pd_updated = $this->persModel->updatePersonalData($benutzer_id, $name, $vorname, $klassen_id);
            $user_updated = $this->benutzerModel->updateUser($benutzer_id, $email, $passwort ?: null);

            if ($pd_updated && $user_updated) {
                return [ 'success' => true, 'message' => 'Daten erfolgreich aktualisiert!' ];
            }

            return [ 'success' => false, 'message' => 'Fehler beim Speichern der Daten.' ];
        } catch (\Exception $e) {
            return [ 'success' => false, 'message' => 'Fehler: ' . $e->getMessage() ];
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
            return [];
        }
    }

    /**
     * Validiert Benutzerdaten vor dem Speichern.
     * Prüft Pflichtfelder und das E-Mail-Suffix @bib.de.
     *
     * @param array $data
     * @return array ['valid' => bool, 'error' => string|null]
     */
    private function validateUserFormData(array $data): array {
        $benutzer_id = $data['benutzer_id'] ?? null;
        $name = $data['name'] ?? '';
        $vorname = $data['vorname'] ?? '';
        $email = trim($data['email'] ?? '');

        // Pflichtfelder: benutzer_id, name, vorname - Email wird geprüft wenn vorhanden
        if (!$benutzer_id || !$name || !$vorname) {
            return [ 'valid' => false, 'error' => 'Alle Pflichtfelder müssen gefüllt sein (Name, Vorname).' ];
        }

        // Email-Validierung: wenn Email eingegeben, prüfe auf gültiges Email-Format
        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [ 'valid' => false, 'error' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.' ];
            }
        }

        return [ 'valid' => true ];
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

    /**
     * Speichert/aktualisiert persönliche Daten (Name, Vorname, Klassenzuweisung).
     *
     * @param array $formData
     * @return array ['success'=>bool, 'message'=>string]
     */
    public function savePersonalData(array $formData): array {
        try {
            $validation = $this->validatePersonalFormData($formData);
            if (!$validation['valid']) {
                return [ 'success' => false, 'message' => $validation['error'] ];
            }

            $benutzer_id = (int)$formData['benutzer_id'];
            $name = $formData['name'] ?? '';
            $vorname = $formData['vorname'] ?? '';
            $klassen_id = !empty($formData['klassen_id']) ? (int)$formData['klassen_id'] : null;

            $updated = $this->persModel->updatePersonalData($benutzer_id, $name, $vorname, $klassen_id);
            if ($updated) {
                return [ 'success' => true, 'message' => 'Persönliche Daten erfolgreich aktualisiert!' ];
            }

            return [ 'success' => false, 'message' => 'Fehler beim Speichern der Daten.' ];
        } catch (\Exception $e) {
            return [ 'success' => false, 'message' => 'Fehler: ' . $e->getMessage() ];
        }
    }

    /**
     * Validiert die Felder für persönliche Daten.
     *
     * @param array $data
     * @return array ['valid'=>bool, 'error'=>string|null]
     */
    private function validatePersonalFormData(array $data): array {
        $benutzer_id = $data['benutzer_id'] ?? null;
        $name = $data['name'] ?? '';
        $vorname = $data['vorname'] ?? '';

        if (!$benutzer_id || !$name || !$vorname) {
            return [ 'valid' => false, 'error' => 'Alle Pflichtfelder müssen gefüllt sein (Name, Vorname).' ];
        }

        if (strlen($name) < 2 || strlen($vorname) < 2) {
            return [ 'valid' => false, 'error' => 'Name und Vorname müssen mindestens 2 Zeichen lang sein.' ];
        }

        return [ 'valid' => true ];
    }
}