<?php

namespace ppb\Model;

use ppb\Library\Msg;

class Florian_persoehnliche_datenModel extends Database {
    
    public function __construct()
    {
        // leer
    }

    /**
     * Liefert persönliche Daten anhand der Benutzer_id
     * @param int $benutzer_id
     * @return array|null
     */
    public function getPersonalDataByUserId(int $benutzer_id)
    {
        try {
            $pdo = $this->linkDB();
            $stmt = $pdo->prepare("SELECT benutzer_id, name, vorname, klassen_id FROM PERSOENLICHE_DATEN WHERE benutzer_id = ?");
            $stmt->execute([$benutzer_id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\PDOException $e) {
            new Msg(true, "Fehler beim Lesen der PERSOENLICHE_DATEN-Tabelle", $e);
            return null;
        }
    }

    /**
     * Aktualisiert persönliche Daten (name, vorname, klassen_id)
     * @param int $benutzer_id
     * @param string $name
     * @param string $vorname
     * @param int|null $klassen_id
     * @return bool
     */
    public function updatePersonalData(int $benutzer_id, string $name, string $vorname, ?int $klassen_id = null): bool
    {
        try {
            $pdo = $this->linkDB();
            $stmt = $pdo->prepare(
                "INSERT INTO PERSOENLICHE_DATEN (benutzer_id, name, vorname, klassen_id) VALUES (?, ?, ?, ?) " .
                "ON DUPLICATE KEY UPDATE name = VALUES(name), vorname = VALUES(vorname), klassen_id = VALUES(klassen_id)"
            );
            // Fügt neuen Datensatz ein oder aktualisiert vorhandenen anhand des Primärschlüssels benutzer_id
            $stmt->execute([$benutzer_id, $name, $vorname, $klassen_id ?: null]);
            return true;
        } catch (\PDOException $e) {
            new Msg(true, "Fehler beim Aktualisieren der persönlichen Daten", $e);
            return false;
        }
    }
}