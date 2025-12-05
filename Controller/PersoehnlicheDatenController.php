<?php

namespace ppb\Controller;

use ppb\Model\Florian_persoehnliche_datenModel;
use ppb\Model\Florian_KlassenModel;

/**
 * PersoehnlicheDatenController - Verwaltet alle Operationen für persönliche Daten
 * Orchestriert Models und bereitet Daten für die View vor
 */
class PersoehnlicheDatenController {

    private $persModel;
    private $klassenModel;

    public function __construct() {
        $this->persModel = new Florian_persoehnliche_datenModel();
        $this->klassenModel = new Florian_KlassenModel();
    }

    /**
     * Lädt persönliche Daten anhand der Benutzer_id
     * 
     * @param int $benutzer_id
     * @return array mit 'success' => bool, 'data' => array, 'error' => string
     */
    public function getPersonalData(int $benutzer_id): array {
        try {
            $personalData = $this->persModel->getPersonalDataByUserId($benutzer_id);
            
            if (!$personalData) {
                return [
                    'success' => false,
                    'error' => "Keine persönlichen Daten für Benutzer_id $benutzer_id gefunden."
                ];
            }

            // Klassenname laden (falls klassen_id vorhanden)
            $klassenName = '';
            if ($personalData['klassen_id']) {
                $classData = $this->klassenModel->getClassById((int)$personalData['klassen_id']);
                $klassenName = $classData['klassenname'] ?? '';
            }

            // Daten ergänzen
            $personalData['klassenname'] = $klassenName;

            return [
                'success' => true,
                'data' => $personalData
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Fehler beim Laden: " . $e->getMessage()
            ];
        }
    }

    /**
     * Speichert / Aktualisiert persönliche Daten
     * 
     * @param array $formData aus $_POST
     * @return array mit 'success' => bool, 'message' => string
     */
    public function savePersonalData(array $formData): array {
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

            // Persönliche Daten aktualisieren
            $updated = $this->persModel->updatePersonalData($benutzer_id, $name, $vorname, $klassen_id);

            if ($updated) {
                return [
                    'success' => true,
                    'message' => 'Persönliche Daten erfolgreich aktualisiert!'
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
     * Lädt alle verfügbaren Klassen (für Dropdown-Menü)
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

        // Pflichtfelder prüfen
        if (!$benutzer_id || !$name || !$vorname) {
            return [
                'valid' => false,
                'error' => 'Alle Pflichtfelder müssen gefüllt sein (Name, Vorname).'
            ];
        }

        // Mindestlänge prüfen
        if (strlen($name) < 2 || strlen($vorname) < 2) {
            return [
                'valid' => false,
                'error' => 'Name und Vorname müssen mindestens 2 Zeichen lang sein.'
            ];
        }

        return ['valid' => true];
    }
}
