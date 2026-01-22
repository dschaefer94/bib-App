
<?php
require_once 'Kalender/Kalenderupdater.php';
use SDP\Updater;

// Echo abfangen
ob_start();

// Updater starten
Updater\updateAlleKalendare();

// Echos als String holen
$log = ob_get_clean();

// Zeilen splitten
$lines = preg_split('/\r\n|\r|\n/', $log);

// HTML ausgeben
?>
<!DOCTYPE html>
<html lang="de">
<meta charset="utf-8">
<body>

<h1>Kalender-Updates</h1>

<?php
foreach ($lines as $line) {

    if (trim($line) === '') continue;

    // 1) Ist es die Start-Zeile eines Updates?
    if (str_starts_with($line, 'Starte Kalender-Update für Klasse:')) {
        echo "<h3>" . htmlspecialchars($line) . "</h3>\n";
        echo "<ol>\n";       // neue Liste beginnen
        continue;
    }

    // 2) Ist es das Ende eines Updates?
    if (str_starts_with($line, 'Datenbankoperationen erfolgreich')) {
        echo "<li>" . htmlspecialchars($line) . "</li>\n";
        echo "</ol>\n";      // Liste schließen
        continue;
    }

    // 3) Normale Zeile
    echo "<li>" . htmlspecialchars($line) . "</li>\n";
}
?>

</body>
</html>
