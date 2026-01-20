<?php

namespace ppb\Model;

use ppb\Library\Msg;

abstract class Database
{


    // Zugangsdaten für die lokale Datenbank 

    private $dbName = "stundenplan_db"; //Datenbankname
    private $linkName = "localhost"; //Datenbank-Server
    private $user = "root"; //Benutzername
    private $pw = "root"; //Passwort

    // Test-Online-Datenbank
// $host = "localhost:3306";
// $dbname = "pbd2h24asc_bibapp";
// $username = "pbd2h24asc_backendboi";
// $password = "T3llMeWhy!";
    
// //MySQL-Datenbank Zugangsdaten
//     private $dbName = "pbd2h24asc_taskit"; //Datenbankname
//     private $linkName = "mysql.pb.bib.de"; //Datenbank-Server
//     private $user = "pbd2h24asc"; //Benutzername
//     private $pw = "8x2uXWAeTEMC"; //Passwort


    /**
     * Stellt eine Verbindung zur Datenbank her
     * 
     * @return \PDO Gibt eine Datenbankverbindung zurueck
     */
    public function linkDB()
    {
        try {
            $pdo = new \PDO(
                "mysql:dbname=$this->dbName;host=$this->linkName",
                $this->user,
                $this->pw,
                array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION)
            );
            return $pdo;
        } catch (\PDOException $e) {
            new Msg(true, null, $e);
        }
    }

    /**
     * Zum serverseitigen generieren einer UUID
     * 
     * @return string Liefert eine UUID
     */
    public function createUUID()
    {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
