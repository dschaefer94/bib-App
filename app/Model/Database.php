<?php

namespace SDP\Model;

use SDP\Library\Msg;

abstract class Database
{
    private $config;

    // Wir verlangen die Config jetzt direkt im Konstruktor
    public function __construct()
    {
        $path = __DIR__ . '/../../config/config.php';

        if (file_exists($path)) {
            // Wir löschen den internen Cache für diese Datei, um sicherzugehen
            $this->config = require $path;

            // Falls require nur '1' zurückgibt (weil es schonmal geladen wurde)
            if (!is_array($this->config)) {
                // Plan B: Dateiinhalt direkt parsen oder über globale Variable gehen
                // Aber meistens reicht es, require OHNE _once zu nutzen.
            }
        }
    }

    public function linkDB()
    {
        $db = $this->config['db'];
        $dsn = "mysql:host=" . $db['host'];
        if (!empty($db['port'])) {
            $dsn .= ";port=" . $db['port'];
        }
        $dsn .= ";dbname=" . $db['dbname'] . ";charset=" . $db['charset'];

        try {
            return new \PDO(
                $dsn,
                $db['user'],
                $db['password'],
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        } catch (\PDOException $e) {
            new Msg(true, "Verbindung fehlgeschlagen", $e->getMessage());
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
