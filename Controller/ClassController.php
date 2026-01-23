<?php

namespace ppb\Controller;

use ppb\Model\ClassModel;

class ClassController
{
  public function __construct() {}
  /**
   * Daniel und Florian
   * gibt alle gespeicherten Klassennamen für das Dropdown-Menü der Registrierung/Benutzerverwaltung aus
   * @return void, JSON mit Klassennamen-Array
   */
  public function getClass()
  {
    $model = new ClassModel();
    $rows  = $model->selectClass();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
  }
  /**
   * Daniel und Florian
   * gibt den Klassennamen des eingeloggten Benutzers aus
   * @return void
   */
  public function getClassById()
  {
    $model = new ClassModel();
    $rows  = $model->selectClass($_SESSION['user_id']);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
  }
  /**
   * Daniel
   * fügt Klasse in entsprechende Tabellen in Datenbank ein
   * @param mixed $data
   * @return void
   */
  public function writeClass($data)
  {
    echo json_encode((new ClassModel())->insertClass($data), JSON_PRETTY_PRINT);
  }
  /**
   * Daniel
   * updatet Klasse
   * @param mixed $id
   * @param mixed $data
   * @return void
   */
  public function updateClass($id, $data)
  {
    //noch nicht implementiert
  }

  /**
   * Daniel
   * löscht Klasse
   * @param mixed $id
   * @return void
   */
  public function deleteClass($id)
  {
    //noch nicht implementiert
  }
}
