<?php

namespace ppb\Model;

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
    $pdo = $this->linkDB();
    $query = "SELECT klassenname FROM klassen ORDER BY 1 ASC";
    $stmt = $pdo->query($query);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Daniel
   * lege Klasse an, mit Ordnerstruktur und ics-Platzhaltern, Details genau wie bei insertUser
   * @param mixed $data mit klassenname, ical_link, json_link
   * @return bool true: hat geklappt, false: Klasse bereits vorhanden
   */
  public function insertClass($data): bool
  {
    $pdo = $this->linkDB();
    $uuid = $this->createUUID();
    $query = "INSERT IGNORE INTO klassen (klassen_id, klassenname, ical_link, json_link)
    VALUES (:klassen_id, :klassenname, :ical_link, :json_link)";
    $pdo->beginTransaction();
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':klassen_id', $uuid);
    $stmt->bindParam(':klassenname', $data['klassenname']);
    $stmt->bindParam(':ical_link', $data['ical_link']);
    $stmt->bindParam(':json_link', $data['json_link']);
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
      $pdo->rollBack();
      return false;
    }
    $pdo->commit();
    $ordnerName = $data['klassenname'];
    $ordnerPfad = dirname(__DIR__) . '/Kalender/Kalenderdateien' . $ordnerName;
    mkdir($ordnerPfad, 0755, true);
    file_put_contents($ordnerPfad . '/stundenplan_alt.ics', '');
    file_put_contents($ordnerPfad . '/stundenplan_neu.ics', '');

    return true;
  }
}
