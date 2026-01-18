<?php

namespace ppb\Model;

class UserModel extends Database
{

    public function __construct() {}
    /**
     * Daniel
     * einfache Benutzerabfrage
     * @return array mit allen benutzer:ids und Emails
     */
    public function selectUser()
    {

        $pdo = $this->linkDB();
        $stmt = $pdo->query("SELECT benutzer_id, email FROM benutzer");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        //Rückgabe verarbeiten
    }
    /**
     * Daniel
     * beim Registrieren checkt die DB auf Email-Duplikate und bricht entweder den Registrierungsvorgang ab
     * bzw. legt den Benutzer in den Tabellen "Benutzer" und "persoenliche_daten" an
     * Außerdem wird ein Ordner für den Benutzer angelegt, in dem gelesene und eigene Events gespeichert werden
     * @param array $data mit name, vorname, klassenname, email,
     * @return array true: hat geklappt, false: Benutzer bereits vorhanden
     */
    public function insertUser(array $data): array
    {
        try {
            $pdo  = $this->linkDB();
            $uuid = $this->createUUID();
            //Cooler Befehl, um ähnlich wie bei GitHub Befehle erstmal zu "stagen" und erst beim Commit zu speichern.
            //Dadurch muss keine Check-Methode im Controller aufgerufen werden, womit die Datenbank zusätzlich vorher
            //angerufen wird, um zu checken, ob es einen Benutzer bereits gibt.
            $pdo->beginTransaction();
            //"INSERT IGNORE" fügt keine Duplikate bei einem Unique Index ein (Email) - ohne eine Exception zu werfen!
            $query = "INSERT IGNORE INTO benutzer (benutzer_id, passwort, email)
              VALUES (:benutzer_id, :passwort, :email)";
            $stmtUser = $pdo->prepare($query);
            $stmtUser->bindParam(':benutzer_id', $uuid);
            $stmtUser->bindParam(':passwort', $data['passwort']);
            $stmtUser->bindParam(':email', $data['email']);
            $stmtUser->execute();

            //wenn ein Duplikat via "INSERT IGNORE INTO" ignoriert wird, ist rowCount = 0
            if ($stmtUser->rowCount() === 0) {
                //Rollback macht, dass alles während der Transaktion geschehene rückgängig gemacht wird
                $pdo->rollBack();
                //für JSON im Controller false als UUID zurückgeben, JS wertet dies dann so, dass es den Nutzer schon gibt
                return ['benutzerAngelegt' => false, 'grund' => 'Benutzer existiert bereits (Rollback).'];
            }

            $query = "INSERT INTO persoenliche_daten (benutzer_id, name, vorname, klassen_id)
              VALUES (
                  :benutzer_id,
                  :name,
                  :vorname,
                  (SELECT klassen_id FROM klassen WHERE klassenname = :klassenname)
              )";
            $stmtPD = $pdo->prepare($query);
            $stmtPD->bindParam(':benutzer_id', $uuid);
            $stmtPD->bindParam(':name', $data['name']);
            $stmtPD->bindParam(':vorname', $data['vorname']);
            $stmtPD->bindParam(':klassenname', $data['klassenname']);
            $stmtPD->execute();

            $pdo->commit();
        } catch (\PDOException $e) {
            return ['benutzerAngelegt' => false, 'grund' => 'Datenbankfehler: ' . $e->getMessage()];
        }
        //Benutzerordner für gelesene und eigene Events anlegen (heißt wie UUID)
        //Pfad muss beim Porten noch angepasst werden (noch eine Ebene höher (dirname(__DIR__, 2)))
        //error_log provisorische Fehlerbehandlung (kein echo!!)
        $ordnerName = $uuid;
        $ordnerPfad = dirname(__DIR__) . '/Benutzer/' . $ordnerName;

        if (!file_exists($ordnerPfad)) {
            if (mkdir($ordnerPfad, 0700, true)) {
                file_put_contents($ordnerPfad . '/geleseneEvents.json', json_encode([]));
                file_put_contents($ordnerPfad . '/eigeneEvents.json', json_encode([]));
                error_log("Ordner mit leerer JSON erfolgreich erstellt!");
            } else {
                error_log("Fehler beim Erstellen des Ordners.");
            }
        }
        return ['benutzerAngelegt' => true];
    }
}
