<?php
// Daniel

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
function kalenderupdater(string $name, string $ordner): void
{
    $ordner = rtrim($ordner, "/\\") . DIRECTORY_SEPARATOR;

    $alt = $ordner . 'stundenplan_alt.ics';
    $neu = $ordner . 'stundenplan_neu.ics';

    // rufe die individuelle ical_url der jeweiligen Klasse von der DB ab.
    // Die DB-Verbindung hier direkt aufbauen, da wir außerhalb des MVC arbeiten.
    // erstmal hardcoded, später in eine Config auslagern
    $pdo = new \PDO("mysql:dbname=stundenplan_db;host=localhost", "root", "root", []);
    $stmt = $pdo->prepare("SELECT ical_link FROM klassen WHERE klassenname = :name");
    $stmt->execute([':name' => $name]);
    $ical_link = $stmt->fetchColumn();

    // "kalender_alt.ics" löschen, "kalender_neu.ics" in "kalender_alt.ics" umbenennen
    unlink($alt);
    rename($neu, $alt);

    // Neue Datei von der URL herunterladen und als "kalender_neu.ics" speichern
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



    $result = ['neu' => [], 'geloescht' => [], 'geaendert' => [], 'current' => $eventsNeu];

    $allKeys = array_unique(array_merge(array_keys($eventsAlt), array_keys($eventsNeu)));
    foreach ($allKeys as $uid) {
        $inOld = isset($eventsAlt[$uid]);
        $inNew = isset($eventsNeu[$uid]);
        if ($inNew && !$inOld) {
            $result['neu'][$uid] = $eventsNeu[$uid];
        } elseif ($inOld && !$inNew) {
            $result['geloescht'][$uid] = $eventsAlt[$uid];
        } else { // in beiden
            $old = $eventsAlt[$uid];
            $new = $eventsNeu[$uid];
            $changed = [];
            foreach (['summary', 'start', 'end', 'location'] as $f) {
                if (($old[$f] ?? '') !== ($new[$f] ?? '')) $changed[] = $f;
            }
            if (!empty($changed)) {
                $result['geaendert'][$uid] = ['old' => $old, 'new' => $new, 'changed_fields' => $changed];
            }
        }
    }

    file_put_contents(
        $ordner . 'stundenplan.json',
        json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}



foreach (glob(__DIR__ . '/Kalenderdateien/*') as $dir) {
    if (!is_dir($dir)) continue;
    kalenderupdater(basename($dir), $dir);
}
