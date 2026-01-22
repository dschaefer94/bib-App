<?php

namespace SDP\Updater;

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
        echo "Fehler beim Herunterladen der ICS-Datei für Klasse '$name'. Update abgebrochen.\n";
        return;
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
        $pdo = new \PDO(
            "mysql:dbname=pbd2h24asc_stundenplan_db;host=mysql.pb.bib.de",
            "pbd2h24asc",
            "8x2uXWAeTEMC",
            array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION)
        );
        $query = "SELECT klassenname FROM klassen ORDER BY 1 ASC";
        $stmt = $pdo->query($query);
        $klassennamen = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        echo "Fehler bei DB-Verbindung: " . $e->getMessage() . "\n";
        return;
    }
    foreach ($klassennamen as $klassenname) {
        echo "Starte Kalender-Update für Klasse: " . $klassenname['klassenname'] . "\n";
        kalenderupdater($klassenname['klassenname'], $pdo);
    }
}
