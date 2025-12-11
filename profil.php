<?php
/**
 * Florian
 * profil.php - API-Endpunkt für die Benutzerverwaltung
 *
 * Dieses Skript dient als API-Endpunkt für die Web-Anwendung.
 * Es übernimmt folgende Aufgaben:
 * 1. Laden aller notwendigen Klassen (Models, Controller).
 * 2. Instanziieren der benötigten Controller.
 * 3. Verarbeiten von GET- und POST-Requests zum Laden und Speichern von Benutzern.
 * 4. Rückgabe der Ergebnisse im JSON-Format.
 */

// -------------------- DEPENDENCIES LADEN --------------------
require_once __DIR__ . '/Florian/Library/Msg.php';
require_once __DIR__ . '/Florian/Model/Database.php';
require_once __DIR__ . '/Florian/Model/Florian_BenutzerModel.php';
require_once __DIR__ . '/Florian/Model/Florian_KlassenModel.php';
require_once __DIR__ . '/Florian/Model/Florian_Persoenliche_datenModel.php';

require_once __DIR__ . '/Florian/Controller/Florian_KlassenController.php';
require_once __DIR__ . '/Florian/Controller/Florian_Persoenliche_datenController.php';
require_once __DIR__ . '/Florian/Controller/Florian_BenutzerController.php';

use ppb\Controller\Florian_BenutzerController;
use ppb\Controller\Florian_KlassenController;

// -------------------- RESPONSE HEADER --------------------
header('Content-Type: application/json');

// -------------------- CONTROLLER INSTANZIIEREN --------------------
$benutzerController = new Florian_BenutzerController();
$klassenController = new Florian_KlassenController();

// -------------------- RESPONSE INITIALISIEREN --------------------
$response = [
    'success' => false,
    'message' => '',
    'error' => null,
    'userData' => null,
    'klassen' => []
];

// -------------------- HAUPTLOGIK (Request-Verarbeitung) --------------------
try {
    $response['klassen'] = $klassenController->getAllClasses();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['benutzer_id'])) {
            $benutzer_id = (int)$_GET['benutzer_id'];
            $result = $benutzerController->loadUser($benutzer_id);

            if ($result['success']) {
                $response['success'] = true;
                $response['userData'] = $result['data'];
            } else {
                $response['error'] = $result['error'];
                http_response_code(404); // Not Found
            }
        } else {
            // No action specified for GET without parameters, but we can return all classes
            $response['success'] = true;
        }
    }

    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Assume save operation from the previous logic
        $postData = $_POST;
        // The js will send 'save' as a field
        if (isset($postData['name'])) {
            $result = $benutzerController->saveUser($postData);

            if ($result['success']) {
                $response['success'] = true;
                $response['message'] = $result['message'];
                // Reload user data to send back the latest state
                if (isset($postData['benutzer_id'])) {
                    $reload = $benutzerController->loadUser((int)$postData['benutzer_id']);
                    if ($reload['success']) {
                        $response['userData'] = $reload['data'];
                    }
                }
            } else {
                $response['error'] = $result['error'];
                http_response_code(400); // Bad Request
            }
        } else {
            $response['error'] = "Keine Speicherdaten empfangen.";
            http_response_code(400);
        }
    }
    
    else {
        $response['error'] = "Ungültige Anfragemethode.";
        http_response_code(405); // Method Not Allowed
    }

} catch (\Exception $e) {
    $response['error'] = "Ein unerwarteter Serverfehler ist aufgetreten: " . $e->getMessage();
    http_response_code(500); // Internal Server Error
}

// -------------------- JSON-ANTWORT AUSGEBEN --------------------
echo json_encode($response);