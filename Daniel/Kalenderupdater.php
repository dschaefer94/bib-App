
#!/usr/bin/env php
<?php
// PHP-CLI: Parameter sauber über getopt() einlesen
// --user-dir  Pfad des gefundenen Unterordners
// --name      Ordnername (Basename)

$options = getopt("", ["user-dir:", "name:", "dry-run"]);
$userDir = $options['user-dir'] ?? null;
$name    = $options['name']     ?? null;
//dry-run Modus muss nachher entfernt werden
$dryRun  = isset($options['dry-run']);

// Beispielaktion: Nur Ausgabe
echo ($dryRun ? "[Dry-Run] " : "");
echo "Empfangen: name='{$name}', user-dir='{$userDir}'\n";

// hier würdest du deine eigentliche Logik implementieren
// (z// (z. B. Dateien verarbeiten, Metadaten schreiben, etc.)

