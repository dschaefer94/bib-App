<?php

namespace SDP\Controller;

use SDP\Model\CalendarModel;
use Throwable;

class CalendarController
{
    public function __construct() {}
    /**
     * Daniel
     * holt aktuellen Stundenplan
     * @return void
     */
    public function getCalendar()
    {
        try {
            echo json_encode((new CalendarModel())->selectCalendar($_SESSION['klassenname']), JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            $this->sendErrorResponse($e);
        }
    }
    /**
     * Daniel
     * holt gelabelte Termine
     * @return void
     */
    public function getChanges()
    {
        try {
            echo json_encode((new CalendarModel())->selectChanges($_SESSION['klassenname']), JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            $this->sendErrorResponse($e);
        }
    }
    /**
     * Daniel
     * holt Liste aller gelesenen gelabelten Termine für einen Benutzer
     * @return void
     */
    public function getNotedChanges()
    {
        try {
            echo json_encode((new CalendarModel())->selectNotedChanges($_SESSION['benutzer_id']), JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            $this->sendErrorResponse($e);
        }
    }
    /**
     * Daniel
     * fügt gelesenen Termin eines Nutzers in die Tabelle ein
     * @param mixed $data
     * @return void
     */
    public function writeNotedChanges($data)
    {
        try {
            echo json_encode((new CalendarModel())->insertNotedChanges($_SESSION['benutzer_id'], $data['termin_id']), JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            $this->sendErrorResponse($e);
        }
    }
    /**
     * Daniel
     * Hilfsfunktion für Errormessages
     * @param Throwable $e
     * @return void
     */
    private function sendErrorResponse(Throwable $e): void
    {
        http_response_code($e->getCode());
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'erfolg' => false,
            'grund'  => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}