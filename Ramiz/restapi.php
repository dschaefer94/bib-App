<?php
// Teacher-style: CORS + OPTIONS handling
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
}

// Minimal REST front controller
session_start();

// Prefer JSON responses for the API (prevents HTML error pages from breaking JSON.parse)
header('Content-Type: application/json; charset=utf-8');

// Don't display PHP errors as HTML to the client — log them instead
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// автозагрузка классов
spl_autoload_register(function ($className) {
    if (substr($className, 0, 4) !== 'ppb\\') { return; }

    $fileName = __DIR__.'/'.str_replace('\\', DIRECTORY_SEPARATOR, substr($className, 4)).'.php';

    if (file_exists($fileName)) { include $fileName; }
});

// PATH_INFO kann bei manchen Servern fehlen — Fallback auf REQUEST_URI
$path = $_SERVER['PATH_INFO'] ?? null;
if (!$path) {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if ($script && strpos($requestPath, $script) === 0) {
        $path = substr($requestPath, strlen($script));
    } else {
        // falls das nicht passt, entferne das Verzeichnis des Scripts
        $dir = rtrim(dirname($script), '/\\');
        if ($dir !== '' && strpos($requestPath, $dir) === 0) {
            $path = substr($requestPath, strlen($dir));
        } else {
            $path = $requestPath;
        }
    }
}

$endpoint = explode('/', trim($path, '/'));
$data = json_decode(file_get_contents('php://input'), true);

// Legacy support: allow ?action=login (and optional &controller=user)
// This helps older JS that posts to restapi.php?action=login instead of /restapi.php/user
if ((empty($endpoint) || empty($endpoint[0])) && isset($_GET['action'])) {
    $legacyController = $_GET['controller'] ?? 'user';
    $endpoint = [$legacyController];
    // mark alias so later routing will call the action method
    $alias = $_GET['action'];
}

// Logging für Debugging (Datei: log.txt im Projekt)
file_put_contents(__DIR__.'/log.txt', "Endpoint: " . print_r($endpoint, true) . "\n", FILE_APPEND);
file_put_contents(__DIR__.'/log.txt', "Method: " . ($_SERVER['REQUEST_METHOD'] ?? '') . "\n", FILE_APPEND);
file_put_contents(__DIR__.'/log.txt', "Data: " . print_r($data, true) . "\n", FILE_APPEND);

if (empty($endpoint) || empty($endpoint[0])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "No endpoint specified"], JSON_PRETTY_PRINT);
    exit;
}

$controllerName = $endpoint[0];
$endpoint2 = isset($endpoint[1]) ? $endpoint[1] : false;
$id = false;
$alias = isset($alias) ? $alias : false;

if ($endpoint2) {
    if (preg_match('/\b[0-9a-f]{8}\b-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-\b[0-9a-f]{12}\b/', $endpoint2)) {
        $id = $endpoint2;
    } else {
        $alias = $endpoint2;
    }
}

$controllerClassName = 'ppb\\Controller\\'.ucfirst($controllerName).'Controller';

// Bestimme Methodenname
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // POST /restapi.php/user -> writeUser
    // POST /restapi.php/user/login -> login (alias)
    if ($alias) {
        $methodName = $alias;
    } else {
        $methodName = "write" . ucfirst($controllerName);
    }
} else if ($_SERVER['REQUEST_METHOD'] == "GET") {
    if ($alias) {
        $methodName = $alias;
    } else {
        $methodName = "get" . ucfirst($controllerName);
    }
} else {
    $methodName = "get" . ucfirst($controllerName);
}

// Controller-/Methodenaufruf
if (class_exists($controllerClassName) && method_exists($controllerClassName, $methodName)) {
    $controller = new $controllerClassName();
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $controller->$methodName($data);
    } else if ($_SERVER['REQUEST_METHOD'] == "GET") {
        // Pass GET params if controller expects them
        if ($id) {
            $controller->$methodName($id);
        } else {
            $controller->$methodName();
        }
    } else {
        $controller->$methodName($id, $data);
    }
} else {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Page not found: $controllerClassName::$methodName"], JSON_PRETTY_PRINT);
}

?>
