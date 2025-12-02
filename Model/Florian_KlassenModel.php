<?php

namespace ppb\Model;

use ppb\Library\Msg;

class Florian_KlassenModel extends Database {
    
    public function __construct()
    {
        
    }
    public function selectTask ()
    {
        try {
       //Verbindung zur DB
        $pdo = $this->linkDB();

        //abfrage SQL-Start -> Select * from task
        $stmt = $pdo->query("SELECT `task`.`id`, `task`.`title`, `task`.`expense`, `task`.`dueDate`, `task`.`done`, `priority`.`description`
                            FROM `task` 
	                        Inner JOIN `priority` ON `task`.`priorityId` = `priority`.`id`;");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        //Rückgabe verarbeiten
    
        } catch (\PDOException $e) {
           new Msg(true,"Fehler beim lesen der Task Tabelle", $e);
        }
    }

    
}