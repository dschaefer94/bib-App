<?php

namespace ppb\Controller;

use ppb\Model\Florian_KlassenModel;

/**
 * Florian_KlassenController
 *
 * Zusammengeführter Controller für Klassen-bezogene Funktionalität.
 * Enthält:
 * - Legacy helper-Methoden (z.B. JSON-Ausgaben)
 * - MVC-Methoden zum Laden, Speichern und Validieren von Klassen
 */
class Florian_KlassenController {

    private $klassenModel;

    public function __construct()
    {
        $this->klassenModel = new Florian_KlassenModel();
    }

    // Alte helper-methoden
    /**
     * Legacy: Gibt Klassen als JSON aus (für einfache API-Endpunkte).
     *
     * @return void
     */
    public function getklassen()
    {
        echo json_encode($this->klassenModel->selectTask(), JSON_PRETTY_PRINT);
    }

    // MVC-Methoden aus KlassenController.php
    /**
     * Liefert alle Klassen in einem einheitlichen Antwort-Array.
     *
     * @return array ['success'=>bool, 'data'=>array|'error'=>string]
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
     * Liefert eine Klasse nach ID.
     *
     * @param int $klassen_id
     * @return array ['success'=>bool, 'data'=>array|'error'=>string]
     */
    public function getClassById(int $klassen_id): array {
        try {
            $klasse = $this->klassenModel->getClassById($klassen_id);
            if (!$klasse) {
                return [ 'success' => false, 'error' => "Klasse mit ID $klassen_id nicht gefunden." ];
            }
            return [ 'success' => true, 'data' => $klasse ];
        } catch (\Exception $e) {
            return [ 'success' => false, 'error' => "Fehler beim Laden der Klasse: " . $e->getMessage() ];
        }
    }

    /**
     * Speichert bzw. aktualisiert eine Klasse.
     * Erwartet `klassen_id` und `klassenname` im Formular-Array.
     *
     * @param array $formData
     * @return array ['success'=>bool, 'message'=>string]
     */
    public function saveClass(array $formData): array {
        try {
            $validation = $this->validateFormData($formData);
            if (!$validation['valid']) {
                return [ 'success' => false, 'message' => $validation['error'] ];
            }

            $klassen_id = (int)($formData['klassen_id'] ?? 0);
            $klassenname = $formData['klassenname'] ?? '';

            $updated = $this->klassenModel->updateClass($klassen_id, $klassenname);

            if ($updated) {
                return [ 'success' => true, 'message' => 'Klasse erfolgreich aktualisiert!' ];
            }

            return [ 'success' => false, 'message' => 'Fehler beim Speichern der Klasse.' ];
        } catch (\Exception $e) {
            return [ 'success' => false, 'message' => 'Fehler: ' . $e->getMessage() ];
        }
    }

    /**
     * Validiert das Formular für Klassen.
     *
     * @param array $data
     * @return array ['valid'=>bool, 'error'=>string|null]
     */
    private function validateFormData(array $data): array {
        $klassen_id = $data['klassen_id'] ?? null;
        $klassenname = $data['klassenname'] ?? '';

        if (!$klassen_id || !$klassenname) {
            return [ 'valid' => false, 'error' => 'Alle Felder sind erforderlich.' ];
        }

        if (strlen($klassenname) < 2) {
            return [ 'valid' => false, 'error' => 'Klassenname muss mindestens 2 Zeichen lang sein.' ];
        }

        return [ 'valid' => true ];
    }
}