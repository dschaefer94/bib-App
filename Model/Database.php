<?php

namespace SDP\Model;

use SDP\Library\Msg;

abstract class Database
{
    private $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config/config.php';
    }

    /**
     * Stellt eine Verbindung zur Datenbank her
     * 
     * @return \PDO Gibt eine Datenbankverbindung zurueck
     */
    public function linkDB()
    {
        $db = $this->config['db'];

        try {
            $pdo = new \PDO(
                "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}",
                $db['user'],
                $db['password'],
                array(
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                )
            );
            return $pdo;
        } catch (\PDOException $e) {
            new Msg(true, null, $e);
            return;
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
