<?php

namespace SDP\Updater;

// Daniel
// Runner für die Kalender-Updates aller Klassen (./Kalenderdateien/*)
// ruft die DB für individuelle ical_url der jeweiligen Klasse ab
// speichert die aktuellen Kalenderdaten in der DB
// speichert die Änderungen in der DB

/**
 * Daniel
 * parset die ics-Datei und gibt die Events als Array zurück
 * @param mixed $icsContent Der Inhalt der ics-Datei als String
 * @return array{end: string, location: string, start: string, summary: string[]}
 */
function parseIcsEvents($icsContent): array
{
    preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/s', $icsContent, $matches);
    $events = [];
    foreach ($matches[1] as $event) {
        preg_match('/UID:(.*)/', $event, $uid);
        preg_match('/SUMMARY:(.*)/', $event, $summary);
        preg_match('/DTSTART(?:;TZID=[^:]+)?:([0-9T]+)/', $event, $start);
        preg_match('/DTEND(?:;TZID=[^:]+)?:([0-9T]+)/', $event, $end);
        preg_match('/LOCATION:(.*)/', $event, $location);
        $id = trim($uid[1] ?? uniqid());
        $events[$id] = [
            'summary' => trim($summary[1] ?? ''),
            'start' => trim($start[1] ?? ''),
            'end' => trim($end[1] ?? ''),
            'location' => trim($location[1] ?? '')
        ];
    }
    return $events;
}

/**
 * Daniel
 * lädt den aktuellen Stundenplan und vergleicht ihn mit dem alten und speichert die Änderungen
 * @param string $name Name der Klasse für DB-Abfrage
 * @param string $ordner Pfad zum Klassenordner, wo die Kalenderdateien gespeichert sind
 */

function kalenderupdater(string $name, string $ordner, $pdo)
{
    // SQL-Injection verhindern
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
        throw new \RuntimeException("Ungültiger Klassenname");
    }
    $ordner = rtrim($ordner, "/\\") . DIRECTORY_SEPARATOR;
    $ics = $ordner . 'stundenplan.ics';

    // rufe die individuelle ical_url der jeweiligen Klasse von der DB ab
    try {
        $query = "SELECT ical_link FROM klassen WHERE klassenname = :name";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':name' => $name]);
        $ical_link = $stmt->fetchColumn();

        if (!$ical_link) {
            echo "Fehler: Klasse '$name' nicht in DB gefunden\n";
            return;
        }
    } catch (\PDOException $e) {
        echo "Fehler bei der Datenbankverbindung: " . $e->getMessage() . "\n";
        return;
    }
    echo "ical-Link aus DB extrahieren...\n";

    // Neue Datei von der URL herunterladen und speichern
    $download = file_get_contents($ical_link);
    if ($download === false) {
        echo "Fehler: Kalender-URL '$ical_link' nicht erreichbar\n";
        return;
    }
    echo "ical-Datei herunterladen...\n";
    if (file_put_contents($ics, $download) === false) {
        echo "Fehler: Kann nicht in '$ics' schreiben\n";
        return;
    }
    chmod($ics, 0664);
    echo "ical-Datei speichern...\n";

    // Datenbankoperationen
    // Dynamische Tabellennamen basierend auf der Klasse via Einbettung($name)
    $alter_stundenplan = "{$name}_alter_stundenplan";
    $neuer_stundenplan = "{$name}_neuer_stundenplan";
    $wartezimmer = "{$name}_wartezimmer";

    // 1. vorletzte Termindaten löschen
    $query = "TRUNCATE TABLE `{$alter_stundenplan}`;";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $query = "RENAME TABLE `{$alter_stundenplan}` TO `{$wartezimmer}`;";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    // 2. letzte Termindaten in die Tabelle alter_stundenplan "verschieben"
    $query = "RENAME TABLE `{$neuer_stundenplan}` TO `{$alter_stundenplan}`;";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $query = "RENAME TABLE `{$wartezimmer}` TO `{$neuer_stundenplan}`;";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    // 3. neue Termindaten in die Tabelle neuer_stundenplan einfügen
    // wegen der vielen Termine empfiehlt es sich, die Inserts zu "chunkieren"

    //##############################TESTREGION################################
    //1. normal gedownloadete Datei parsen
    $events = parseIcsEvents($download);
    //2. Testfall: lokale manipulierte Datei mit Änderungen parsen, die so tun soll als wäre sie gedownloaded worden
    // $events = parseIcsEvents(file_get_contents(__DIR__ . "/Kalenderdateien/{$name}/stundenplan_test.ics"));
    //##############################TESTREGION################################

    $chunkSize = 100;
    $chunks = array_chunk($events, $chunkSize, true);
    $pdo->beginTransaction();
    try {
        foreach ($chunks as $chunk) {
            $valueParts = [];
            $params = [];
            $counter = 1;

            foreach ($chunk as $uid => $event) {
                $valueParts[] = "(:termin_id$counter, :summary$counter, :start$counter, :end$counter, :location$counter)";
                $params[":termin_id$counter"] = $uid;
                $params[":summary$counter"] = $event['summary'];
                $params[":start$counter"] = $event['start'];
                $params[":end$counter"] = $event['end'];
                $params[":location$counter"] = $event['location'];
                $counter++;
            }
            $query = "INSERT INTO `{$neuer_stundenplan}` (termin_id, summary, start, end, location)
            VALUES " . implode(", ", $valueParts);
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
        }

        // 4. Vergleich der beiden Tabellen und Ermittlung der Änderungen
        $aenderungen = "{$name}_aenderungen";
        $veraenderte_termine = "{$name}_veraenderte_termine";

        // 4.1 in alt und nicht in neu -> gelöscht
        $query = "INSERT INTO `{$aenderungen}` (termin_id, label)
        SELECT DISTINCT alt.termin_id, 'gelöscht'
        FROM `{$alter_stundenplan}` AS alt
        WHERE NOT EXISTS (
            SELECT termin_id
            FROM `{$neuer_stundenplan}` AS neu
            WHERE neu.termin_id = alt.termin_id
            )
        ON DUPLICATE KEY UPDATE label = VALUES(label);
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        // 4.2 in neu und nicht in alt -> neu
        $query = "INSERT INTO `{$aenderungen}` (termin_id, label)
        SELECT neu.termin_id, 'neu'
        FROM `{$neuer_stundenplan}` AS neu
        LEFT JOIN `{$alter_stundenplan}` AS alt
        ON alt.termin_id = neu.termin_id
        WHERE alt.termin_id IS NULL
        ON DUPLICATE KEY UPDATE label = VALUES(label);
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        // 4.3 in beiden, aber mit Unterschieden -> geändert
        $query = "INSERT INTO `{$aenderungen}` (termin_id, label)
        SELECT neu.termin_id, 'geändert'
        FROM `{$neuer_stundenplan}` AS neu
        JOIN `{$alter_stundenplan}` AS alt ON neu.termin_id = alt.termin_id
            WHERE NOT (
            neu.summary   <=> alt.summary AND
            neu.start     <=> alt.start   AND
            neu.end       <=> alt.end     AND
            neu.location  <=> alt.location
            )
        ON DUPLICATE KEY UPDATE label = VALUES(label);
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        // 4.4. geänderte Termine in die Tabelle veraenderte_termine kopieren
        $query = "INSERT INTO `{$veraenderte_termine}` (termin_id, summary, start, end, location)
        SELECT alt.termin_id, alt.summary, alt.start, alt.end, alt.location
        FROM `{$alter_stundenplan}` AS alt
        WHERE alt.termin_id IN (
            SELECT termin_id
            FROM `{$aenderungen}`
                WHERE label = 'geändert'
            )
        ON DUPLICATE KEY UPDATE
        summary = VALUES(summary),
        start   = VALUES(start),
        end     = VALUES(end),
        location= VALUES(location);
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $pdo->commit();
    } catch (\PDOException $e) {
        $pdo->rollBack();
        echo "Fehler bei der Datenbankoperation: " . $e->getMessage() . "\n";
    }
    echo "Datenbankoperationen erfolgreich!\n";
}

// Runner: lädt alle Klassen-Kalender aus ./Kalenderdateien/* und aktualisiert sie
function runAllCalendarUpdates()
{
    try {
        $pdo = new \PDO(
            "mysql:dbname=stundenplan_db;host=localhost",
            "root",
            "root",
            []
        );
    } catch (\PDOException $e) {
        echo "Fehler bei DB-Verbindung: " . $e->getMessage() . "\n";
        return;
    }

    foreach (glob(__DIR__ . '/Kalenderdateien/*') as $dir) {
        if (!is_dir($dir)) continue;
        echo "Update " . basename($dir) . "...\n";
        kalenderupdater(basename($dir), $dir, $pdo);
    }
}
