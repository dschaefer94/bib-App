<?php

namespace ppb\Controller;

use ppb\Model\Florian_persoehnliche_datenModel;

class Florian_persoehnliche_datenController {
    
    public function __construct()
    {
        
    }
        public function getpersöoehnliche_daten()
        {
            $model = new Florian_persoehnliche_datenModel();
            echo json_encode($model->selectProject(), JSON_PRETTY_PRINT);
        }
}