<?php
//Daniel
//Dieses Skript liegt eigentlich außerhalb des Wegbroots und startet per Cronjob Kalenderupdater.php und logt das Ergebnis
require_once 'Kalender/Kalenderupdater.php';

use SDP\Updater;

ob_start();
Updater\updateAlleKalendare();
$logContent = ob_get_clean();

$logDir = __DIR__ . '/logs';
$logFile = $logDir . '/kalender_update_' . date('Y-m-m') . '.log';

$timestamp = date('Y-m-d H:i:s');
$formattedLog = "--- UPDATE START: $timestamp ---\n";
$formattedLog .= $logContent;
$formattedLog .= "\n--- UPDATE ENDE ---\n\n";

file_put_contents($logFile, $formattedLog, FILE_APPEND);

if (php_sapi_name() !== 'cli') {
    echo "Update abgeschlossen. Log geschrieben in: $logFile";
}
