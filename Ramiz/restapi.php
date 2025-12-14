<?php
// CORS + OPTIONS handling
// Prüft, ob die Anfrage von einer bestimmten Origin kommt und fügt die entsprechenden CORS-Header hinzu
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");  // Erlaubt den Zugriff von dieser Origin
    header('Access-Control-Allow-Credentials: true');  // Erlaubt das Senden von Cookies
    header('Access-Control-Max-Age: 86400');    // Setzt das Cache-Limit für den Preflight-Request auf 1 Tag
}

// OPTIONS wird von modernen Browsern vor einem tatsächlichen API-Request gesendet, um die erlaubten Methoden und Header zu prüfen
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");  // Erlaubt GET, POST und OPTIONS als Methoden

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");  // Erlaubt die angegebenen Header

    exit(0);  
}

session_start();  

// Setzt den Content-Type auf JSON, um sicherzustellen, dass die Antwort als JSON verarbeitet wird
header('Content-Type: application/json; charset=utf-8');

// Verhindert das Anzeigen von PHP-Fehlern auf der Seite, stattdessen werden sie in eine Logdatei geschrieben
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Automatische Ladefunktion für Klassen
spl_autoload_register(function ($className) {
    if (substr($className, 0, 4) !== 'ppb\\') { return; }  // Stellt sicher, dass nur Klassen mit dem Namespace "ppb" geladen werden

    $fileName = __DIR__.'/'.str_replace('\\', DIRECTORY_SEPARATOR, substr($className, 4)).'.php';  // Wandelt den Namespace in einen Dateipfad um

    if (file_exists($fileName)) { include $fileName; }  // Lädt die Datei, wenn sie existiert
});

// Verarbeitet den URL-Pfad, um den Endpunkt zu extrahieren (z. B. /user/1)
$path = $_SERVER['PATH_INFO'] ?? null;
if (!$path) {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);  // Extrahiert den Pfad aus der Anfrage-URL
    $script = $_SERVER['SCRIPT_NAME'] ?? '';  // Holt den Scriptnamen
    if ($script && strpos($requestPath, $script) === 0) {
        $path = substr($requestPath, strlen($script));  // Entfernt den Scriptnamen, um den Rest der URL zu bekommen
    } else {
        $dir = rtrim(dirname($script), '/\\');
        if ($dir !== '' && strpos($requestPath, $dir) === 0) {
            $path = substr($requestPath, strlen($dir));
        } else {
            $path = $requestPath;
        }
    }
}

// Teilt den Pfad in verschiedene Teile (z. B. /user/1 -> ["user", "1"])
$endpoint = explode('/', trim($path, '/'));
$data = json_decode(file_get_contents('php://input'), true);  // Liest und decodiert die eingehenden JSON-Daten

// Legacy-Support: Erlaubt das Aufrufen von alten URLs wie ?action=login anstelle von /user/login
if ((empty($endpoint) || empty($endpoint[0])) && isset($_GET['action'])) {
    $legacyController = $_GET['controller'] ?? 'user';  // Der Standard-Controller ist 'user'
    $endpoint = [$legacyController];
    $alias = $_GET['action'];  // Der Alias wird für die Methode verwendet
}

$controllerName = $endpoint[0];  // Der erste Teil des Endpunkts ist der Controllername (z. B. 'user')
$endpoint2 = isset($endpoint[1]) ? $endpoint[1] : false;  // Der zweite Teil des Endpunkts ist optional (z. B. '1' bei /user/1)
$id = false;
$alias = isset($alias) ? $alias : false;

// Überprüft, ob der zweite Endpunkt Teil eine ID oder einen Alias darstellt
if ($endpoint2) {
    if (preg_match('/\b[0-9a-f]{8}\b-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-\b[0-9a-f]{12}\b/', $endpoint2)) {
        $id = $endpoint2;  // Wenn es eine UUID ist, behandeln wir es als ID
    } else {
        $alias = $endpoint2;  // Ansonsten als Alias
    }
}

// Der Name der Controller-Klasse, basierend auf dem Endpunkt
$controllerClassName = 'ppb\\Controller\\'.ucfirst($controllerName).'Controller';

// Bestimmt den Methodennamen basierend auf der HTTP-Methode (POST, GET, etc.)
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // POST /restapi.php/user -> writeUser
    // POST /restapi.php/user/login -> login (alias)
    if ($alias) {
        $methodName = $alias;  // Wenn ein Alias angegeben ist, verwenden wir diesen als Methodennamen
    } else {
        $methodName = "write" . ucfirst($controllerName);  // Ansonsten verwenden wir 'write' + Controllername
    }
} else if ($_SERVER['REQUEST_METHOD'] == "GET") {
    if ($alias) {
        $methodName = $alias;  // Wenn ein Alias angegeben ist, verwenden wir diesen als Methodennamen
    } else {
        $methodName = "get" . ucfirst($controllerName);  // Ansonsten verwenden wir 'get' + Controllername
    }
} else {
    $methodName = "get" . ucfirst($controllerName);  // Standardmäßig verwenden wir 'get' + Controllername für andere Methoden
}

// Ruft die Controller-Methode auf, wenn sie existiert
if (class_exists($controllerClassName) && method_exists($controllerClassName, $methodName)) {
    $controller = new $controllerClassName();  // Erstellt eine Instanz des Controllers
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $controller->$methodName($data);  // Bei POST wird die Methode mit den übergebenen Daten aufgerufen
    } else if ($_SERVER['REQUEST_METHOD'] == "GET") {
        // Bei GET wird entweder eine ID übergeben oder keine (je nach Anfrage)
        if ($id) {
            $controller->$methodName($id);
        } else {
            $controller->$methodName();
        }
    } else {
        $controller->$methodName($id, $data);  // Andere Methoden wie PUT oder DELETE
    }
} else {
    // Wenn die Controller-Klasse oder die Methode nicht existiert, wird ein 404-Fehler zurückgegeben
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Page not found: $controllerClassName::$methodName"], JSON_PRETTY_PRINT);
}
?>
