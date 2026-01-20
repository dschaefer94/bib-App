<?php

namespace ppb\Model;

use ppb\Library\Msg;
require_once __DIR__ . '/../Kalender/Kalenderupdater.php';

class ClassModel extends Database
{
  public function __construct() {}
  /**
   * Daniel & Florian
   * ruft alle Klassennamen auf für das Dropdownmenü in der Stundenplanansicht und bei der Profilverwaltung
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
   * legt Klasse in der DB an:
   * 1) Duplikatsprüfung und Einfügen in Tabelle klassen
   * 2) dynamische Erstellung von drei Tabellen pro Klasse für den die von der ics-Datei geparseten Termine
   *   und die Auswertung der Änderungen
   * 3) initiales Befüllen der Tabellen über ../Kalender/kalenderupdater.php
   * 4) Leeren der Änderungstabelle (alle Termine sind ja neu)
   * @param mixed $data Array mit klassenname und ical_link
   * @return array{erfolg: bool, grund: string|array{erfolg: bool}} Erfolgsmeldung oder Fehlergrund
   */
  public function insertClass($data): array
  {
    try {
      $pdo = $this->linkDB();

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

    } catch (\PDOException $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      return ['erfolg' => false, 'grund' => 'Fehler beim Datenbankzugriff:' . $e->getMessage()];
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
      `{$klassenname['klassenname']}_wartezimmer`,
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
