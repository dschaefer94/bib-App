<?php
/**
 * ierestapi.php - Generischer REST-API-Router
 *
 * Dieses Skript fungiert als einfacher, aber flexibler Router für eine REST-API.
 * Es analysrt die URL, um den Ziel-Controller und die aufzurufende Methode zu bestimmen,
 * und leitet die Anfrage entsprechend weiter.
 *
 * URL-Struktur: /restapi.php/{controllerName}/{id}
 * Beispiel: /restapi.php/benutzer/123
 */

// Startet die Session, falls benötigt (z.B. für Authentifizierung).
session_start();

/**
 * Ein einfacher PSR-4-kompatibler Autoloader.
 * Lädt Klassen automatisch, wenn sie im `ppb` Namespace sind.
 * Beispiel: `ppb\Controller\MyController` wird zu `Controller/MyController.php`.
 */
spl_autoload_register(function ($className) {
    // Ignoriert Klassen, die nicht zum Projekt-Namespace 'ppb' gehören.
    if (substr($className, 0, 4) !== 'ppb\\') { return; }

    // Ersetzt den Namespace-Präfix und wandelt Backslashes in Verzeichnis-Trennzeichen um.
    $fileName = __DIR__ . '/' . str_replace('\\', DIRECTORY_SEPARATOR, substr($className, 4)) . '.php';

    // Lädt die Datei, wenn sie existiert.
    if (file_exists($fileName)) { include $fileName; }
});

// -------------------- URL-PARSING UND ROUTING --------------------

// Holt den Pfad aus der URL (z.B. /benutzer/123) und teilt ihn in Segmente auf.
$endpoint = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));

// Das erste Segment der URL bestimmt den Controller (z.B. "benutzer").
$controllerName = $endpoint[0] ?? null;

// Das zweite Segment ist die ID.
$id = isset($endpoint[1]) && is_numeric($endpoint[1]) ? (int)$endpoint[1] : null;

// Baut den vollqualifizierten Klassennamen für den Controller dynamisch zusammen.
// z.B. "benutzer" -> "ppb\Controller\Florian_BenutzerController"
$controllerClassName = 'ppb\\Controller\\Florian_' . ucfirst($controllerName) . 'Controller';

// Holt die HTTP-Request-Methode (GET, POST, PUT, DELETE).
$requestMethod = $_SERVER['REQUEST_METHOD'];
$methodName = '';

// Logik zur Bestimmung des Methodennamens im Controller basierend auf der HTTP-Methode und dem Vorhandensein einer ID.
// Konvention: {http_verb}{controller}{Api-Suffix} z.B. getBenutzerByIdApi
switch ($requestMethod) {
    case 'GET':
        $methodName = $id ? 'get' . ucfirst($controllerName) . 'ByIdApi' : 'get' . ucfirst($controllerName) . 'Api';
        break;
    case 'POST':
    case 'PUT':
        $methodName = 'save' . ucfirst($controllerName) . 'Api';
        break;
    case 'DELETE':
        $methodName = 'delete' . ucfirst($controllerName) . 'Api';
        break;
    default:
        // Unbekannte Methode
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
        exit;
}

// -------------------- CONTROLLER-AUFRUF --------------------

// Prüft, ob die Klasse und die dynamisch ermittelte Methode existieren.
if (class_exists($controllerClassName) && method_exists($controllerClassName, $methodName)) {
    // Instanziiert den Controller.
    $controller = new $controllerClassName();
    $params = [];

    // Bereitet die Parameter für den Methodenaufruf vor.
    // Bei GET, PUT, DELETE wird die ID als Parameter erwartet, falls vorhanden.
    if ($id && in_array($requestMethod, ['GET', 'PUT', 'DELETE'])) {
        $params[] = $id;
    }

    // Ruft die Methode auf dem Controller-Objekt mit den vorbereiteten Parametern auf.
    call_user_func_array([$controller, $methodName], $params);

} else {
    // Wenn die Methode oder der Controller nicht gefunden wird, wird ein 404-Fehler zurückgegeben.
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => "API endpoint not found: {$controllerClassName}::{$methodName}"]);
}