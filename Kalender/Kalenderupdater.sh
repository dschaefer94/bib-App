
#!/usr/bin/env bash

# Kalenderupdater-Runner
# - durchläuft jeden Unterordner in ./Kalenderdateien (Klassenordner)
# - ruft für jeden Ordner den PHP-Worker Kalenderupdater.php auf
# - übergebener Parameter ist der Klassenname, mit dem der Worker dann die DB anruft,
# um die ics-Datei herunterzuladen und auszuwerten

BASE_DIR="Kalenderdateien"
UPDATER="Kalenderupdater.php"

for klassenordner in "$BASE_DIR"/*; do
# mit -d prüfen wir, ob es ein Verzeichnis ist,
# continue skippet zur nächsten Iteration ohne den Updater aufzurufen
  [ -d "$klassenordner" ] || continue
  php "$UPDATER" "$klassenordner"
done
