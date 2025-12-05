<?php

namespace ppb\Controller;

use ppb\Model\Florian_KlassenModel;

class Florian_KlassenController {
    
    public function __construct()
    {
        
    }
        public function getklassen()
        {
            $model = new Florian_KlassenModel();
            echo json_encode($model->selectTask(), JSON_PRETTY_PRINT);
        }
}