<?php

namespace ppb\Controller;

use ppb\Model\CalendarModel;

class CalendarController
{
  public function __construct() {}

  public function getCalendar()
  {
    echo json_encode((new CalendarModel())->selectCalendar($_GET['klasse']), JSON_UNESCAPED_UNICODE);
  }

  public function getChanges()
  {
    echo json_encode((new CalendarModel())->selectChanges($_GET['klasse']), JSON_UNESCAPED_UNICODE);
  }

  public function getDetails()
  {
    echo json_encode((new CalendarModel())->selectDetails($_GET['klasse']), JSON_UNESCAPED_UNICODE);
  }

  public function getNotedChanges()
  {
    echo json_encode((new CalendarModel())->selectNotedChanges($_GET['benutzer_id']), JSON_UNESCAPED_UNICODE);
  }
}