<?php

namespace ppb\Model;

use ppb\Library\Msg;

class Florian_persoehnliche_datenModel extends Database {
    
    public function __construct()
    {
        
    }
    public function selectProject ()
    {
        try {
       //Verbindung zur DB
        $pdo = $this->linkDB();

        //abfrage SQL-Start -> Select * from projects
        $stmt = $pdo->query("SELECT id, name FROM project");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        //Rückgabe verarbeiten
    
        } catch (\PDOException $e) {
           new Msg(true,"Fehler beim lesen der Project Tabelle", $e);
        }
    }
}