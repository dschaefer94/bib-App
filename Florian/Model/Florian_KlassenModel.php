<?php

namespace ppb\Model;

use PDO;
use PDOException;

/**
 * Florian_KlassenModel
 *
 * Verantwortlich für alle Datenbankoperationen, die die KLASSEN-Tabelle betreffen.
 */
class Florian_KlassenModel {

    /**
     * Holt alle Klassen aus der Datenbank, sortiert nach dem Klassennamen.
     *
     * @return array Eine Liste von assoziativen Arrays, die die Klassen repräsentieren.
     * @throws PDOException
     */
    public function getAllClasses(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT klassen_id, klassenname FROM KLASSEN ORDER BY klassenname");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Holt eine einzelne Klasse anhand ihrer ID aus der Datenbank.
     *
     * @param int $klassen_id Die ID der zu suchenden Klasse.
     * @return array|null Ein assoziatives Array mit den Klassendaten oder null, wenn nicht gefunden.
     * @throws PDOException
     */
    public function getClassById(int $klassen_id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT klassen_id, klassenname FROM KLASSEN WHERE klassen_id = ?");
        $stmt->execute([$klassen_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Aktualisiert den Namen einer bestehenden Klasse in der Datenbank.
     *
     * @param int $klassen_id Die ID der zu aktualisierenden Klasse.
     * @param string $klassenname Der neue Name für die Klasse.
     * @return bool True, wenn die Aktualisierung erfolgreich war, sonst false.
     * @throws PDOException
     */
    public function updateClass(int $klassen_id, string $klassenname): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE KLASSEN SET klassenname = ? WHERE klassen_id = ?");
        $stmt->execute([$klassenname, $klassen_id]);
        return $stmt->rowCount() > 0;
    }
}