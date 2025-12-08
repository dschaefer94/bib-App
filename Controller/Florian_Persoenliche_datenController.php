<?php

namespace ppb\Controller;

use ppb\Model\Florian_Persoenliche_datenModel;

/**
 * Florian_Persoenliche_datenController
 *
 * Verantwortlich für alle Aktionen, die mit den persönlichen Daten eines Benutzers zu tun haben.
 * Koordiniert das Model für persönliche Daten und den Klassen-Controller.
 */
class Florian_Persoenliche_datenController {

    private $persModel;
    private $klassenController;

    /**
     * Konstruktor.
     * Initialisiert das Model für persönliche Daten und den Klassen-Controller.
     */
    public function __construct()
    {
        $this->persModel = new Florian_Persoenliche_datenModel();
        $this->klassenController = new Florian_KlassenController();
    }

    /**
     * Holt die persönlichen Daten für einen Benutzer und reichert sie mit dem Klassennamen an.
     *
     * @param int $benutzer_id Die ID des Benutzers.
     * @return array Ein Array, das den Erfolgsstatus und bei Erfolg die Daten enthält.
     *               ['success'=>bool, 'data'=>array|null, 'error'=>string|null]
     */
    public function getPersonalData(int $benutzer_id): array {
        try {
            $personalData = $this->persModel->getPersonalDataByUserId($benutzer_id);
            if (!$personalData) {
                return [ 'success' => false, 'error' => "Keine persönlichen Daten für Benutzer_id $benutzer_id gefunden." ];
            }

            $klassenName = '';
            if (!empty($personalData['klassen_id'])) {
                $classData = $this->klassenController->getClassById((int)$personalData['klassen_id']);
                $klassenName = $classData['klassenname'] ?? '';
            }

            $personalData['klassenname'] = $klassenName;

            return [ 'success' => true, 'data' => $personalData ];
        } catch (\Exception $e) {
            return [ 'success' => false, 'error' => "Fehler beim Laden: " . $e->getMessage() ];
        }
    }

    /**
     * Aktualisiert die persönlichen Daten eines Benutzers in der Datenbank.
     * Dient als Wrapper für die entsprechende Model-Methode.
     *
     * @param int $benutzer_id Die ID des Benutzers.
     * @param string $name Der neue Nachname.
     * @param string $vorname Der neue Vorname.
     * @param int|null $klassen_id Die neue Klassen-ID oder null.
     * @return bool True bei Erfolg, false bei einem Fehler.
     */
    public function updatePersonalData(int $benutzer_id, string $name, string $vorname, ?int $klassen_id): bool
    {
        try {
            return $this->persModel->updatePersonalData($benutzer_id, $name, $vorname, $klassen_id);
        } catch (\Exception $e) {
            return false;
        }
    }
}
