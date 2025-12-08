<?php 
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
    
    $controllerClassName = 'ppb\\Controller\\Florian_'.ucfirst($controllerName). 'Controller';
    
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $methodName = '';

    // Logik zur Bestimmung des Methodennamens basierend auf der HTTP-Methode und dem Vorhandensein einer ID
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
    }
    
    if (method_exists($controllerClassName, $methodName)) {
        $controller = new $controllerClassName();
        $params = [];

        // Parameter für den Methodenaufruf vorbereiten
        switch ($requestMethod) {
            case 'GET':
            case 'DELETE':
                if ($id) {
                    $params[] = $id;
                }
                break;
            case 'PUT':
                // Bei PUT wird die ID als Parameter erwartet
                if ($id) {
                    $params[] = $id;
                }
                break;
            case 'POST':
                // POST erwartet keine ID in der URL, die save-Methode sollte das verarbeiten können
                break;
        }

        // Die Methode mit den entsprechenden Parametern aufrufen
        call_user_func_array([$controller, $methodName], $params);

    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => "API endpoint not found: {$controllerClassName}::{$methodName}"]);
    }
?>