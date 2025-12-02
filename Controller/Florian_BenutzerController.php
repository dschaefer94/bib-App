<?php

namespace ppb\Controller;

use ppb\Model\Florian_BenutzerModel;

class Florian_BenutzerController {

    public function __construct()
    {
        
    }
        public function getTask()
        {
            $model = new Florian_BenutzerModel();
            echo json_encode($model->selectTask(), JSON_PRETTY_PRINT);
        }

        public function getFilteredTasks($filter)
        {
            $model = new Florian_BenutzerModel();
            echo json_encode($model->selectFilteredTasks($filter), JSON_PRETTY_PRINT);
        }
}