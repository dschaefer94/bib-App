<?php

namespace ppb\Controller;

use ppb\Model\TaskModel;

class TaskController {

    public function __construct()
    {
        
    }
        public function getTask()
        {
            $model = new TaskModel();
            echo json_encode($model->selectTask(), JSON_PRETTY_PRINT);
        }

        public function getFilteredTasks($filter)
        {
            $model = new TaskModel();
            echo json_encode($model->selectFilteredTasks($filter), JSON_PRETTY_PRINT);
        }
}