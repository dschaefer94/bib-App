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
     * @return array true: hat geklappt, false: Benutzer bereits vorhanden
     */
    /**
     * Daniel, erweitert von KI
     * Benutzerabfrage anhand der E-Mail für den Login
     * @param string $email
     * @return array|false
     */
    public function selectUserByEmail(string $email)
    {
        $pdo = $this->linkDB();
        $stmt = $pdo->prepare("SELECT benutzer_id, email, passwort FROM benutzer WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function insertUser(array $data):bool
    {
        $pdo  = $this->linkDB();
        $uuid = $this->createUUID();
        
        // Hash the password for secure storage
        $hashedPassword = password_hash($data['passwort'], PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();

            // First, try to insert the new user.
            $queryUser = "INSERT INTO benutzer (benutzer_id, passwort, email)
                          VALUES (:benutzer_id, :passwort, :email)";
            $stmtUser = $pdo->prepare($queryUser);
            $stmtUser->bindParam(':benutzer_id', $uuid);
            $stmtUser->bindParam(':passwort', $hashedPassword);
            $stmtUser->bindParam(':email', $data['email']);
            $stmtUser->execute();

            // If the user was inserted successfully, insert their personal data.
            $queryPD = "INSERT INTO persoenliche_daten (benutzer_id, name, vorname, klassen_id)
                          VALUES (
                              :benutzer_id,
                              :name,
                              :vorname,
                              (SELECT klassen_id FROM klassen WHERE klassenname = :klassenname)
                          )";
            $stmtPD = $pdo->prepare($queryPD);
            $stmtPD->bindParam(':benutzer_id', $uuid);
            $stmtPD->bindParam(':name', $data['name']);
            $stmtPD->bindParam(':vorname', $data['vorname']);
            $stmtPD->bindParam(':klassenname', $data['klassenname']);
            $stmtPD->execute();

            $pdo->commit();

            return true;
        } catch (\PDOException $e) {
            // An error occurred, roll back the transaction.
            $pdo->rollBack();
            
            // Check if the error is a duplicate entry violation (MySQL error code 23000)
            if ($e->getCode() == 23000) {
                // This means the user (based on the unique email) already exists.
                return false;
            } else {
                // For any other database error, re-throw the exception to be handled elsewhere.
                throw $e;
            }
        }
    }
}
