<?php

namespace ppb\Controller;

use ppb\Model\Florian_BenutzerModel;
use ppb\Model\Florian_persoehnliche_datenModel;
use ppb\Model\Florian_KlassenModel;

/**
 * BenutzerController - Verwaltet alle Benutzer-bezogenen Operationen
 * Orchestriert Models und bereitet Daten für die View vor
 */
class BenutzerController {

    private $benutzerModel;
    private $persModel;
    private $klassenModel;

    public function __construct() {
        $this->benutzerModel = new Florian_BenutzerModel();
        $this->persModel = new Florian_persoehnliche_datenModel();
        $this->klassenModel = new Florian_KlassenModel();
    }

    /**
     * Verarbeitet GET-Anfrage: Lädt Benutzerdaten anhand der Benutzer_id
     * 
     * @param int $benutzer_id
     * @return array mit 'success' => bool, 'data' => array, 'error' => string
     */
    public function loadUser(int $benutzer_id): array {
        try {
            // Persönliche Daten laden
            $personalData = $this->persModel->getPersonalDataByUserId($benutzer_id);
            
            if (!$personalData) {
                return [
                    'success' => false,
                    'error' => "Kein Datensatz mit Benutzer_id: $benutzer_id gefunden."
                ];
            }

            // Klassenname laden (falls klassen_id vorhanden)
            $klassenName = '';
            if ($personalData['klassen_id']) {
                $classData = $this->klassenModel->getClassById((int)$personalData['klassen_id']);
                $klassenName = $classData['klassenname'] ?? '';
            }

            // Benutzerdaten (Email, Passwort) laden
            $benutzerData = $this->benutzerModel->getUserById($benutzer_id);
            $email = $benutzerData['email'] ?? '';
            $passwort = $benutzerData['passwort'] ?? '';

            // Alles zusammenstellen
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
                'error' => "Fehler beim Laden: " . $e->getMessage()
            ];
        }
    }

    /**
     * Verarbeitet POST-Anfrage: Speichert geänderte Benutzerdaten
     * 
     * @param array $formData aus $_POST
     * @return array mit 'success' => bool, 'message' => string
     */
    public function saveUser(array $formData): array {
        try {
            // Daten validieren
            $validation = $this->validateFormData($formData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['error']
                ];
            }

            $benutzer_id = (int)$formData['benutzer_id'];
            $name = $formData['name'] ?? '';
            $vorname = $formData['vorname'] ?? '';
            $klassen_id = $formData['klassen_id'] ? (int)$formData['klassen_id'] : null;
            $email = $formData['email'] ?? '';
            $passwort = $formData['passwort'] ?? null;

            // Persönliche Daten aktualisieren
            $pd_updated = $this->persModel->updatePersonalData($benutzer_id, $name, $vorname, $klassen_id);
            
            // Benutzerdaten aktualisieren
            $user_updated = $this->benutzerModel->updateUser($benutzer_id, $email, $passwort ?: null);

            if ($pd_updated && $user_updated) {
                return [
                    'success' => true,
                    'message' => 'Daten erfolgreich aktualisiert!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Fehler beim Speichern der Daten.'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Fehler: " . $e->getMessage()
            ];
        }
    }

    /**
     * Lädt alle verfügbaren Klassen
     * 
     * @return array Liste der Klassen
     */
    public function getAllClasses(): array {
        try {
            return $this->klassenModel->getAllClasses();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Validiert die Formulardaten
     * 
     * @param array $data
     * @return array mit 'valid' => bool, 'error' => string
     */
    private function validateFormData(array $data): array {
        $benutzer_id = $data['benutzer_id'] ?? null;
        $name = $data['name'] ?? '';
        $vorname = $data['vorname'] ?? '';
        $email = $data['email'] ?? '';

        // Pflichtfelder prüfen
        if (!$benutzer_id || !$name || !$vorname || !$email) {
            return [
                'valid' => false,
                'error' => 'Alle Pflichtfelder müssen gefüllt sein (Name, Vorname, E-Mail).'
            ];
        }

        // Email-Format prüfen (muss mit @bib.de enden)
        $required_suffix = '@bib.de';
        if (function_exists('str_ends_with')) {
            $valid_email = str_ends_with($email, $required_suffix);
        } else {
            $valid_email = (substr($email, -strlen($required_suffix)) === $required_suffix);
        }

        if (!$valid_email) {
            return [
                'valid' => false,
                'error' => 'Die E-Mail-Adresse muss mit @bib.de enden!'
            ];
        }

        return ['valid' => true];
    }
}
