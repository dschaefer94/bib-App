<?php

/**
 * Zeigt die Termine des aktuellen Tages aus einer ICS-Datei an.
 * - Liest .ics aus Nachbarordner (z.B. ./data/stundenplan.ics)
 * - Unterstützt DTSTART/DTEND, SUMMARY, LOCATION, DESCRIPTION
 * - Handhabt Zeitzonen rudimentär (Europe/Berlin) und ganztägige Termine
 */

date_default_timezone_set('Europe/Berlin'); // lokal sinnvoll für Paderborn

// === Konfiguration: Pfad zur ICS-Datei anpassen ===
$icsPath = __DIR__ . '/Kalender/Kalenderdateien/pbd2h24a/pbd2h24a.ics';

// --- Hilfsfunktionen ---
/**
 * ICS-Zeilen „unfolden“ (Fortsetzungszeilen beginnen mit Leerzeichen).
 * RFC 5545: lange Zeilen dürfen umgebrochen und mit führendem Leerzeichen fortgesetzt werden.
 */
function ics_unfold_lines(string $raw): array
{
  $lines = preg_split("/\r\n|\n|\r/", $raw);
  $unfolded = [];
  foreach ($lines as $line) {
    if ($line === '') {
      continue;
    }
    if (!empty($unfolded) && isset($line[0]) && ($line[0] === ' ' || $line[0] === "\t")) {
      // Fortsetzung: an vorherige Zeile anhängen (ohne führendes Leerzeichen)
      $unfolded[count($unfolded) - 1] .= substr($line, 1);
    } else {
      $unfolded[] = $line;
    }
  }
  return $unfolded;
}

/**
 * ICS-Datum/Zeit parsen (unterstützt:
 * - YYYYMMDD (ganztägig)
 * - YYYYMMDDTHHMMSS
 * - YYYYMMDDTHHMMSSZ (UTC)
 * - mit optionalem TZID=Europe/Berlin oder anderen TZIDs
 */
function parse_ics_datetime(string $value, ?string $tzid = null): array
{
  // Ganztägiger Termin
  if (preg_match('/^\d{8}$/', $value)) {
    $dt = DateTime::createFromFormat('Ymd', $value, new DateTimeZone($tzid ?: date_default_timezone_get()));
    return [$dt, true];
  }

  // UTC-Zeit (Suffix Z)
  if (preg_match('/^\d{8}T\d{6}Z$/', $value)) {
    $dt = DateTime::createFromFormat('Ymd\THis\Z', $value, new DateTimeZone('UTC'));
    // In lokale TZ umrechnen für Anzeige
    $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
    return [$dt, false];
  }

  // Lokale/benannte TZ
  if (preg_match('/^\d{8}T\d{6}$/', $value)) {
    $tz = new DateTimeZone($tzid ?: date_default_timezone_get());
    $dt = DateTime::createFromFormat('Ymd\THis', $value, $tz);
    return [$dt, false];
  }

  // Fallback: direkt versuchen
  try {
    $tz = new DateTimeZone($tzid ?: date_default_timezone_get());
    $dt = new DateTime($value, $tz);
    return [$dt, false];
  } catch (Exception $e) {
    return [null, false];
  }
}

/**
 * ICS-Datei zu Event-Array parsen.
 */
function parse_ics_events(string $icsFile): array
{
  if (!is_readable($icsFile)) {
    return [];
  }
  $raw = file_get_contents($icsFile);
  $lines = ics_unfold_lines($raw);

  $events = [];
  $inEvent = false;
  $current = [];

  foreach ($lines as $line) {
    if ($line === 'BEGIN:VEVENT') {
      $inEvent = true;
      $current = [];
      continue;
    }
    if ($line === 'END:VEVENT') {
      if (!empty($current)) {
        $events[] = $current;
      }
      $inEvent = false;
      $current = [];
      continue;
    }
    if (!$inEvent) {
      continue;
    }

    // Property + Params + Value trennen: NAME(;PARAMS):VALUE
    $parts = explode(':', $line, 2);
    if (count($parts) < 2) {
      continue;
    }
    [$propWithParams, $value] = $parts;

    $propParts = explode(';', $propWithParams);
    $propName = strtoupper($propParts[0]);
    $params = [];

    // Parameter sammeln (z.B. TZID=Europe/Berlin)
    for ($i = 1; $i < count($propParts); $i++) {
      $kv = explode('=', $propParts[$i], 2);
      if (count($kv) === 2) {
        $params[strtoupper($kv[0])] = $kv[1];
      }
    }

    switch ($propName) {
      case 'DTSTART':
      case 'DTEND':
        $tzid = $params['TZID'] ?? null;
        [$dt, $isAllDay] = parse_ics_datetime($value, $tzid);
        $current[$propName] = $dt;
        $current[$propName . '_ALLDAY'] = $isAllDay;
        break;

      case 'SUMMARY':
      case 'LOCATION':
      case 'DESCRIPTION':
      case 'UID':
        // Unescaped Kommas/Striche können enthalten sein; einfache Speicherung
        $current[$propName] = $value;
        break;
    }
  }

  return $events;
}

/**
 * Prüft, ob Termin am gegebenen Tag stattfindet (inkl. ganztägig).
 */
function event_matches_day(array $evt, DateTime $day): bool
{
  $y = (int)$day->format('Y');
  $m = (int)$day->format('n');
  $d = (int)$day->format('j');

  $start = $evt['DTSTART'] ?? null;
  $end   = $evt['DTEND']   ?? null;

  if (!$start) {
    return false;
  }

  // Ganztägige Events: DTSTART/DTEND sind Datum ohne Zeit.
  $isAllDay = ($evt['DTSTART_ALLDAY'] ?? false) || ($evt['DTEND_ALLDAY'] ?? false);

  if ($isAllDay) {
    // Bei ganztägigen Terminen gilt: END ist exklusiv; Event läuft bis (END - 1 Tag).
    $startDate = (clone $start)->setTime(0, 0, 0);
    $endDate   = $end ? (clone $end)->setTime(0, 0, 0) : (clone $start)->setTime(0, 0, 0);
    if ($end) {
      $endDate->modify('-1 day');
    }
    $dayDate = (clone $day)->setTime(0, 0, 0);
    return ($dayDate >= $startDate && $dayDate <= $endDate);
  } else {
    // Zeitbasierte Events: Überschneidung mit Tag prüfen
    $dayStart = (clone $day)->setTime(0, 0, 0);
    $dayEnd   = (clone $day)->setTime(23, 59, 59);

    // Falls kein DTEND gegeben: Event gilt, wenn Start am Tag liegt
    if (!$end) {
      return ($start >= $dayStart && $start <= $dayEnd);
    }
    // Normale Intervall-Überschneidung (END kann exklusiv sein, aber hier pragmatisch)
    return ($start <= $dayEnd && $end >= $dayStart);
  }
}

// --- Hauptlogik ---
$events = parse_ics_events($icsPath);
$today  = new DateTime('today');

$todayEvents = array_values(array_filter($events, fn($evt) => event_matches_day($evt, $today)));

// Einfaches HTML-Rendering
?>

<!DOCTYPE html>
<html lang="de">

<head>
  <meta charset="UTF-8">
  <title>Startseite</title>
  <link rel="stylesheet" href="CSS/layout.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body class="login-page">
  <main class="login-form">
    <div class="card">
      <header style="text-align: center; margin-bottom: 20px;">
        <h1>Herzlich Willkommen</h1>
        <p>
          <!-- Hier den Benutzernamen dynamisch einfügen -->
          <span id="benutzername">[Benutzername]</span>
        </p>
        <p>
          <!-- Hier die Klasse dynamisch einfügen -->
          <span id="klasse">[Klasse]</span>
        </p>
      </header>

      <section id="stundenplan-platzhalter" style="text-align: center; margin: 40px 0;">
        <h2>Stundenplan</h2>
        <div style="border: 1px dashed #ccc; padding: 50px; background-color: #f9f9f9;">

          <div id="termine">
            <h1>Termine heute <?= htmlspecialchars($today->format('d.m.Y')) ?></h1>

            <?php if (empty($todayEvents)): ?>
              <p class="empty">Keine Termine heute.</p>
            <?php else: ?>
              <?php foreach ($todayEvents as $evt): ?>
                <?php
                $start   = $evt['DTSTART'] ?? null;
                $end     = $evt['DTEND'] ?? null;
                $allDay  = ($evt['DTSTART_ALLDAY'] ?? false);

                $timeStr = $allDay ? 'Ganztägig'
                  : (($start ? $start->format('H:i') : '') . ($end ? '–' . $end->format('H:i') : ''));

                $summary = $evt['SUMMARY'] ?? '(ohne Titel)';
                $loc     = $evt['LOCATION'] ?? '';
                $desc    = $evt['DESCRIPTION'] ?? '';
                ?>
                <div class="item">
                  <div class="time"><?= htmlspecialchars($timeStr) ?></div>
                  <div class="summary"><?= htmlspecialchars($summary) ?></div>
                  <?php if ($loc): ?>
                    <div class="meta">Ort: <?= htmlspecialchars($loc) ?></div>
                  <?php endif; ?>
                  <?php if ($desc): ?>
                    <div class="meta">Notiz: <?= nl2br(htmlspecialchars($desc)) ?></div>
                  <?php endif; ?>
                </div>
                <!-- KEIN zusätzliches </div> hier! -->
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

      </section>

      <div class="actions" style="display: flex; justify-content: space-between; margin-top: 20px;">
        <a href="profil.html" class="btn">Profil bearbeiten</a>
        <a href="index.html" class="btn">Logout</a>
      </div>
    </div>

  </main>

  <script>
    // Beispiel-Skript, um Benutzerdaten zu laden und anzuzeigen
    // In einer echten Anwendung würden diese Daten vom Server kommen (z.B. per API)
    document.addEventListener('DOMContentLoaded', () => {
      // Annahme: Benutzerdaten sind im sessionStorage gespeichert nach dem Login
      const benutzername = sessionStorage.getItem('benutzername');
      const klasse = sessionStorage.getItem('klasse');

      if (benutzername) {
        document.getElementById('benutzername').textContent = benutzername;
      }
      if (klasse) {
        document.getElementById('klasse').textContent = klasse;
      }
    });
  </script>



</body>

</html>