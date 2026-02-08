<?php
//Daniel
//Dieses Skript liegt eigentlich außerhalb des Wegbroots und startet per Cronjob Kalenderupdater.php und logt das Ergebnis
require_once 'Kalenderupdater.php';

use SDP\Updater;

Updater\updateAlleKalendare();

