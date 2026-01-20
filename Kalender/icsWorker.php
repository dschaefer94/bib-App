<?php

namespace SDP\Updater;
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

function icsDownloader(string $name, $pdo)
{
    // SQL-Injection verhindern
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
        throw new \RuntimeException("Ungültiger Klassenname");
    }

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
    }
    return $download;
}