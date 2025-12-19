<?php
// #!/usr/bin/env php
// Das Ding in Zeile 2 heißt shebang und muss auf Linux/macOS ganz oben stehen,
// wenn das Skript direkt ausführbar sein soll. Da es aber ausschließlich
// über Kalenderupdater.sh aufgerufen wird ("php Kalenderupdater.php"), ist das hier nicht nötig.
// PHP-CLI: Parameter vom Kalenderupdater.sh sauber über getopt() einlesen
// --user-dir  Pfad des gefundenen Unterordners für die Adressierung
// --name      Ordnername (Basename) zum Anrufen der DB

$options = getopt("", ["klassenname:", "user-dir:"]);

$name    = $options['klassenname'] ?? null;
$ordner = rtrim($options['user-dir'], "/\\") . DIRECTORY_SEPARATOR;
$alt = $ordner . 'kalender_alt.ics';
$neu = $ordner . 'kalender_neu.ics';

// rufe die individuelle ical_url der jeweiligen Klasse von der DB ab
$pdo = $this->linkDB();
$ical_link = $pdo->query("SELECT ical_link FROM klassen WHERE klassenname = '$name'")->fetchColumn();

// Alte Datei löschen, neue in alt umbenennen
unlink($alt);
rename($neu, $alt);

// Neue Datei von der URL herunterladen
$download = file_get_contents($ical_link);
if ($download !== false) {
    file_put_contents($neu, $download);
    chmod($neu, 0664);
}

// Kalender laden
$ics_alt = file_exists($alt) ? file_get_contents($alt) : '';
$ics_neu = $download; // Kein erneutes Laden nötig

$eventsAlt = parseIcsEvents($ics_alt);
$eventsNeu = parseIcsEvents($ics_neu);

//######################################################################################

// Unterschiede berechnen und speichern (nur wenn neue gefunden)
$diffs = loadDiffs();
$changed = false;

foreach ($eventsNeu as $uid => $event) {
    if (!isset($eventsAlt[$uid]) && !isset($diffs[$uid])) {
        $diffs[$uid] = [
            'type' => 'neu',
            'summary' => $event['summary'],
            'start' => $event['start'],
            'location' => $event['location']
        ];
        $changed = true;
    }
}

foreach ($eventsNeu as $uid => $eventB) {
    if (isset($eventsAlt[$uid]) && $eventsAlt[$uid] !== $eventB && !isset($diffs[$uid])) {
        $eventA = $eventsAlt[$uid];
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

foreach ($eventsAlt as $uid => $event) {
    if (!isset($eventsNeu[$uid]) && !isset($diffs[$uid])) {
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

file_put_contents(__DIR__ . '/eventsB.json', json_encode($eventsNeu));

// Funktionen
function loadDiffs($filename = __DIR__ . '/unterschiede.json')
{
    if (file_exists($filename)) {
        $json = file_get_contents($filename);
        $data = json_decode($json, true);
        if (is_array($data)) {
            return $data;
        }
    }
    return [];
}

function saveDiffs($diffs, $filename = __DIR__ . '/unterschiede.json')
{
    file_put_contents($filename, json_encode($diffs));
}

function parseIcsEvents($icsContent)
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
