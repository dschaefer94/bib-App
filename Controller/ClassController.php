<?php

namespace ppb\Controller;

use ppb\Model\ClassModel;

class ClassController
{
  public function __construct() {}
  /**
   * Daniel
   * gibt alle gespeicherten Klassennamen für das Dropdown-Menü der Registrierung aus
   * @return void, JSON mit Klassennamen-Array
   */
  public function getClass()
  {
    $model = new ClassModel();
    $rows  = $model->selectClass();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
  }
}
