<?php

namespace SDP\Model;

class UserModel extends Database
{

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Florian
     * Wählt alle Benutzer mit ihren persönlichen Daten und dem Klassennamen aus.
     * @return array
     */
    public function selectBenutzer($benutzer_id): array
    {
        try {
            $pdo = $this->linkDB();
            $sql = "
        SELECT b.benutzer_id, b.ist_admin, pd.name, pd.vorname, b.email, k.klassenname
        FROM benutzer b
        LEFT JOIN persoenliche_daten pd ON b.benutzer_id = pd.benutzer_id
        LEFT JOIN klassen k ON pd.klassen_id = k.klassen_id
        WHERE b.benutzer_id = :benutzer_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':benutzer_id' => $benutzer_id
            ]);
        } catch (\PDOException $e) {
            return [];
        }
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    /**
     * Ramiz
     * @param string $email
     */
    public function getUserByEmail(string $email): ?array
    {
        try {
            $pdo = $this->linkDB();
            $stmt = $pdo->prepare("
                SELECT b.*, pd.vorname, pd.name as nachname
                FROM benutzer b
                LEFT JOIN persoenliche_daten pd ON b.benutzer_id = pd.benutzer_id
                WHERE b.email = :email
            ");
            $stmt->execute(['email' => $email]);
        } catch (\PDOException $e) {
            return null;
        }
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Daniel
     * Benutzer anlegen:
     * 1) Duplikatsprüfung (unique email)
     * 2) Benutzer mit gehashetem Passwort in die Tabellen benutzer und persoenliche_daten einfügen
     * @param array $data Array mit benutzerbezogenen Daten aus dem Frontend-Formular
     * @return array{benutzerAngelegt: bool, grund: string|array{benutzerAngelegt: bool}} Erfolgsmeldung oder Fehlergrund
     */
    public function insertUser(array $data): array
    {
        try {
            $pdo  = $this->linkDB();
            $uuid = $this->createUUID();
            $passwort = password_hash($data['passwort'], PASSWORD_BCRYPT);
            $pdo->beginTransaction();

            $query = "INSERT IGNORE INTO benutzer (benutzer_id, passwort, email)
            VALUES (:benutzer_id, :passwort, :email)";
            $stmtUser = $pdo->prepare($query);
            $stmtUser->bindParam(':benutzer_id', $uuid);
            $stmtUser->bindParam(':passwort', $passwort);
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
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['benutzerAngelegt' => false, 'grund' => 'Datenbankfehler: ' . $e->getMessage()];
        }
        return ['benutzerAngelegt' => true];
    }

    /**
     * Florian
     * Aktualisiert die E-Mail und optional das Passwort eines Benutzers in der BENUTZER-Tabelle.
     * @param int $benutzer_id Die ID des zu aktualisierenden Benutzers.
     * @param string $email Die neue E-Mail-Adresse.
     * @param string|null $passwort Das neue Passwort. Wenn null, wird es nicht geändert.
     * @return bool True bei Erfolg.
     */
    public function updateUser($benutzer_id, string $email, ?string $passwort = null): bool
    {
        $pdo  = $this->linkDB();

        if ($passwort) {
            $stmt = $pdo->prepare("UPDATE benutzer SET email = ?, passwort = ? WHERE benutzer_id = ?");
            $stmt->execute([$email, $passwort, $benutzer_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE benutzer SET email = ? WHERE benutzer_id = ?");
            $stmt->execute([$email, $benutzer_id]);
        }

        return $stmt->rowCount() > 0;
    }
    /**
     * Florian
     * Löscht einen Benutzer aus der Datenbank.
     * Dies geschieht in einer Transaktion, um Datenkonsistenz zu gewährleisten.
     * @param $id
     * @return bool
     */
    public function deleteUser($id): bool
    {
        $pdo  = $this->linkDB();
        try {
            $pdo->beginTransaction();
            $stmt3 = $pdo->prepare("DELETE FROM gelesene_termine WHERE benutzer_id = ?");
            $stmt3->execute([$id]);
            // Zuerst abhängige Daten löschen
            $stmt1 = $pdo->prepare("DELETE FROM persoenliche_daten WHERE benutzer_id = ?");
            $stmt1->execute([$id]);

            // Dann den Hauptdatensatz löschen
            $stmt2 = $pdo->prepare("DELETE FROM benutzer WHERE benutzer_id = ?");
            $stmt2->execute([$id]);

            $pdo->commit();

            // Gibt true zurück, wenn der Benutzer-Datensatz gelöscht wurde.
            return $stmt2->rowCount() > 0;
        } catch (\PDOException $e) {
            $pdo->rollBack();
            throw $e; // Ausnahme weiterleiten, damit der Controller sie behandeln kann.
        }
    }

    // Florian
    public function getUserData(int $benutzer_id): ?array
    {
        try {
            $pdo = $this->linkDB();
            $sql = "
                SELECT b.benutzer_id, pd.name, pd.vorname, b.email, k.klassen_id, k.klassenname
                FROM BENUTZER b
                LEFT JOIN PERSOEHNLICHE_DATEN pd ON b.benutzer_id = pd.benutzer_id
                LEFT JOIN KLASSEN k ON pd.klassen_id = k.klassen_id
                WHERE b.benutzer_id = :benutzer_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':benutzer_id' => $benutzer_id
            ]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    // Florian
    public function updatePersonalData($benutzer_id, $name, $vorname, $klassen_id = null): bool
    {
        $pdo = $this->linkDB();
        $stmt = $pdo->prepare(
            "UPDATE persoenliche_daten SET name = ?, vorname = ?, klassen_id = ? WHERE benutzer_id = ?"
        );
        $stmt->execute([$name, $vorname, $klassen_id, $benutzer_id]);
        return $stmt->rowCount() > 0;
    }
}
