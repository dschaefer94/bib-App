<?php

namespace ppb\Controller;

use ppb\Model\ClassModel;

class ClassController
{
  public function __construct() {}

  // GET /restAPI.php/klasse
  public function getClass()
  {
    $model = new ClassModel();
    $rows  = $model->selectClass();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
  }
}
