
#!/usr/bin/env bash

# Daniel
# Kalenderupdater-Runner
# - durchläuft jeden Unterordner in ./Kalenderdateien (Klassenordner)
# - ruft für jeden Ordner den PHP-Worker Kalenderupdater.php auf
# - übergebener Parameter ist der Klassenname, mit dem der Worker dann die DB anruft,
# um die ics-Datei herunterzuladen und auszuwerten

# Ordner und Worker liegen hier mit im Ordner rum
BASE_DIR="Kalenderdateien"
UPDATER="Kalenderupdater.php"

for klassenordner in "$BASE_DIR"/*; do
# mit -d prüfen wir, ob es ein Verzeichnis ist,
# continue skippt zur nächsten Iteration ohne den Updater aufzurufen
  [ -d "$klassenordner" ] || continue
  name="$(basename "$klassenordner")"
  php "$UPDATER" --klassenname "$name"
done
