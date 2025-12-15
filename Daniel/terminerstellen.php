<?php
/**
 * Event erstellen und einfügen in die ICS-Datei für Testzwecke
 */
// scripts/add_test_event.php
$icsPath = __DIR__ . '/../data/pbd2h24a.ics'; // Relativer Pfad von scripts/
if (!is_readable($icsPath) || !is_writable($icsPath)) {
    echo "Datei nicht lesbar/schreibbar: $icsPath\n"; exit(1);
}

// Backup erstellen
copy($icsPath, $icsPath . '.bak.' . date('Ymd_His'));

// Event für heute (Europe/Berlin)
$today = new DateTime('today', new DateTimeZone('Europe/Berlin'));
$dtstart = $today->format('Ymd') . 'T100000'; // 10:00
$dtend   = $today->format('Ymd') . 'T113000'; // 11:30
$uid     = 'test-' . bin2hex(random_bytes(6)) . '@local';
$dtstamp = gmdate('Ymd\THis') . 'Z';

$event = "BEGIN:VEVENT\n";
$event .= "UID:$uid\n";
$event .= "SUMMARY:Testtermin für Heute\n";
$event .= "DESCRIPTION:Automatisch hinzugefügt für Testzwecke\n";
$event .= "LOCATION:Online\n";
$event .= "DTSTART;TZID=Europe/Berlin:$dtstart\n";
$event .= "DTEND;TZID=Europe/Berlin:$dtend\n";
$event .= "DTSTAMP:$dtstamp\n";
$event .= "END:VEVENT\n";

// Datei in die CAL einfügen (vor END:VCALENDAR)
$content = file_get_contents($icsPath);
if (strpos($content, 'END:VCALENDAR') === false) {
    echo "Ungültige ICS-Datei (keine END:VCALENDAR)\n"; exit(1);
}
$content = preg_replace("/END:VCALENDAR\s*$/", $event . "END:VCALENDAR\n", $content);
file_put_contents($icsPath, $content);

echo "Testtermin hinzugefügt: $uid\nBackup: " . $icsPath . ".bak.*\n";