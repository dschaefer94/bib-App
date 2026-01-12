<?php


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

    session_start();

    spl_autoload_register(function ($className) {
        if (substr($className, 0, 4) !== 'ppb\\') { return; }

        $fileName = __DIR__.'/'.str_replace('\\', DIRECTORY_SEPARATOR, substr($className, 4)).'.php';

        if (file_exists($fileName)) { include $fileName; }
    });    
   
    $endpoint = explode('/', trim($_SERVER['PATH_INFO'],'/'));
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
    
    $controllerClassName = 'ppb\\Controller\\'.ucfirst($controllerName). 'Controller';
    
    if ($_SERVER['REQUEST_METHOD'] == "DELETE") {
        $methodName = "delete" . ucfirst($controllerName);
    } else if ($_SERVER['REQUEST_METHOD'] == "PUT") {
        $methodName = "update" . ucfirst($controllerName);
    } else if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $methodName = "write" . ucfirst($controllerName);
    } else if ($_SERVER['REQUEST_METHOD'] == "GET") {
        if ($alias) {
            $methodName = $alias;
        } else {
            $methodName = "get" . ucfirst($controllerName);
        } 
    }

    if (method_exists($controllerClassName, $methodName)) {
        $controller = new $controllerClassName();
        if ($_SERVER['REQUEST_METHOD'] == "GET") {
            if ($id) {
                $controller->$methodName($id);
            } else {
                $controller->$methodName();
            }
        } else if ($_SERVER['REQUEST_METHOD'] == "POST"){
            $controller->$methodName($data);
        } else if ($_SERVER['REQUEST_METHOD'] == "DELETE"){
            $controller->$methodName($id);    
        } else {
            $controller->$methodName($id, $data);
        }
    } else {
        //http_response_code(404);
        new \ppb\Library\Msg(true, 'Page not found: '.$controllerClassName.'::'.$methodName); 

    }
?>