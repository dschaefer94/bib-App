<?php

namespace ppb\Model;

class CalendarModel extends Database
{
  public function __construct() {}

  public function selectCalendar($klassenname)
  {
    try {
      $pdo = $this->linkDB();
      $query = "SELECT * FROM `{$klassenname}`_stundenplan ORDER BY start ASC";
      $stmt = $pdo->query($query);
      return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
      return [];
    }
  }

  public function selectChanges($klassenname)
  {
    try {
      $pdo = $this->linkDB();
      $query = "SELECT * FROM `{$klassenname}`_aenderungen ORDER BY start ASC";
      $stmt = $pdo->query($query);
      return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
      return [];
    }
  }

  public function selectDetails($klassenname)
  {
    try {
      $pdo = $this->linkDB();
      $query = "SELECT * FROM `{$klassenname}`_veraenderte_termine ORDER BY start ASC";
      $stmt = $pdo->query($query);
      return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
      return [];
    }
  }

  public function selectNotedChanges($benutzer_id)
  {
    //muss noch gucken, ob ich alle Events mappe oder je User eine neue Tabelle anlege
    return [];
  }
}
