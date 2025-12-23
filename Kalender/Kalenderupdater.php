<?php
// Daniel
// Runner für die Kalender-Updates aller Klassen (./Kalenderdateien/*)
// ruft die DB für individuelle ical_url der jeweiligen Klasse ab
// speichert die aktuellen Kalenderdaten als JSON in ./Kalenderdateien/*/stundenplan.json,
// speichert die Änderungen als JSON in ./Kalenderdateien/*/aenderungen.json,
// die dann vom Frontend direkt eingesammelt wird
// 

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
 * Führt alte und neue Änderungskategorien zusammen.
 * - Für 'neu' und 'geloescht': neue Einträge überschreiben gleichnamige alte UIDs.
 * - Für 'geaendert': neue Einträge überschreiben alte UIDs; optional werden Felder vereinheitlicht.
 */

function mergeChanges(array $old, array $new): array
{
    $merged = ['neu' => [], 'geloescht' => [], 'geaendert' => []];

    foreach (['neu', 'geloescht', 'geaendert'] as $key) {
        foreach ($old[$key] ?? [] as $uid => $payload) {
            $event = $key === 'geaendert' ? ($payload['new'] ?? $payload['old'] ?? null) : $payload;
            if ($event && strtotime($event['start']) >= time()) {
                $merged[$key][$uid] = $payload;
            }
        }
    }

    foreach (['neu', 'geloescht', 'geaendert'] as $key) {
        foreach ($new[$key] ?? [] as $uid => $payload) {
            $merged[$key][$uid] = $payload;
        }
    }

    return $merged;
}


/**
 * Liest aenderungen.json aus dem gegebenen Pfad und gibt sie als Array zurück.
 * @param string $path Pfad zu aenderungen.json.
 */
function loadExistingChanges(string $path): array
{
    $json = json_decode(file_get_contents($path), true);
    return $json;
}


/**
 * Daniel
 * lädt den aktuellen Stundenplan und vergleicht ihn mit dem alten und speichert die Änderungen
 * @param string $name Name der Klasse für DB-Abfrage
 * @param string $ordner Pfad zum Klassenordner, wo die Kalenderdateien gespeichert sind
 */
function kalenderupdater(string $name, string $ordner)
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

    //Kategorien der Änderungen

    $result = ['neu' => [], 'geloescht' => [], 'geaendert' => []];

    // Vergleich
    $allKeys = array_unique(array_merge(array_keys($eventsAlt), array_keys($eventsNeu)));
    foreach ($allKeys as $uid) {
        $inOld = isset($eventsAlt[$uid]);
        $inNew = isset($eventsNeu[$uid]);

        // Prüfen: Ist der relevante Termin in der Vergangenheit?
        // Wir nehmen END, wenn vorhanden, sonst START.
        $checkEvent = $inNew ? $eventsNeu[$uid] : ($eventsAlt[$uid] ?? null);
        $dateStr = $checkEvent['end'] ?? $checkEvent['start'] ?? null;

        if ($dateStr && strtotime($dateStr) < time()) {
            continue; // Vergangenheit -> ignorieren
        }

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


    // --- alten Änderungen-Stand laden & sichern ---
    $aenderungenPfad = $ordner . 'aenderungen.json';
    $oldChanges = loadExistingChanges($aenderungenPfad);

    // --- neue Änderungen (aus aktuellem Vergleich) mit alten zusammenführen ---
    $mergedChanges = mergeChanges($oldChanges, $result);

    // aktuellen Stundenplan als JSON speichern (wie bisher)
    file_put_contents(
        $ordner . 'stundenplan.json',
        json_encode($eventsNeu, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
    @chmod($ordner . 'stundenplan.json', 0664);

    // zusammengeführte Änderungen als JSON speichern (ersetzt die alte Datei)
    file_put_contents(
        $aenderungenPfad,
        json_encode($mergedChanges, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
    @chmod($aenderungenPfad, 0664);
}


//Runner für die Kalender-Updates aller Klassen (./Kalenderdateien/*)
foreach (glob(__DIR__ . '/Kalenderdateien/*') as $dir) {
    if (!is_dir($dir)) continue;
    kalenderupdater(basename($dir), $dir);
}
