<?php

namespace SDP\Model;

class CalendarModel extends Database
{
  public function __construct() {}
  /**
   * Daniel
   * holt sich den den jeweiligen aktuellen Stundenplan einer Klasse
   * @param mixed $klassenname übergebener Klassenname
   * @return array Array mit Stundenplandaten
   */
  public function selectCalendar($klassenname)
  {
    try {
      $pdo = $this->linkDB();
      $query = "SELECT * FROM `{$klassenname}_neuer_stundenplan`";
      $stmt = $pdo->query($query);
      return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
      return [];
    }
  }
  /**
   * Daniel
   * holt sich die Liste aller Termine, die geändert wurden
   * @param mixed $klassenname übergebener Klassenname
   * @return array Array mit Termin_id, label und ggf. den ursprünlichen Termindaten
   */
  public function selectChanges($klassenname)
  {
    try {
      $pdo = $this->linkDB();
      $query = "SELECT * FROM `{$klassenname}_aenderungen`";
      $stmt = $pdo->query($query);
      return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
      return [];
    }
  }
  /**
   * Daniel
   * holt sich die Liste aller vom Benutzer notierten Änderungen, damit diese Im Frontend nicht angezeigt werden
   * @param mixed $benutzer_id übergebene benutzer_id der Session
   * @return array Array mit den vom Benutzer notierten Änderungen
   */
  public function selectNotedChanges($benutzer_id)
  {
    try {
      $pdo = $this->linkDB();
      $query = "SELECT termin_id FROM gelesene_termine WHERE benutzer_id = :benutzer_id";
      $stmt = $pdo->prepare($query);
      $stmt->bindParam(':benutzer_id', $benutzer_id);
      $stmt->execute();
      return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
      return [];
    }
  }

  /**
   * Daniel
   * fügt vom Benutzer als gelesen markierte Termin_ids in die gelesen-Tabelle ein
   * @param mixed $benutzer_id übergebene benutzer_id der Session
   * @param mixed $termin_id übergebene event_id des Termins
   * @return bool
   */
  public function insertNotedChanges($benutzer_id, $termin_id): array
  {
    try{
      $pdo = $this->linkDB();
      $query = "INSERT INTO gelesene_termine (benutzer_id, termin_id) VALUES (:benutzer_id, :termin_id)";
      $stmt = $pdo->prepare($query);
      $stmt->bindParam(':benutzer_id', $benutzer_id);
      $stmt->bindParam(':termin_id', $termin_id);
      $stmt->execute();
    } catch (\PDOException $e) {
      return ['aenderungen_notiert' => false, 'grund' => $e->getMessage()];
    }
    return ['aenderungen_notiert' => true];
  }
}
