<?php

namespace ppb\Model;

use ppb\Library\Msg;

class Florian_BenutzerModel extends Database {

    public function __construct()
    {
        // leerer Konstruktor – Verbindung wird in Methoden aufgebaut
    }

    /**
     * Liefert einen Benutzer-Datensatz aus der Tabelle BENUTZER anhand der Benutzer-ID
     *
     * @param int $benutzer_id
     * @return array|null assoziatives Array mit den Feldern oder null wenn nicht gefunden
     */
    public function getUserById(int $benutzer_id)
    {
        try {
            $pdo = $this->linkDB();
            $stmt = $pdo->prepare("SELECT benutzer_id, email, passwort FROM BENUTZER WHERE benutzer_id = ?");
            $stmt->execute([$benutzer_id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\PDOException $e) {
            new Msg(true, "Fehler beim Lesen der BENUTZER-Tabelle", $e);
            return null;
        }
    }

    /**
     * Aktualisiert Email und optional Passwort in der BENUTZER-Tabelle
     *
     * @param int $benutzer_id
     * @param string $email
     * @param string|null $passwort wenn null oder leer -> Passwort wird nicht geändert
     * @return bool true bei Erfolg, false bei Fehler
     */
    public function updateUser(int $benutzer_id, string $email, ?string $passwort = null): bool
    {
        try {
            $pdo = $this->linkDB();

            if ($passwort) {
                $stmt = $pdo->prepare("UPDATE BENUTZER SET email = ?, passwort = ? WHERE benutzer_id = ?");
                $stmt->execute([$email, $passwort, $benutzer_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE BENUTZER SET email = ? WHERE benutzer_id = ?");
                $stmt->execute([$email, $benutzer_id]);
            }

            return true;
        } catch (\PDOException $e) {
            new Msg(true, "Fehler beim Aktualisieren der BENUTZER-Tabelle", $e);
            return false;
        }
    }

    /**
     * (Optional) Beispielmethode aus dem alten Code – bleibt erhalten falls benötigt
     */
    public function selectProject ()
    {
        try {
            $pdo = $this->linkDB();
            $stmt = $pdo->query("SELECT id, name FROM project");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            new Msg(true, "Fehler beim lesen der Project Tabelle", $e);
        }
    }
}