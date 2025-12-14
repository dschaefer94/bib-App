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

  public function getClassById(int $id): ?array
{
    $pdo = $this->linkDB();
    $stmt = $pdo->prepare("SELECT klassen_id, klassenname, ical_link FROM klassen WHERE klassen_id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    return $row ?: null;
}
}
