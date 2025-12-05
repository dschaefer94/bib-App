<?php

namespace ppb\Controller;

use ppb\Model\Florian_KlassenModel;

/**
 * KlassenController - Verwaltet alle Klassen-bezogenen Operationen
 * Orchestriert das Klassen-Model und bereitet Daten für die View vor
 */
class KlassenController {

    private $klassenModel;

    public function __construct() {
        $this->klassenModel = new Florian_KlassenModel();
    }

    /**
     * Lädt alle verfügbaren Klassen
     * 
     * @return array mit 'success' => bool, 'data' => array, 'error' => string
     */
    public function getAllClasses(): array {
        try {
            $klassen = $this->klassenModel->getAllClasses();
            return [
                'success' => true,
                'data' => $klassen
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Fehler beim Laden der Klassen: " . $e->getMessage()
            ];
        }
    }

    /**
     * Lädt eine einzelne Klasse anhand der ID
     * 
     * @param int $klassen_id
     * @return array mit 'success' => bool, 'data' => array, 'error' => string
     */
    public function getClassById(int $klassen_id): array {
        try {
            $klasse = $this->klassenModel->getClassById($klassen_id);
            
            if (!$klasse) {
                return [
                    'success' => false,
                    'error' => "Klasse mit ID $klassen_id nicht gefunden."
                ];
            }

            return [
                'success' => true,
                'data' => $klasse
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Fehler beim Laden der Klasse: " . $e->getMessage()
            ];
        }
    }

    /**
     * Speichert / Aktualisiert eine Klasse
     * 
     * @param array $formData aus $_POST
     * @return array mit 'success' => bool, 'message' => string
     */
    public function saveClass(array $formData): array {
        try {
            // Daten validieren
            $validation = $this->validateFormData($formData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['error']
                ];
            }

            $klassen_id = (int)$formData['klassen_id'];
            $klassenname = $formData['klassenname'] ?? '';

            // Klasse aktualisieren
            $updated = $this->klassenModel->updateClass($klassen_id, $klassenname);

            if ($updated) {
                return [
                    'success' => true,
                    'message' => 'Klasse erfolgreich aktualisiert!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Fehler beim Speichern der Klasse.'
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
     * Validiert die Formulardaten
     * 
     * @param array $data
     * @return array mit 'valid' => bool, 'error' => string
     */
    private function validateFormData(array $data): array {
        $klassen_id = $data['klassen_id'] ?? null;
        $klassenname = $data['klassenname'] ?? '';

        // Pflichtfelder prüfen
        if (!$klassen_id || !$klassenname) {
            return [
                'valid' => false,
                'error' => 'Alle Felder sind erforderlich.'
            ];
        }

        // Mindestlänge prüfen
        if (strlen($klassenname) < 2) {
            return [
                'valid' => false,
                'error' => 'Klassenname muss mindestens 2 Zeichen lang sein.'
            ];
        }

        return ['valid' => true];
    }
}
