<?php

namespace ppb\Model;

use ppb\Library\Msg;

class Florian_KlassenModel extends Database {
    
    public function __construct()
    {
        // leer
    }

    /**
     * Liefert alle Klassen
     * @return array
     */
    public function getAllClasses(): array
    {
        try {
            $pdo = $this->linkDB();
            $stmt = $pdo->query("SELECT klassen_id, klassenname FROM KLASSEN ORDER BY klassenname");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            new Msg(true, "Fehler beim Lesen der KLASSEN-Tabelle", $e);
            return [];
        }
    }

    /**
     * Liefert eine Klasse anhand der ID
     * @param int $klassen_id
     * @return array|null
     */
    public function getClassById(int $klassen_id)
    {
        try {
            $pdo = $this->linkDB();
            $stmt = $pdo->prepare("SELECT klassen_id, klassenname FROM KLASSEN WHERE klassen_id = ?");
            $stmt->execute([$klassen_id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\PDOException $e) {
            new Msg(true, "Fehler beim Lesen einer Klasse", $e);
            return null;
        }
    }

    /**
     * Aktualisiert eine Klasse (z.B. klassenname)
     * @param int $klassen_id
     * @param string $klassenname
     * @return bool
     */
    public function updateClass(int $klassen_id, string $klassenname): bool
    {
        try {
            $pdo = $this->linkDB();
            $stmt = $pdo->prepare("UPDATE KLASSEN SET klassenname = ? WHERE klassen_id = ?");
            $stmt->execute([$klassenname, $klassen_id]);
            return true;
        } catch (\PDOException $e) {
            new Msg(true, "Fehler beim Aktualisieren der Klasse", $e);
            return false;
        }
    }

    
}