<?php

namespace ppb\Controller;

use ppb\Model\CalendarModel;

class CalendarController
{
  public function __construct() {}
  /**
   * Daniel
   * holt sich den normalen aktuellen Stundenplan ab mit der Klasse des eingeloggten Benutzers
   * @return void
   */
  public function getCalendar()
  {
    echo json_encode((new CalendarModel())->selectCalendar($_SESSION['klassenname']), JSON_UNESCAPED_UNICODE);
  }

  /**
   * Daniel
   * holt sich die ausgewerteten Änderungen ab für die Klasse des eingeloggten Benutzers
   * @return void
   */
  public function getChanges()
  {
    echo json_encode((new CalendarModel())->selectChanges($_SESSION['klassenname']), JSON_UNESCAPED_UNICODE);
  }

  /**
   * Daniel
   * holt sich die vom Benutzer bereits abgehakten Termin_UIDs ab
   * @return void
   */
  public function getNotedChanges()
  {
    echo json_encode((new CalendarModel())->selectNotedChanges($_SESSION['benutzer_id']), JSON_UNESCAPED_UNICODE);
  }

  public function writeNotedChanges($data)
  {
    echo json_encode((new CalendarModel())->insertNotedChanges($_SESSION['benutzer_id'], $data['termin_id']), JSON_UNESCAPED_UNICODE);
  }
}
