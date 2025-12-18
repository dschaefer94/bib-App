
#!/usr/bin/env php
<?php
// PHP-CLI: Parameter sauber über getopt() einlesen
// --user-dir  Pfad des gefundenen Unterordners
// --name      Ordnername (Basename)

$options = getopt("", ["klassenname:"]);

$name    = $options['klassenname'] ?? null;


// hier würdest du deine eigentliche Logik implementieren
// (z// (z. B. Dateien verarbeiten, Metadaten schreiben, etc.)

