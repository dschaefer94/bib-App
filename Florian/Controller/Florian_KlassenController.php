<?php

namespace ppb\Controller;

use ppb\Model\Florian_KlassenModel;

/**
 * Florian_KlassenController
 *
 * Verantwortlich für alle Aktionen, die mit Klassen zu tun haben.
 * Dient als Schnittstelle zum Florian_KlassenModel.
 */
class Florian_KlassenController {

    private $klassenModel;

    /**
     * Konstruktor. Initialisiert das Klassen-Model.
     */
    public function __construct()
    {
        $this->klassenModel = new Florian_KlassenModel();
    }

    /**
     * Liefert alle Klassen.
     * Wrapper-Methode, die die Daten direkt vom Model holt.
     * Gedacht für die Verwendung in Views (z.B. für Dropdowns).
     *
     * @return array Eine Liste von Klassen-Arrays oder ein leeres Array bei einem Fehler.
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
     * Liefert eine einzelne Klasse anhand ihrer ID.
     * Wrapper-Methode, die die Daten direkt vom Model holt.
     *
     * @param int $klassen_id Die ID der zu suchenden Klasse.
     * @return array|null Das Klassen-Array bei Erfolg, sonst null.
     */
    public function getClassById(int $klassen_id): ?array {
        try {
            return $this->klassenModel->getClassById($klassen_id);
        } catch (\Exception $e) {
            return null;
        }
    }
}
