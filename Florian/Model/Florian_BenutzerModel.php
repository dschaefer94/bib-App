<?php

namespace ppb\Model;

use PDO;
use PDOException;

/**
 * Florian_BenutzerModel
 *
 * Verantwortlich für alle Datenbankoperationen, die die BENUTZER-Tabelle betreffen.
 */
class Florian_BenutzerModel {

    /**
     * Liefert einen Benutzer-Datensatz aus der Tabelle BENUTZER anhand der Benutzer-ID.
     *
     * @param int $benutzer_id Die ID des zu suchenden Benutzers.
     * @return array|null Ein assoziatives Array mit den Benutzerdaten oder null, wenn nicht gefunden.
     * @throws PDOException
     */
    public function getUserById(int $benutzer_id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT benutzer_id, email, passwort FROM BENUTZER WHERE benutzer_id = ?");
        $stmt->execute([$benutzer_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Wählt alle Benutzer mit ihren persönlichen Daten und dem Klassennamen aus.
     *
     * @return array
     * @throws PDOException
     */
    public function selectBenutzer(): array
    {
        $sql = "
            SELECT b.benutzer_id, pd.name, pd.vorname, b.email, k.klassenname
            FROM BENUTZER b
            LEFT JOIN PERSOEHNLICHE_DATEN pd ON b.benutzer_id = pd.benutzer_id
            LEFT JOIN KLASSEN k ON pd.klassen_id = k.klassen_id
            ORDER BY pd.name, pd.vorname
        ";
        $pdo = Database::getConnection();
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Aktualisiert die E-Mail und optional das Passwort eines Benutzers in der BENUTZER-Tabelle.
     *
     * @param int $benutzer_id Die ID des zu aktualisierenden Benutzers.
     * @param string $email Die neue E-Mail-Adresse.
     * @param string|null $passwort Das neue Passwort. Wenn null, wird es nicht geändert.
     * @return bool True bei Erfolg.
     * @throws PDOException
     */
    public function updateUser(int $benutzer_id, string $email, ?string $passwort = null): bool
    {
        $pdo = Database::getConnection();

        if ($passwort) {
            $stmt = $pdo->prepare("UPDATE BENUTZER SET email = ?, passwort = ? WHERE benutzer_id = ?");
            $stmt->execute([$email, $passwort, $benutzer_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE BENUTZER SET email = ? WHERE benutzer_id = ?");
            $stmt->execute([$email, $benutzer_id]);
        }

        return $stmt->rowCount() > 0;
    }

    /**
     * Löscht einen Benutzer aus der Datenbank.
     * Dies geschieht in einer Transaktion, um Datenkonsistenz zu gewährleisten.
     *
     * @param int $id
     * @return bool
     * @throws PDOException
     */
    public function deleteUser(int $id): bool
    {
        $pdo = Database::getConnection();
        try {
            $pdo->beginTransaction();

            // Zuerst abhängige Daten löschen
            $stmt1 = $pdo->prepare("DELETE FROM PERSOEHNLICHE_DATEN WHERE benutzer_id = ?");
            $stmt1->execute([$id]);

            // Dann den Hauptdatensatz löschen
            $stmt2 = $pdo->prepare("DELETE FROM BENUTZER WHERE benutzer_id = ?");
            $stmt2->execute([$id]);

            $pdo->commit();

            // Gibt true zurück, wenn der Benutzer-Datensatz gelöscht wurde.
            return $stmt2->rowCount() > 0;
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e; // Ausnahme weiterleiten, damit der Controller sie behandeln kann.
        }
    }

    /**
     * LEGACY-METHODE: Selektiert alle Einträge aus einer `project`-Tabelle.
     * @return array Eine Liste von Projekten.
     * @throws PDOException
     */
    public function selectProject(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, name FROM project");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}