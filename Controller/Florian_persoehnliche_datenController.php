<?php

namespace ppb\Controller;

use ppb\Model\Florian_persoehnliche_datenModel;
use ppb\Model\Florian_KlassenModel;

/**
 * Florian_persoehnliche_datenController
 *
 * Controller für persönliche Daten (Persoenliche_Daten).
 * Enthält:
 * - Legacy helper-Methoden für JSON-Ausgaben
 * - MVC-Methoden zum Laden, Validieren und Speichern persönlicher Daten
 */
class Florian_persoehnliche_datenController {

    private $persModel;
    private $klassenModel;

    public function __construct()
    {
        $this->persModel = new Florian_persoehnliche_datenModel();
        $this->klassenModel = new Florian_KlassenModel();
    }

    // Alte helper-methoden
    /**
     * Legacy: Gibt persönliche Daten als JSON aus (für Debug/Legacy-API).
     *
     * @return void
     */
    public function getpersoehnliche_daten()
    {
        echo json_encode($this->persModel->selectProject(), JSON_PRETTY_PRINT);
    }

    // MVC-Methoden aus PersoehnlicheDatenController.php
    /**
     * Liefert persönliche Daten für einen Benutzer inkl. Klassenname.
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
            return [ 'success' => false, 'error' => "Fehler beim Laden: " . $e->getMessage() ];
        }
    }

    /**
     * Speichert persönliche Daten (Name, Vorname, Klassenzuordnung).
     *
     * @param array $formData
     * @return array ['success'=>bool, 'message'=>string]
     */
    public function savePersonalData(array $formData): array {
        try {
            $validation = $this->validateFormData($formData);
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
     * Liefert alle Klassen (wird von Views/Dropdowns verwendet).
     *
     * @return array
     */
    public function getAllClasses(): array {
        try {
            return $this->klassenModel->getAllClasses();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Validiert persönliche Daten vor dem Speichern.
     *
     * @param array $data
     * @return array ['valid'=>bool, 'error'=>string|null]
     */
    private function validateFormData(array $data): array {
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