<?php

namespace ppb\Controller;

use ppb\Model\Florian_BenutzerModel;

class Florian_BenutzerController {

    public function __construct()
    {
        
    }
        public function getbenutzer()
        {
            $model = new Florian_BenutzerModel();
            echo json_encode($model->selectBenutzer(), JSON_PRETTY_PRINT);
        }

        public function getFilteredBenutzer($filter)
        {
            $model = new Florian_BenutzerModel();
            echo json_encode($model->selectFilteredBenutzer($filter), JSON_PRETTY_PRINT);
        }
}