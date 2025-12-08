<?php

namespace ppb\Model;

use PDO;
use PDOException;

/**
 * Abstract Database class - Basis für alle Model-Klassen
 * Stellt die PDO-Verbindung zur Datenbank über ein Singleton-Muster zur Verfügung.
 */
abstract class Database {

    // ToDo: Diese Werte sollten aus einer Konfigurationsdatei oder Umgebungsvariablen geladen werden.
    private const DB_HOST = "localhost";
    private const DB_NAME = "stundenplan_db";
    private const DB_USER = "root";
    private const DB_PASS = "root";

    /** @var PDO|null Die einzige PDO-Instanz (Singleton). */
    private static ?PDO $instance = null;

    /**
     * Stellt eine Verbindung zur MySQL-Datenbank her und gibt die PDO-Instanz zurück.
     * Verwendet das Singleton-Muster, um sicherzustellen, dass nur eine Verbindung besteht.
     *
     * @return PDO Das PDO-Verbindungsobjekt.
     * @throws PDOException Wenn die Verbindung fehlschlägt.
     */
    final public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . self::DB_HOST . ";dbname=" . self::DB_NAME . ";charset=utf8";
            
            try {
                self::$instance = new PDO($dsn, self::DB_USER, self::DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // Anstatt das Skript zu beenden, wird die Ausnahme weitergegeben.
                // Ein globaler Error Handler sollte dies abfangen und eine benutzerfreundliche Fehlermeldung anzeigen.
                throw $e;
            }
        }
        return self::$instance;
    }

    /**
     * Generiert eine serverseitige UUID (Version 4).
     * Nützlich für die Erstellung eindeutiger IDs ohne Datenbank-Unterstützung.
     *
     * @return string Eine eindeutige UUID im Standardformat.
     */
    public static function createUUID(): string {
        try {
            // Generiert 16 Bytes an kryptographisch sicheren Zufallsdaten
            $data = random_bytes(16);
            // Setzt die Bits für eine UUID Version 4
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variante
            // Formatiert die Bytes in den Standard-UUID-String
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        } catch (\Exception $e) {
            // Fallback, falls random_bytes nicht verfügbar ist (sehr unwahrscheinlich)
            return uniqid('uuid-', true);
        }
    }
    
    /**
     * Der Konstruktor ist privat, um eine direkte Instanziierung zu verhindern.
     */
    final private function __construct() {}

    /**
     * Klonen ist nicht erlaubt.
     */
    final private function __clone() {}

    /**
     * Aufwachen aus dem serialisierten Zustand ist nicht erlaubt.
     */
    final public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}
