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


    public function insertUser($data)
    {
        $pdo  = $this->linkDB();
        $uuid = $this->createUUID();
        $pdo->beginTransaction();
        $stmtUser = $pdo->prepare("
        INSERT INTO Benutzer (benutzer_id, passwort, email)
        VALUES (:benutzer_id, :passwort, :email)
    ");
        $stmtUser->bindParam(':benutzer_id', $uuid);
        $stmtUser->bindParam(':passwort',    $data['passwort']); // du hast 'passwort' im Frontend
        $stmtUser->bindParam(':email',       $data['email']);
        $stmtUser->execute();

        $stmtPD = $pdo->prepare("
        INSERT INTO persoenliche_daten (benutzer_id, name, vorname, klassen_id)
        VALUES (
            :benutzer_id,
            :name,
            :vorname,
            (SELECT klassen_id FROM klassen WHERE klassenname = :klassenname LIMIT 1)
        )
    ");
        $stmtPD->bindParam(':benutzer_id', $uuid);
        $stmtPD->bindParam(':name',        $data['name']);
        $stmtPD->bindParam(':vorname',     $data['vorname']);
        $stmtPD->bindParam(':klassenname', $data['klassenname']);
        $stmtPD->execute();

        $pdo->commit();
        return $uuid;
    }
}
