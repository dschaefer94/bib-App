<?php

namespace ppb\Model;

class PersonalDataModel extends Database
{
    public function __construct()
    {
    }

    /**
     * Florian
     * Holt die vollständigen Benutzerdaten für die Profilseite.
     * @param string $benutzer_id
     * @return array|null
     */
    public function getUserData(string $benutzer_id): ?array
    {
        try {
            $pdo = $this->linkDB();
            $sql = "
                SELECT b.benutzer_id, pd.name, pd.vorname, b.email, k.klassen_id, k.klassenname
                FROM benutzer b
                LEFT JOIN persoenliche_daten pd ON b.benutzer_id = pd.benutzer_id
                LEFT JOIN klassen k ON pd.klassen_id = k.klassen_id
                WHERE b.benutzer_id = :benutzer_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':benutzer_id' => $benutzer_id
            ]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            error_log('getUserData query result: ' . ($user ? 'found' : 'not found'));
            if ($user) {
                error_log('User data: ' . json_encode($user));
            }
            return $user ?: null;
        } catch (\PDOException $e) {
            error_log('PDOException in getUserData: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Florian
     * Aktualisiert die persönlichen Daten eines Benutzers (Name, Vorname, Klasse).
     * Falls der Datensatz noch nicht existiert, wird er erstellt.
     * @param string $benutzer_id
     * @param string $name
     * @param string $vorname
     * @param int|null $klassen_id
     * @return bool
     */
    public function updatePersonalData(string $benutzer_id, string $name, string $vorname, ?int $klassen_id = null): bool
    {
        $pdo = $this->linkDB();
        
        // Zuerst prüfen, ob der Datensatz existiert
        $check = $pdo->prepare("SELECT benutzer_id FROM persoenliche_daten WHERE benutzer_id = ?");
        $check->execute([$benutzer_id]);
        
        if ($check->rowCount() > 0) {
            // UPDATE wenn Datensatz existiert
            $stmt = $pdo->prepare(
                "UPDATE persoenliche_daten SET name = ?, vorname = ?, klassen_id = ? WHERE benutzer_id = ?"
            );
            $stmt->execute([$name, $vorname, $klassen_id, $benutzer_id]);
        } else {
            // INSERT wenn Datensatz nicht existiert
            $stmt = $pdo->prepare(
                "INSERT INTO persoenliche_daten (benutzer_id, name, vorname, klassen_id) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$benutzer_id, $name, $vorname, $klassen_id]);
        }
        
        return $stmt->rowCount() > 0 || $check->rowCount() > 0;
    }

    /**
     * Florian
     * Aktualisiert die Account-Daten eines Benutzers (E-Mail, Passwort).
     * @param string $benutzer_id
     * @param string $email
     * @param string|null $passwort
     * @return bool
     */
    public function updateUser(string $benutzer_id, string $email, ?string $passwort = null): bool
    {
        $pdo = $this->linkDB();

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
     * Ruft alle Klassen ab.
     * @return array
     */
    public function getAllClasses(): array
    {
        $pdo = $this->linkDB();
        $stmt = $pdo->query("SELECT klassen_id, klassenname FROM klassen ORDER BY klassenname");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
