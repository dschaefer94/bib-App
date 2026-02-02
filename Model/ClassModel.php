<?php

namespace ppb\Model;

use ppb\Library\Msg;

require_once __DIR__ . '/../Kalender/Kalenderupdater.php';

class ClassModel extends Database
{
  public function __construct() {}
  /**
   * Daniel & Florian
   * ruft alle Klassennamen auf für das Dropdownmenü in der Stundenplanansicht/Klassenverwaltung
   * oder eine einzelne via Klassenname für die Profilverwaltung
   * @return array mit Klassennamen
   */
  public function selectClass($benutzer_id = null)
  {
    try {
      $pdo = $this->linkDB();

      $where = '';
      $params = [];

      if ($benutzer_id !== null) {
        $where = "
                WHERE klassen_id = (
                    SELECT klassen_id
                    FROM persoenliche_daten
                    WHERE benutzer_id = :benutzer_id
                )
            ";
        $params[':benutzer_id'] = $benutzer_id;
      }
      $query = "
            SELECT klassen_id, klassenname
            FROM klassen
            $where
            ORDER BY klassenname ASC
        ";
      $stmt = $pdo->prepare($query);
      $stmt->execute($params);

      return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
      new Msg(true, 'Datenbankfehler in selectClass', $e);
      return [];
    }
  }

  /**
   * Daniel
   * legt Klasse in der DB an:
   * 1) Duplikatsprüfung und Einfügen in Tabelle klassen
   * 2) dynamische Erstellung von drei Tabellen pro Klasse für den die von der ics-Datei geparseten Termine
   *   und die Auswertung der Änderungen
   * 3) initiales Befüllen der Tabellen über ../Kalender/kalenderupdater.php
   * 4) Leeren der Änderungstabelle (alle Termine sind ja neu)
   * @param mixed $data Array mit klassenname und ical_link
   * @return array{erfolg: bool, grund: string|array{erfolg: bool}} Erfolgsmeldung oder Fehlergrund
   */
  public function insertClass($data)
  {
    try {
      $pdo = $this->linkDB();

      $checkQuery = "SELECT klassenname FROM klassen WHERE klassenname = :klassenname";
      $checkStmt = $pdo->prepare($checkQuery);
      $checkStmt->bindParam(':klassenname', $data['klassenname']);
      $checkStmt->execute();
      if ($checkStmt->rowCount() > 0) {
        throw new \Exception('Klasse bereits vorhanden', 409);
      }

      $pdo->beginTransaction();
      $query = "INSERT INTO klassen (klassenname, ical_link)
      VALUES (:klassenname, :ical_link)";
      $stmt = $pdo->prepare($query);
      $stmt->bindParam(':klassenname', $data['klassenname']);
      $stmt->bindParam(':ical_link', $data['ical_link']);
      $stmt->execute();
      $pdo->commit();

      $alter_stundenplan = "{$data['klassenname']}_alter_stundenplan";
      $neuer_stundenplan = "{$data['klassenname']}_neuer_stundenplan";
      $aenderungen = "{$data['klassenname']}_aenderungen";

      $query = "CREATE TABLE `{$alter_stundenplan}` (
      `termin_id` VARCHAR(255) NOT NULL,
      `summary` VARCHAR(255) NOT NULL,
      `start` DATETIME NOT NULL,
      `end` DATETIME NOT NULL,
      `location` VARCHAR(255) NOT NULL,
      PRIMARY KEY (`termin_id`)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
      $stmt = $pdo->prepare($query);
      $stmt->execute();

      $query = "CREATE TABLE `{$neuer_stundenplan}` (
      `termin_id` VARCHAR(255) NOT NULL,
      `summary` VARCHAR(255) NOT NULL,
      `start` DATETIME NOT NULL,
      `end` DATETIME NOT NULL,
      `location` VARCHAR(255) NOT NULL,
      PRIMARY KEY (`termin_id`)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
      $stmt = $pdo->prepare($query);
      $stmt->execute();

      $query = "CREATE TABLE `{$aenderungen}` (
      `termin_id` VARCHAR(255) NOT NULL,
      `label` ENUM('gelöscht', 'neu', 'geändert') NOT NULL,
      `summary_alt` VARCHAR(255),
      `start_alt` DATETIME,
      `end_alt` DATETIME,
      `location_alt` VARCHAR(255),
      PRIMARY KEY (`termin_id`)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
      $stmt = $pdo->prepare($query);
      $stmt->execute();

      \SDP\Updater\kalenderupdater($data['klassenname'], $pdo);

      $query = "TRUNCATE TABLE `{$aenderungen}`";
      $stmt = $pdo->prepare($query);
      $stmt->execute();
      return ['erfolg' => true, 'klassenname' => $data['klassenname']];
    } catch (\Throwable $e) {
      if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
      }
      if ($e->getCode() >= 400) {
        throw $e;
      }
      throw new \Exception('Datenbankfehler beim Erstellen: ' . $e->getMessage(), 500);
    }
  }
  /**
   * Daniel
   * ändert bei Namensänderung des Klassennamens diesen in der Tabelle Klassen
   * ändert dazu alle korrespondierenden Tabellennamen der Klasse
   * bei einem neuen Ical-Link wird der neue Stundenplan heruntergeladen und alle Änderungen gelöscht
   * @param mixed $id Klassen-ID
   * @param mixed $data neuer Klassenname und/oder Ical-Link
   * @throws \Exception
   * @return array{erfolg: bool}
   */
  public function updateClass($id, $data)
  {
    try {
      $pdo = $this->linkDB();

      $stmt = $pdo->prepare("SELECT klassenname FROM klassen WHERE klassen_id = :id");
      $stmt->execute([':id' => $id]);
      $alterName = $stmt->fetchColumn();

      if (!$alterName) {
        throw new \Exception('Klasse nicht gefunden', 404);
      }

      $pdo->beginTransaction();

      if ($alterName !== $data['klassenname']) {
        $suffixes = ['alter_stundenplan', 'neuer_stundenplan', 'aenderungen'];
        foreach ($suffixes as $suffix) {
          $oldTable = "{$alterName}_{$suffix}";
          $newTable = "{$data['klassenname']}_{$suffix}";
          $pdo->exec("RENAME TABLE `{$oldTable}` TO `{$newTable}`");
        }
      }

      $query = "UPDATE klassen SET klassenname = :klassenname";
      $params = [':klassenname' => $data['klassenname'], ':id' => $id];

      if (!empty($data['ical_link'])) {
        $query .= ", ical_link = :ical_link";
        $params[':ical_link'] = $data['ical_link'];
      }
      $query .= " WHERE klassen_id = :id";
      $pdo->prepare($query)->execute($params);

      if (!empty($data['ical_link'])) {
        \SDP\Updater\kalenderupdater($data['klassenname'], $pdo);
        $truncateQuery = "TRUNCATE TABLE `{$data['klassenname']}_aenderungen`";
        $pdo->exec($truncateQuery);
      }

      $pdo->commit();
      return ['erfolg' => true];
    } catch (\Throwable $e) {
      if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
      }
      throw $e;
    }
  }
  /**
   * Daniel
   * löscht alle korrespondierenden Klassentabellen
   * alle Benutzer in dieser Klasse werden auf die Klassen-ID NULL verwiesen
   * löscht Klasse in der Tabelle Klassen
   * mögliche gelesene Termine der Klasse werden wegen möglicher Mehrfachverwendung des Termins (n-m-Beziehung) nicht angefasst
   * @param mixed $id Klassen-ID
   * @param mixed $data neuer Klassenname und/oder Ical-Link
   * @throws \Exception
   * @return array{erfolg: bool}
   */
  public function deleteClass($id): array
  {
    try {
      $pdo = $this->linkDB();

      $stmt = $pdo->prepare("SELECT klassenname FROM klassen WHERE klassen_id = ?");
      $stmt->execute([$id]);
      $klassenname = $stmt->fetchColumn();

      if (!$klassenname) {
        throw new \Exception("Klasse mit ID $id existiert nicht.", 404);
      }

      $pdo->beginTransaction();

      $pdo->prepare("UPDATE persoenliche_daten SET klassen_id = NULL WHERE klassen_id = ?")
        ->execute([$id]);

      $pdo->prepare("DELETE FROM klassen WHERE klassen_id = ?")
        ->execute([$id]);

      $pdo->commit();
      $pdo->exec(
        "DROP TABLE IF EXISTS 
            `{$klassenname}_alter_stundenplan`, 
            `{$klassenname}_neuer_stundenplan`, 
            `{$klassenname}_aenderungen`"
      );

      return ['erfolg' => true];
    } catch (\Throwable $e) {
      if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
      }
      throw $e;
    }
  }

  /**
   * Florian
   * @return array
   */
  public function getAllClasses(): array
  {
    $pdo = $this->linkDB();
    $stmt = $pdo->query("SELECT klassen_id, klassenname FROM klassen ORDER BY klassenname");
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
}
