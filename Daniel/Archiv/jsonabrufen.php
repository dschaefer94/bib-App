<?php
namespace ppb\Controller;

class CalendarController
{
    // Konfiguration: Pfad außerhalb des Webroots
    private string $calendarFile = '/var/data/stundenplan.json';

    /**
     * Liefert den Stundenplan als JSON, nur für authentifizierte Nutzer.
     * GET /restapi.php/calendar
     */
    public function getCalendar()
    {
        header('Content-Type: application/json; charset=utf-8');

        // --- Authentifizierung prüfen (Session-Beispiel) ---
        // Deine restapi.php ruft session_start() bereits auf.
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Optional: Rollen-/Berechtigungsprüfung
        // if (!in_array('calendar:view', $user['permissions'] ?? [], true)) {
        //     http_response_code(403);
        //     echo json_encode(['error' => 'Forbidden'], JSON_UNESCAPED_UNICODE);
        //     return;
        // }

        // --- Datei prüfen ---
        if (!is_file($this->calendarFile) || !is_readable($this->calendarFile)) {
            http_response_code(404);
            echo json_encode(['error' => 'Calendar not found'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // --- Conditional Requests (ETag/Last-Modified) ---
        $etag = '"' . md5_file($this->calendarFile) . '"';
        $lastModified = gmdate('D, d M Y H:i:s', filemtime($this->calendarFile)) . ' GMT';
        header('ETag: ' . $etag);
        header('Last-Modified: ' . $lastModified);
        header('Cache-Control: private, max-age=60'); // kurzzeitiges Caching beim Client

        $ifNoneMatch    = $_SERVER['HTTP_IF_NONE_MATCH']    ?? '';
        $ifModifiedSince= $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';

        if ($ifNoneMatch === $etag || $ifModifiedSince === $lastModified) {
            http_response_code(304); // Not Modified
            return;
        }

        // --- Optional: Filter aus $_GET anwenden ---
        $klasse = $_GET['klasse'] ?? null;
        $week   = $_GET['week']   ?? null;

        $json = file_get_contents($this->calendarFile);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(500);
            echo json_encode(['error' => 'Calendar JSON invalid'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Beispielhafte Filterung (wenn deine Datei Listen enthält):
        if ($klasse || $week) {
            $data['entries'] = array_values(array_filter($data['entries'] ?? [], function ($e) use ($klasse, $week) {
                $ok = true;
                if ($klasse) { $ok = $ok && ($e['klasse'] ?? null) === $klasse; }
                if ($week)   { $ok = $ok && ($e['week']   ?? null) === $week; }
                return $ok;
            }));
               }

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
  }