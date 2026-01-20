<?php

namespace ppb\Model;

use ppb\Library\Msg;

class ClassModel extends Database
{
  public function __construct() {}
  /**
   * Daniel
   * ruft alle Klassennamen auf
   * @return array mit Klassennamen
   */
  public function selectClass()
  {
    try {
      $pdo = $this->linkDB();
      $query = "SELECT klassenname FROM klassen ORDER BY 1 ASC";
      $stmt = $pdo->query($query);
      return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
      new Msg(true, 'Datenbankfehler in selectClass', $e);
      return [];
    }
  }

  /**
   * Daniel
   * lege Klasse an, mit Ordnerstruktur und ics-Platzhaltern, Details genau wie bei insertUser
   * @param mixed $data mit klassenname, ical_link, json_link
   * @return bool true: hat geklappt, false: Klasse bereits vorhanden
   */
  public function insertClass($data): array
  {
    try {
      $pdo = $this->linkDB();

      // Prüfe ob Klasse bereits existiert
      $checkQuery = "SELECT klassenname FROM klassen WHERE klassenname = :klassenname";
      $checkStmt = $pdo->prepare($checkQuery);
      $checkStmt->bindParam(':klassenname', $data['klassenname']);
      $checkStmt->execute();
      if ($checkStmt->rowCount() > 0) {
        return ['erfolg' => false, 'grund' => 'Klasse bereits vorhanden'];
      }

      $pdo->beginTransaction();
      $query = "INSERT INTO klassen (klassenname, ical_link)
      VALUES (:klassenname, :ical_link)";
      $stmt = $pdo->prepare($query);
      $stmt->bindParam(':klassenname', $data['klassenname']);
      $stmt->bindParam(':ical_link', $data['ical_link']);
      $stmt->execute();
      $pdo->commit();
      //ab hier wird alles autocommittet.
      //pro Klasse dynamisch je fünf Tabellen anlegen (AUSSERHALB Transaction!)
      $alter_stundenplan = "{$data['klassenname']}_alter_stundenplan";
      $neuer_stundenplan = "{$data['klassenname']}_neuer_stundenplan";
      $pending = "{$data['klassenname']}_pending";
      $aenderungen = "{$data['klassenname']}_aenderungen";
      $veraenderte_termine = "{$data['klassenname']}_veraenderte_termine";

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
      PRIMARY KEY (`termin_id`)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
      $stmt = $pdo->prepare($query);
      $stmt->execute();

      $query = "CREATE TABLE `{$veraenderte_termine}` (
      `termin_id` VARCHAR(255) NOT NULL,
      `summary` VARCHAR(255) NOT NULL,
      `start` DATETIME NOT NULL,
      `end` DATETIME NOT NULL,
      `location` VARCHAR(255) NOT NULL,
      PRIMARY KEY (`termin_id`)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
      $stmt = $pdo->prepare($query);
      $stmt->execute();
    } catch (\PDOException $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      return ['erfolg' => false, 'grund' => 'Fehler beim Datenbankzugriff:' . $e->getMessage()];
    }
    $ordnerName = $data['klassenname'];
    try {
      $ordnerPfad = dirname(__DIR__) . '/Kalender/Kalenderdateien/' . $ordnerName;
      mkdir($ordnerPfad, 0755, true);
    } catch (\Exception $e) {
      return ['erfolg' => false, 'grund' => 'Fehler beim Erstellen des Ordners: ' . $e->getMessage()];
    }
    try {
      $kalenderdatei = file_get_contents($data['ical_link']);
      if ($kalenderdatei === false) {
        return ['erfolg' => false, 'grund' => 'Fehler: Kalenderdatei URL ist nicht erreichbar'];
      }
    } catch (\Exception $e) {
      return ['erfolg' => false, 'grund' => 'Fehler beim Laden der Kalenderdatei: ' . $e->getMessage()];
    }
    try {
      $result = file_put_contents($ordnerPfad . '/stundenplan.ics', $kalenderdatei);
      if ($result === false) {
        return ['erfolg' => false, 'grund' => 'Fehler: Keine Schreibrechte im Ordner ' . $ordnerPfad];
      }
    } catch (\Exception $e) {
      return ['erfolg' => false, 'grund' => 'Fehler beim Speichern der Kalenderdatei: ' . $e->getMessage()];
    }
    return ['erfolg' => true];
  }

  public function updateClass($id, $data)
  {
    try {
      $pdo = $this->linkDB();
      $query = "UPDATE klassen SET klassenname = :klassenname, ical_link = :ical_link WHERE klassen_id = :klassen_id";
      $stmt = $pdo->prepare($query);
      $stmt->bindParam(':klassenname', $data['klassenname']);
      $stmt->bindParam(':ical_link', $data['ical_link']);
      $stmt->bindParam(':klassen_id', $id);
      $stmt->execute();
      return ['erfolg' => true];
    } catch (\PDOException $e) {
      return ['erfolg' => false, 'grund' => 'Fehler beim Datenbankzugriff'];

      //wenn der Klassenname geändert wurde, müssen auch die Tabellen umbenannt werden
      //und der Ordner auf dem Server
      //wenn der ical_link geändert wurde, muss die ics-Datei neu heruntergeladen werden
      //und Kalenderrunner.php nochmal ausgeführt werden (Ändeurungen löschen?)
    }
  }

  public function deleteClass($id)
  {
    try {
      $pdo = $this->linkDB();
      $pdo->beginTransaction();

      $query = "SELECT klassenname FROM klassen WHERE klassen_id = :klassen_id";
      $stmt = $pdo->prepare($query);
      $stmt->bindParam(':klassen_id', $id);
      $stmt->execute();
      $klassenname = $stmt->fetchColumn();

      $query = "DELETE FROM klassen WHERE klassen_id = :klassen_id";
      $stmt = $pdo->prepare($query);
      $stmt->bindParam(':klassen_id', $id);
      $stmt->execute();

      $query = "DROP TABLE IF EXISTS 
      `{$klassenname['klassenname']}_alter_stundenplan`,
      `{$klassenname['klassenname']}_neuer_stundenplan`,
      `{$klassenname['klassenname']}_pending`,
      `{$klassenname['klassenname']}_aenderungen`,
      `{$klassenname['klassenname']}_veraenderte_termine`";
      $stmt = $pdo->prepare($query);
      $stmt->execute();
      $pdo->commit();
    } catch (\PDOException $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      return ['erfolg' => false, 'grund' => 'Fehler beim Datenbankzugriff'];
    }
    return ['erfolg' => true];
  }
}
