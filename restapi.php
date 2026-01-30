<?php 
    session_start();

    spl_autoload_register(function ($className) {
        if (substr($className, 0, 4) !== 'ppb\\') { return; }

        $fileName = __DIR__.'/'.str_replace('\\', DIRECTORY_SEPARATOR, substr($className, 4)).'.php';

        if (file_exists($fileName)) { include $fileName; }
    });    
   
    $endpoint = explode('/', trim($_SERVER['PATH_INFO'],'/'));
    $data = json_decode(file_get_contents('php://input'), true);

    //$endpoint[0] ist der erste Part der URL nach restapi.php z.B. "/task" oder "/project"
    //der Slash wird in Zeile 12 entfernt
    $controllerName = $endpoint[0];
    //endpoint2 (endpoint[1]) ist der zweite Part der URL nach restapi.php z.B. "/getfilteredtasks/duedate=1%20DAY",
    //also Alias (mit Parametern) oder ID
    //isset prüft, ob es den zweiten Part der URL gibt, sonst false
    $endpoint2 = isset($endpoint[1]) ? $endpoint[1] : false;
    $id = false;
    $alias = false;

    //hier wird geprüft, ob endpoint2 eine UUID ist oder ein Alias
    if ($endpoint2) {
        if (preg_match('/\b[0-9a-f]{8}\b-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-\b[0-9a-f]{12}\b/', $endpoint2)) {
            $id = $endpoint2;
        } else {
            $alias = $endpoint2;
        }
    }
    
    //endpoint[0], also $controllerName gibt den Controller-Namen vor, z.B. bei /task -> TaskController
    $controllerClassName = 'ppb\\Controller\\'.ucfirst($controllerName). 'Controller';
    
    //endpoint[0] gibt hier den Methodennamen im Controller vor, je nach HTTP-Request-Methode, z.B./task und GET -> getTask
    if ($_SERVER['REQUEST_METHOD'] == "DELETE") {
        $methodName = "delete" . ucfirst($controllerName);
    } else if ($_SERVER['REQUEST_METHOD'] == "PUT") {
           //für Aufgabe relevant 11.11.25
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
    
    //je nach HTTP-Request-Methode wird die entsprechende Methode im Controller aufgerufen
    //manchmal mit und manchmal ohne Parameter
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