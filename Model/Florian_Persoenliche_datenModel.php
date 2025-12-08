<?php

namespace ppb\Model;

use PDO;
use PDOException;

/**
 * Florian_Persoenliche_datenModel
 *
 * Verantwortlich für alle Datenbankoperationen, die die PERSOENLICHE_DATEN-Tabelle betreffen.
 */
class Florian_Persoenliche_datenModel {

    /**
     * Holt die persönlichen Daten eines Benutzers anhand seiner Benutzer-ID.
     *
     * @param int $benutzer_id Die ID des Benutzers.
     * @return array|null Ein assoziatives Array mit den persönlichen Daten oder null, wenn nicht gefunden.
     * @throws PDOException
     */
    public function getPersonalDataByUserId(int $benutzer_id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT benutzer_id, name, vorname, klassen_id FROM PERSOENLICHE_DATEN WHERE benutzer_id = ?");
        $stmt->execute([$benutzer_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Aktualisiert die persönlichen Daten eines bestehenden Benutzers.
     *
     * @param int $benutzer_id Die ID des Benutzers.
     * @param string $name Der neue Nachname.
     * @param string $vorname Der neue Vorname.
     * @param int|null $klassen_id Die neue zugehörige Klassen-ID oder null.
     * @return bool True, wenn die Aktualisierung erfolgreich war, sonst false.
     * @throws PDOException
     */
    public function updatePersonalData(int $benutzer_id, string $name, string $vorname, ?int $klassen_id = null): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "UPDATE PERSOENLICHE_DATEN SET name = ?, vorname = ?, klassen_id = ? WHERE benutzer_id = ?"
        );
        $stmt->execute([$name, $vorname, $klassen_id, $benutzer_id]);
        return $stmt->rowCount() > 0;
    }
}