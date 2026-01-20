<?php

namespace ppb\Model;

class UserModel extends Database
{

    public function __construct() {}
    /**
     * Daniel
     * einfache Benutzerabfrage
     * @return array mit allen benutzer_ids und Emails
     */
    public function selectUser()
    {
        try {
            $pdo = $this->linkDB();
            $stmt = $pdo->query("SELECT benutzer_id, email FROM benutzer");
        } catch (\PDOException $e) {
            return [];
        }
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    /**
     * Daniel
     * Benutzer anlegen:
     * 1) Duplikatsprüfung (unique email)
     * 2) Benutzer in die Tabellen benutzer und persoenliche_daten einfügen
     * @param array $data Array mit benutzerbezogenen Daten aus dem Frontend-Formular
     * @return array{benutzerAngelegt: bool, grund: string|array{benutzerAngelegt: bool}} Erfolgsmeldung oder Fehlergrund
     */
    public function insertUser(array $data): array
    {
        try {
            $pdo  = $this->linkDB();
            $uuid = $this->createUUID();
            $pdo->beginTransaction();

            $query = "INSERT IGNORE INTO benutzer (benutzer_id, passwort, email)
            VALUES (:benutzer_id, :passwort, :email)";
            $stmtUser = $pdo->prepare($query);
            $stmtUser->bindParam(':benutzer_id', $uuid);
            $stmtUser->bindParam(':passwort', $data['passwort']);
            $stmtUser->bindParam(':email', $data['email']);
            $stmtUser->execute();

            if ($stmtUser->rowCount() === 0) {
                $pdo->rollBack();
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
        return ['benutzerAngelegt' => true];
    }
}
