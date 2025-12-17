
#!/usr/bin/env bash
set -euo pipefail

# Basisverzeichnis mit den Benutzer-Unterordnern
BASE_DIR="${1:-"./Benutzer"}"

# Optionaler Dry-Run (nur anzeigen, nicht „wirken“)
DRY_RUN="${DRY_RUN:-false}"  # DRY_RUN=true zum Testen

# Pfad zum PHP-CLI-Skript
PHP_WORKER="${PHP_WORKER:-"./worker.php"}"

echo "Starte Verarbeitung in: $BASE_DIR"
[[ "$DRY_RUN" == "true" ]] && echo "(Dry-Run aktiv)"

# Variante A (empfohlen, robust gegen Leerzeichen/sonderzeichen): find + -print0
# -> iteriert ausschließlich über *Unterordner* (Tiefe 1)
while IFS= read -r -d '' dir; do
  # dir ist ein absoluter/relativer Pfad; wir extrahieren den Ordnernamen
  name="$(basename "$dir")"

  # Aufruf des PHP-Skripts mit benannten Optionen
  if [[ "$DRY_RUN" == "true" ]]; then
    php "$PHP_WORKER" --user-dir "$dir" --name "$name" --dry-run
  else
    php "$PHP_WORKER" --user-dir "$dir" --name "$name"
  fi

done < <(find "$BASE_DIR" -mindepth 1done < <(find "$BASE_DIR" -mindepth 1 -maxdepth 1 -type d -print0)

