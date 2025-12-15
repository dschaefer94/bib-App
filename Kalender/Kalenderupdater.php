<?php
// Pfade zu den Kalenderdateien
$ordner = __DIR__ . '/kalenderdateien/';
$alt = $ordner . 'kalender_alt.ics';
$neu = $ordner . 'kalender_neu.ics';
$url = 'https://intranet.bib.de/ical/d819a07653892b46b6e4d2765246b7ab';

// Alte Datei löschen, falls vorhanden
if (file_exists($alt)) {
    unlink($alt);
}

// Neue Datei umbenennen zu alt
if (file_exists($neu)) {
    rename($neu, $alt);
}

// Neue Datei von der URL herunterladen
$inhalt = file_get_contents($url);
if ($inhalt !== false) {
    file_put_contents($neu, $inhalt);
    chmod($neu, 0664);
    echo "Kalenderdatei erfolgreich aktualisiert.";
} else {
    echo "Fehler beim Herunterladen der Kalenderdatei.";
}

// Kalender laden
$icsA = file_exists($alt) ? file_get_contents($alt) : '';
$icsB = $inhalt; // Kein erneutes Laden nötig

$eventsA = parseIcsEvents($icsA);
$eventsB = parseIcsEvents($icsB);

// Unterschiede berechnen und speichern (nur wenn neue gefunden)
$diffs = loadDiffs();
$changed = false;

foreach ($eventsB as $uid => $event) {
    if (!isset($eventsA[$uid]) && !isset($diffs[$uid])) {
        $diffs[$uid] = [
            'type' => 'neu',
            'summary' => $event['summary'],
            'start' => $event['start'],
            'location' => $event['location']
        ];
        $changed = true;
    }
}

foreach ($eventsB as $uid => $eventB) {
    if (isset($eventsA[$uid]) && $eventsA[$uid] !== $eventB && !isset($diffs[$uid])) {
        $eventA = $eventsA[$uid];
        $diffs[$uid] = [
            'type' => 'geändert',
            'alt_summary' => $eventA['summary'],
            'alt_start' => $eventA['start'],
            'alt_location' => $eventA['location'],
            'neu_summary' => $eventB['summary'],
            'neu_start' => $eventB['start'],
            'neu_location' => $eventB['location']
        ];
        $changed = true;
    }
}

foreach ($eventsA as $uid => $event) {
    if (!isset($eventsB[$uid]) && !isset($diffs[$uid])) {
        $diffs[$uid] = [
            'type' => 'gelöscht',
            'summary' => $event['summary'],
            'start' => $event['start'],
            'location' => $event['location']
        ];
        $changed = true;
    }
}

if ($changed) {
    saveDiffs($diffs);
}

file_put_contents(__DIR__ . '/eventsB.json', json_encode($eventsB));

// Funktionen
function loadDiffs($filename = __DIR__ . '/unterschiede.json') {
    if (file_exists($filename)) {
        $json = file_get_contents($filename);
        $data = json_decode($json, true);
        if (is_array($data)) {
            return $data;
        }
    }
    return [];
}

function saveDiffs($diffs, $filename = __DIR__ . '/unterschiede.json') {
    file_put_contents($filename, json_encode($diffs));
}

function parseIcsEvents($icsContent) {
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
?>