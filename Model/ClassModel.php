<?php

namespace ppb\Model;

class ClassModel extends Database
{
  public function __construct() {}

  public function selectClass()
  {
    $pdo = $this->linkDB();
    $stmt = $pdo->query("SELECT klassen_id, klassenname, ical_link, json_link FROM klassen ORDER BY klassenname ASC");
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
}
