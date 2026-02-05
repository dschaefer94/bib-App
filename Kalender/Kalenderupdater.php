<?php

namespace SDP\Updater;

require_once __DIR__ . '/../Model/Database.php';
require_once __DIR__ . '/icsWorker.php';
require_once __DIR__ . '/Kalenderlogik.php';

/**
 * Daniel
 * Hauptworker zum Downloaden und Updaten der Kalenderdaten in der DB
 * 1) Download der ics-Datei über icsDownloader
 * 2) Aufräumen der Tabellen alter_stundenplan, neuer_stundenplan, wartezimmer über tabellenAufraeumen
 * 3) Einfügen der neuen Termine in die Tabelle neuer_stundenplan über dbMagic inkl. Auswertung der Änderungen
 * @param string $name jeweiliger Klassenname
 * @param mixed $pdo PDO-Datenbankverbindungsobjekt
 * @return void
 */
function kalenderupdater(string $name, $pdo)
{
    $download = icsDownloader($name, $pdo);
    if ($download === "") {
        throw new \Exception("Fehler beim Herunterladen der ICS-Datei für Klasse '$name'.");
    }
    $alter_stundenplan = "{$name}_alter_stundenplan";
    $neuer_stundenplan = "{$name}_neuer_stundenplan";
    $wartezimmer = "{$name}_wartezimmer";
    tabellenAufraeumen($pdo, $alter_stundenplan, $neuer_stundenplan, $wartezimmer);
    $events = parseIcsEvents($download);
    dbMagic($pdo, $alter_stundenplan, $neuer_stundenplan, $name, $events);
}
/**
 * Daniel
 * Runner, der für alle in der DB registrierten Klassen die Updateoperation durchführt
 * wird per Cronjob regelmäßig ausgeführt (10 min-Takt)
 * @return void
 */
function updateAlleKalendare()
{
    try {
        $config = require __DIR__ . '/../config/config.php';
        $db = $config['db'];
        $dsn = "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}";
        $pdo = new \PDO($dsn, $db['user'], $db['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]);
        $stmt = $pdo->query("SELECT klassenname FROM klassen ORDER BY 1 ASC");
        $klassennamen = $stmt->fetchAll();
    } catch (\PDOException $e) {
        throw new \Exception("Datenbank-Verbindungsfehler: " . $e->getMessage());
    }

    foreach ($klassennamen as $klassenname) {
        echo "Starte Update für: " . $klassenname['klassenname'] . "\n";
        try {
            kalenderupdater($klassenname['klassenname'], $pdo);
        } catch (\Exception $e) {
            echo "FEHLER bei {$klassenname['klassenname']}: " . $e->getMessage() . "\n";
        }
    }
}

