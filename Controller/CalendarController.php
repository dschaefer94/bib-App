<?php

namespace ppb\Controller;

use ppb\Model\CalendarModel;

class CalendarController
{
  public function __construct() {}
  /**
   * Daniel
   * holt sich den normalen aktuellen Stundenplan ab
   * @return void
   */
  public function getCalendar()
  {
    echo json_encode((new CalendarModel())->selectCalendar($_GET['klasse']), JSON_UNESCAPED_UNICODE);
  }

  /**
   * Daniel
   * holt sich die ausgewerteten Änderungen ab
   * @return void
   */
  public function getChanges()
  {
    echo json_encode((new CalendarModel())->selectChanges($_GET['klasse']), JSON_UNESCAPED_UNICODE);
  }

  /**
   * Daniel
   * holt sich die vom Benutzer bereits abgehakten Termin_UIDs ab
   * @return void
   */
  public function getNotedChanges()
  {
    echo json_encode((new CalendarModel())->selectNotedChanges($_GET['benutzer_id']), JSON_UNESCAPED_UNICODE);
  }
}