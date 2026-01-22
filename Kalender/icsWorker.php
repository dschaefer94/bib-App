<?php

namespace SDP\Updater;

/**
 * Daniel
 * parset ICS-Events nach einer Vorvalidierung der jeweiligen Parameter in ein assoziatives Array zum Weiterarbeiten in der DB
 * @param mixed $icsString Inhalt der ICS-Datei als String
 * @return array{end: string, location: string, start: string, summary: string[]} Array mit den geparsten Events und deren Parametern
 */
function parseIcsEvents($icsString): array
{
    preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/s', $icsString, $matches);
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
 * ICS-Downloader, der die ics-Datei der jeweiligen Klasse von der in der DB hinterlegten URL herunterlädt
 * @param string $name jeweiliger Klassenname
 * @param mixed $pdo PDO-Datenbankverbindungsobjekt
 * @throws \RuntimeException bei ungültigem Klassennamen
 * @return string Inhalt der ics-Datei als String
 */
function icsDownloader(string $name, $pdo)
{
    // SQL-Injection verhindern
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
        throw new \RuntimeException("Ungültiger Klassenname");
    }
    echo "ical-Link aus DB extrahieren...\n";
    try {
        $query = "SELECT ical_link FROM klassen WHERE klassenname = :name";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':name' => $name]);
        $ical_link = $stmt->fetchColumn();
    } catch (\PDOException $e) {
        echo "Fehler bei der Datenbankverbindung: " . $e->getMessage() . "\n";
        return "";
    }
    echo "ICS-Datei herunterladen...\n";
    $download = file_get_contents($ical_link);
    //manipulierte Testdateien, um gelöschte Termine zu simulieren
    // $download = file_get_contents(__DIR__ . '/Testdateien/'.$name.'.ics');
    if ($download === false) {
        echo "Fehler: Kalender-URL '$ical_link' nicht erreichbar\n";
        return "";
    }
    return $download;
}
