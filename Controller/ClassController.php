<?php

namespace ppb\Controller;

use ppb\Model\ClassModel;
use Throwable;

class ClassController
{
  public function __construct() {}
  /**
   * Daniel & Florian
   * ruft eine/mehrere Klassen ab
   * @return void
   */
  public function getClass()
  {
    try {
      $model = new ClassModel();
      $rows  = $model->selectClass();
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
      $this->sendErrorResponse($e);
    }
  }
  /**
   * Florian
   * @throws \Exception
   * @return void
   */
  public function getClassById()
  {
    try {
      if (!isset($_SESSION['user_id'])) {
        throw new \Exception("Nicht autorisiert", 401);
      }
      $model = new ClassModel();
      $rows  = $model->selectClass($_SESSION['user_id']);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
      $this->sendErrorResponse($e);
    }
  }
  /**
   * Daniel
   * fügt eine neue Klasse ein, Details siehe Model
   * @param mixed $data
   * @throws \Exception
   * @return void
   */
  public function writeClass($data)
  {
    try {
      header('Content-Type: application/json; charset=utf-8');
      if (empty($data['klassenname'])) {
        throw new \Exception("Klassenname ist erforderlich", 400);
      }

      $result = (new ClassModel())->insertClass($data);
      http_response_code(201);
      echo json_encode($result, JSON_PRETTY_PRINT);
    } catch (Throwable $e) {
      $this->sendErrorResponse($e);
    }
  }
  /**
   * Daniel
   * zum Bearbeiten einer Klasse
   * @param mixed $id Klassen-ID
   * @param mixed $data neuer Klassenname und/oder neuer ical-Link
   * @throws \Exception
   * @return void
   */
  public function updateClass($id, $data): void
  {
    try {
      header('Content-Type: application/json; charset=utf-8');
      if (!$id) {
        throw new \Exception('ID fehlt', 400);
      }
      $data = [
        'klassenname' => $data['klassenname'] ?? '',
        'ical_link'   => $data['ical_link'] ?? ''
      ];
      $result = (new ClassModel())->updateClass($id, $data);
      http_response_code(200);
      echo json_encode($result);
    } catch (Throwable $e) {
      $this->sendErrorResponse($e);
    }
  }
  /**
   * Daniel
   * löscht eine Klasse und alle Beziehungen
   * @param mixed $id Klassen-ID
   * @throws \Exception
   * @return void
   */
  public function deleteClass($id): void
  {
    try {
      header('Content-Type: application/json; charset=utf-8');
      if (!$id) {
        throw new \Exception('ID fehlt', 400);
      }
      $result = (new ClassModel())->deleteClass($id);
      http_response_code(200);
      echo json_encode($result);
    } catch (Throwable $e) {
      $this->sendErrorResponse($e);
    }
  }
  /**
   * Daniel
   * Hilfsfunktion, um Exceptions einheitlich zu sammeln und mit korrektem Statuscode ans Frontend zu senden
   * @param Throwable $e PDO, IllegalArgument, etc.
   * @return void
   */
  private function sendErrorResponse(Throwable $e): void
  {
    http_response_code($e->getMessage());
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
      'erfolg' => false,
      'grund'  => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
  }
}
