<?php
/**
 * index.php / test.php - MVC-Einstiegspunkt
 * Orchestriert den BenutzerController und zeigt die View an
 */

// Datenbankverbindungs-Konfiguration und Models laden
require_once __DIR__ . '/Model/Database.php';
require_once __DIR__ . '/Model/Florian_BenutzerModel.php';
require_once __DIR__ . '/Model/Florian_KlassenModel.php';
require_once __DIR__ . '/Model/Florian_persoehnliche_datenModel.php';

// Controller laden
require_once __DIR__ . '/Controller/Florian_BenutzerController.php';

use ppb\Controller\Florian_BenutzerController;

// ========== CONTROLLER INSTANZIIEREN ==========
$controller = new Florian_BenutzerController();

// ========== VARIABLE INITIALISIEREN ==========
$userData = [];
$klassen = [];
$success_message = '';
$error = '';

// ========== HAUPTLOGIK ==========
try {
    // GET-Request: Benutzer laden
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['benutzer_id'])) {
        $benutzer_id = (int)$_GET['benutzer_id'];
        
        $result = $controller->loadUser($benutzer_id);
        
        if ($result['success']) {
            $userData = $result['data'];
        } else {
            $error = $result['error'];
        }
    }
    
    // POST-Request: Benutzer speichern
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
        $result = $controller->saveUser($_POST);
        
        // DEBUG: zeige was der Controller zurückgibt
        error_log("saveUser result: " . json_encode($result));
        
        if ($result['success']) {
            $success_message = $result['message'];
            // Nach dem Speichern die Daten neu laden
            if (isset($_POST['benutzer_id'])) {
                $reload = $controller->loadUser((int)$_POST['benutzer_id']);
                if ($reload['success']) {
                    $userData = $reload['data'];
                }
            }
        } else {
            $error = $result['message'];
        }
    }
    
    // Klassen immer laden (für das Dropdown-Menü)
    $klassen = $controller->getAllClasses();
    
} catch (\Exception $e) {
    $error = "Fehler: " . $e->getMessage();
}

// ========== VIEW LADEN ==========
require_once __DIR__ . '/views/benutzer_edit.php';

    echo "test.php: included view\n";
    echo "</pre>";