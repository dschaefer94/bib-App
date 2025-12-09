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
    $pdo->beginTransaction();

    $query = "INSERT IGNORE INTO Benutzer (benutzer_id, passwort, email)
              VALUES (:benutzer_id, :passwort, :email)";
    $stmtUser = $pdo->prepare($query);
    $stmtUser->bindParam(':benutzer_id', $uuid);
    $stmtUser->bindParam(':passwort', $data['passwort']);
    $stmtUser->bindParam(':email', $data['email']);
    $stmtUser->execute();

    if ($stmtUser->rowCount() === 0) {
        $pdo->rollBack();
        return null; // Benutzer existiert schon
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

    return $uuid; // UUID zurückgeben, kein echo
}

}
