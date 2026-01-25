<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle pre-flight requests for CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

session_start();

spl_autoload_register(function ($className) {
    if (substr($className, 0, 4) !== 'ppb\\') {
        return;
    }

    $fileName = __DIR__ . '/' . str_replace('\\', DIRECTORY_SEPARATOR, substr($className, 4)) . '.php';

    if (file_exists($fileName)) {
        include $fileName;
    }
});

$endpoint = [];

// Versuche PATH_INFO zu nutzen (wenn mod_rewrite aktiv ist)
if (!empty($_SERVER['PATH_INFO'])) {
    $endpoint = explode('/', trim($_SERVER['PATH_INFO'], '/'));
} 
// Fallback: nutze Query-Parameter (z.B. ?path=personalData/loadProfile)
elseif (!empty($_GET['path'])) {
    $endpoint = explode('/', trim($_GET['path'], '/'));
    error_log('Using GET path parameter: ' . $_GET['path']);
} 
// Fallback: nutze die REQUEST_URI und extrahiere den Teil nach restapi.php
else {
    $uri = trim($_SERVER['REQUEST_URI'], '/');
    if (strpos($uri, 'restapi.php/') !== false) {
        $parts = explode('restapi.php/', $uri);
        $endpoint = explode('/', trim($parts[1], '/'));
        error_log('Using REQUEST_URI path: ' . $parts[1]);
    } else {
        error_log('No valid endpoint found. REQUEST_URI: ' . $uri);
    }
}

if (empty($endpoint) || empty($endpoint[0])) {
    http_response_code(400);
    echo json_encode(['isError' => true, 'msg' => 'No valid endpoint provided']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$controllerName = $endpoint[0];
$endpoint2 = isset($endpoint[1]) ? $endpoint[1] : false;
$id = false;
$alias = false;

if ($endpoint2) {
    if (preg_match('/\b[0-9a-f]{8}\b-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-\b[0-9a-f]{12}\b/', $endpoint2)) {
        $id = $endpoint2;
    } else {
        $alias = $endpoint2;
    }
}

$controllerClassName = 'ppb\\Controller\\' . str_replace('_', '', ucwords(str_replace('_', ' ', $controllerName), ' ')) . 'Controller';

error_log('Controller Name: ' . $controllerName);
error_log('Controller Class Name: ' . $controllerClassName);
error_log('Endpoint 2 (alias): ' . ($endpoint2 ? $endpoint2 : 'false'));
error_log('Method Name will be: ' . ($alias ? $alias : 'get' . ucfirst($controllerName)));

if ($_SERVER['REQUEST_METHOD'] == "DELETE") {
    $methodName = "delete" . ucfirst($controllerName);
} else if ($_SERVER['REQUEST_METHOD'] == "PUT") {
    $methodName = "update" . ucfirst($controllerName);
} else if ($_SERVER['REQUEST_METHOD'] == "POST") {
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
}

$classExists = class_exists($controllerClassName);
$methodExists = method_exists($controllerClassName, $methodName);
error_log('Checking if class exists: ' . ($classExists ? 'yes' : 'no'));
error_log('Checking if method exists: ' . ($methodExists ? 'yes' : 'no'));

if ($methodExists) {
    $controller = new $controllerClassName();
    if ($_SERVER['REQUEST_METHOD'] == "GET") {
        if ($id) {
            $controller->$methodName($id);
        } else {
            $controller->$methodName();
        }
    } else if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $controller->$methodName($data);
    } else if ($_SERVER['REQUEST_METHOD'] == "DELETE") {
        $controller->$methodName($id);
    } else {
        $controller->$methodName($id, $data);
    }
} else {
    //http_response_code(404);
    new \ppb\Library\Msg(true, 'Page not found: ' . $controllerClassName . '::' . $methodName);
}
