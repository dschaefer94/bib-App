<?php

namespace ppb\Controller;

use ppb\Model\UserModel;

class UserController {
    
    public function __construct() {} 

    public function getUser()
    {
       $model = new UserModel();
       $model->selectUser();
       echo json_encode($model->selectUser(), JSON_PRETTY_PRINT);
    }
}