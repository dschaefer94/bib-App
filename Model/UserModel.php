<?php

namespace ppb\Model;



class UserModel extends Database
{

    public function __construct() {}

    public function selectUser()
    {


        // return [
        //             'benutzer_id' => 1,
        //             'passwort' => 'Irish Coffee',
        //             'email' => 'kek@lel.de',
        //         ];

        //Verbindung zur Datenbank herstellen
        $pdo = $this->linkDB();
        //Anfrage SQL-Statement -> SELECT
        $stmt = $pdo->query("SELECT benutzer_id, passwort, email FROM Benutzer");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        //Rückgabe verarbeiten
    }
}
