
<?php
/**
 * Daniel
 * Skript (am besten außerhalb vom Webroot im Document Root), per Cronjob alle 10 min
 * Initialisiert Kalenderupdater.php, welches alle Stundenpläne updatet
 * gibt die echo-Meldungen des Prozesses pro Klasseneintrag aus
 */
require_once 'Kalender/Kalenderupdater.php';
use SDP\Updater;

ob_start();
Updater\updateAlleKalendare();
$log = ob_get_clean();
$lines = preg_split('/\r\n|\r|\n/', $log);
?>
<!DOCTYPE html>
<html lang="de">
<meta charset="utf-8">
<body>

<h1>Kalender-Updates</h1>

<?php
foreach ($lines as $line) {
    if (trim($line) === '') continue;
    if (str_starts_with($line, 'Starte Kalender-Update für Klasse:')) {
        echo "<h3>" . htmlspecialchars($line) . "</h3>\n";
        echo "<ol>\n"; 
        continue;
    }
    if (str_starts_with($line, 'Datenbankoperationen erfolgreich')) {
        echo "<li>" . htmlspecialchars($line) . "</li>\n";
        echo "</ol>\n";
        continue;
    }
    echo "<li>" . htmlspecialchars($line) . "</li>\n";
}
?>
</body>
</html>
