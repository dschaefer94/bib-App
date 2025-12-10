<?php

namespace ppb\Model;



class UserModel extends Database
{

    public function __construct() {}

    public function selectUser()
    {

        $pdo = $this->linkDB();
        $stmt = $pdo->query("SELECT benutzer_id, email FROM Benutzer");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        //Rückgabe verarbeiten
    }

    public function insertUser(array $data)
    {
        $pdo  = $this->linkDB();
        $uuid = $this->createUUID();
        //Cooler Befehl, um ähnlich wie bei GitHub Befehle erstmal zu "stagen" und erst beim Commit zu speichern.
        //Dadurch muss keine Check-Methode im Controller aufgerufen werden, womit die Datenbank zusätzlich vorher
        //angerufen wird, um zu checken, ob es einen Benutzer bereits gibt.
        $pdo->beginTransaction();
        //"INSERT IGNORE" fügt keine Duplikate bei einem Unique Index ein (Email) - ohne eine Exception zu werfen!
        $query = "INSERT IGNORE INTO Benutzer (benutzer_id, passwort, email)
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
            //für JSON im Controller null als UUID zurückgeben, JS wertet dies dann so, dass es den Nutzer schon gibt
            return null;
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

        return $uuid;
    }
}
