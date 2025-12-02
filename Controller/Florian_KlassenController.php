<?php

namespace ppb\Controller;

use ppb\Model\Florian_KlassenModel;

class Florian_KlassenController {
    
    public function __construct()
    {
        
    }
        public function getProject()
        {
            $model = new Florian_KlassenModel();
            echo json_encode($model->selectProject(), JSON_PRETTY_PRINT);
        }
}