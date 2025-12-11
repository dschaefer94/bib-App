<?php
/**
 * Florian
 * profil.php - Haupt-Einstiegspunkt für Benutzerverwaltung
 *
 * Dieses Skript dient als Haupteinstiegspunkt für die Web-Anwendung.
 * Es übernimmt folgende Aufgaben:
 * 1. Laden aller notwendigen Klassen (Models, Controller).
 * 2. Instanziieren der benötigten Controller.
 * 3. Verarbeiten von GET- und POST-Requests zum Laden und Speichern von Benutzern.
 * 4. Bereitstellen der Daten für die View.
 * 5. Direkte Ausgabe des HTML-Codes (integrierte View).
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

// -------------------- CONTROLLER INSTANZIIEREN --------------------
$benutzerController = new Florian_BenutzerController();
$klassenController = new Florian_KlassenController();

// -------------------- VARIABLEN FÜR DIE VIEW INITIALISIEREN --------------------
$userData = [];
$klassen = [];
$success_message = '';
$error = '';

// -------------------- HAUPTLOGIK (Request-Verarbeitung) --------------------
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['benutzer_id'])) {
        $benutzer_id = (int)$_GET['benutzer_id'];
        $result = $benutzerController->loadUser($benutzer_id);
        if ($result['success']) {
            $userData = $result['data'];
        } else {
            $error = $result['error'];
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
        $result = $benutzerController->saveUser($_POST);
        error_log("saveUser result: " . json_encode($result));
        if ($result['success']) {
            $success_message = $result['message'];
            if (isset($_POST['benutzer_id'])) {
                $reload = $benutzerController->loadUser((int)$_POST['benutzer_id']);
                if ($reload['success']) {
                    $userData = $reload['data'];
                }
            }
        } else {
            $error = $result['error'];
        }
    }

    $klassen = $klassenController->getAllClasses();
} catch (\Exception $e) {
    $error = "Ein unerwarteter Fehler ist aufgetreten: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Profil bearbeiten</title>
    <link rel="stylesheet" href="CSS/layout.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="login-page">
    <main class="login-form">
        <div class="actions" style="margin-bottom: 20px;">
            <a href="startseite.html" class="btn">Zurück zur Startseite</a>
        </div>
        <div class="card">
            <h1>Profil suchen</h1>
            
            <form method="GET" action="profil.php">
                <div class="input-group">
                    <label for="benutzer_id">Benutzer ID:</label>
                    <input type="number" id="benutzer_id" name="benutzer_id" min="1" 
                           value="<?php echo htmlspecialchars($_GET['benutzer_id'] ?? ''); ?>" 
                           placeholder="z.B. 1" required>
                </div>
                <div class="actions">
                    <button type="submit" class="btn">Suchen</button>
                </div>
            </form>

            <?php if (!empty($error)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <?php if (!empty($userData)): ?>
                <hr>
                <h1>Profil bearbeiten</h1>
                <?php if ($success_message): ?>
                    <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
                <?php endif; ?>
                
                <form method="POST" action="profil.php">
                    <input type="hidden" name="benutzer_id" value="<?php echo htmlspecialchars($userData['benutzer_id']); ?>">
                    
                    <div class="input-group">
                        <label for="name">Name:</label>
                        <input type="text" id="name" name="name" 
                               value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                    </div>
                    
                    <div class="input-group">
                        <label for="vorname">Vorname:</label>
                        <input type="text" id="vorname" name="vorname" 
                               value="<?php echo htmlspecialchars($userData['vorname']); ?>" required>
                    </div>
                    
                    <div class="input-group">
                        <label for="klassen_id">Klasse:</label>
                        <select id="klassen_id" name="klassen_id">
                            <option value="">-- Keine Klasse --</option>
                            <?php foreach ($klassen as $klasse): ?>
                                <option value="<?php echo htmlspecialchars($klasse['klassen_id']); ?>" 
                                    <?php echo ($userData['klassen_id'] == $klasse['klassen_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($klasse['klassenname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="input-group">
                        <label for="email">E-Mail:</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="input-group">
                        <label for="passwort">Neues Passwort:</label>
                        <input type="password" id="passwort" name="passwort" 
                               placeholder="Leer lassen, um nicht zu ändern">
                    </div>
                    
                    <div class="actions">
                        <button type="submit" name="save" value="1" class="btn">Speichern</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>
    <script src="JS/main.js"></script>
</body>
</html>