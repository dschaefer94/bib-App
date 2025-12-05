<?php

namespace ppb\Model;

use ppb\Library\Msg;

/**
 * Abstract Database class - Basis für alle Model-Klassen
 * Stellt PDO-Verbindung zur Verfügung
 */
abstract class Database {
    
    // Datenbankverbindungsdaten
    private $host = "localhost";
    private $dbname = "stundenplan_db";
    private $username = "root";
    private $password = "root";
    
    /**
     * Stellt eine Verbindung zur Datenbank her
     * @return \PDO
     */
    public function linkDB() {
        try {
            $pdo = new \PDO("mysql:host=$this->host;dbname=$this->dbname;charset=utf8", 
                $this->username, 
                $this->password,
                array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION));
            return $pdo;
        } catch (\PDOException $e) {
            new Msg(true, "Datenbankfehler", $e);
            return null;
        }
    }

    /**
     * Zum serverseitigen Generieren einer UUID
     * @return string
     */
    public function createUUID() {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); 
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
