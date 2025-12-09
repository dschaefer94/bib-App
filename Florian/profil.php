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
// Lade alle benötigten Bibliotheken, Models und Controller-Klassen.
// Die Reihenfolge ist wichtig, da die Controller voneinander abhängen.

// Library und Models laden
require_once __DIR__ . '/Library/Msg.php';
require_once __DIR__ . '/Model/Database.php';
require_once __DIR__ . '/Model/Florian_BenutzerModel.php';
require_once __DIR__ . '/Model/Florian_KlassenModel.php';
require_once __DIR__ . '/Model/Florian_Persoenliche_datenModel.php';

// Controller laden (Reihenfolge ist wichtig wegen Abhängigkeiten in Konstruktoren)
require_once __DIR__ . '/Controller/Florian_KlassenController.php';
require_once __DIR__ . '/Controller/Florian_Persoenliche_datenController.php';
require_once __DIR__ . '/Controller/Florian_BenutzerController.php';

use ppb\Controller\Florian_BenutzerController;
use ppb\Controller\Florian_KlassenController;

// -------------------- CONTROLLER INSTANZIIEREN --------------------
// Erstellt Instanzen der Haupt-Controller, die für die Verarbeitung der Logik benötigt werden.
$benutzerController = new Florian_BenutzerController();
$klassenController = new Florian_KlassenController();

// -------------------- VARIABLEN FÜR DIE VIEW INITIALISIEREN --------------------
// Definiert die Variablen, die an die View übergeben werden, um sicherzustellen,
// dass sie immer existieren und Fehler vermieden werden.
$userData = []; // Enthält die Daten des geladenen Benutzers.
$klassen = []; // Enthält die Liste aller Klassen für das Dropdown-Menü.
$success_message = ''; // Erfolgsmeldung nach dem Speichern.
$error = ''; // Fehlermeldung, die in der View angezeigt wird.

// -------------------- HAUPTLOGIK (Request-Verarbeitung) --------------------
try {
    // Fall 1: GET-Request mit einer 'benutzer_id'.
    // Dient zum Laden und Anzeigen eines vorhandenen Benutzers.
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['benutzer_id'])) {
        $benutzer_id = (int)$_GET['benutzer_id'];
        
        $result = $benutzerController->loadUser($benutzer_id);
        
        if ($result['success']) {
            $userData = $result['data'];
        } else {
            $error = $result['error'];
        }
    }
    
    // Fall 2: POST-Request, ausgelöst durch den "Speichern"-Button.
    // Dient zum Aktualisieren der Daten eines Benutzers.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
        $result = $benutzerController->saveUser($_POST);
        
        // Optional: Loggt das Ergebnis des Speichervorgangs für Debugging-Zwecke.
        error_log("saveUser result: " . json_encode($result));
        
        if ($result['success']) {
            $success_message = $result['message'];
            // Lädt die Benutzerdaten nach dem Speichern neu, um die Änderungen
            // direkt im Formular anzuzeigen.
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
    
    // Unabhängig vom Request-Typ werden immer alle Klassen geladen,
    // damit das Dropdown-Menü im Formular gefüllt werden kann.
    $klassen = $klassenController->getAllClasses();
    
} catch (\Exception $e) {
    // Fängt unerwartete Fehler ab und zeigt eine generische Fehlermeldung an.
    $error = "Ein unerwarteter Fehler ist aufgetreten: " . $e->getMessage();
}

// -------------------- HTML-AUSGABE (Integrierte View) --------------------
// Der PHP-Block wird hier beendet, um direkt HTML auszugeben.
// Alle oben definierten Variablen sind hier verfügbar.
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Benutzer bearbeiten</title>
    <link rel="stylesheet" href="CSS/layout.css">
    <script src="JS/main.js"></script>
</head>
<body>
    <h3>Persönliche Daten</h3>
    
    <!-- SUCHFORMULAR: Eingabeformular für die Benutzer-ID -->
    <div class="search-container">
        <form method="GET" action="profil.php">
            <label for="benutzer_id">Benutzer ID:</label>
            <!-- Input-Feld für die Benutzer-ID mit gespeichertem Wert -->
            <input type="number" id="benutzer_id" name="benutzer_id" min="1" 
                   value="<?php echo htmlspecialchars($_GET['benutzer_id'] ?? ''); ?>" 
                   placeholder="z.B. 1">
            <!-- Button zum Absenden der Suchanfrage -->
            <button type="submit">Suchen</button>
        </form>
    </div>

    <!-- FEHLERBEHANDLUNG: Zeige Fehlermeldung wenn kein Datensatz gefunden -->
    <?php if (!empty($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <!-- ERFOLGREICHE ABFRAGE: Zeige das Bearbeitungsformular wenn Datensätze gefunden wurden -->
    <?php elseif (!empty($userData)): ?>
        <!-- ERFOLGSMELDUNG: Zeige die Meldung wenn Daten gespeichert wurden -->
        <?php if ($success_message): ?>
            <p class="success"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>
        
        <!-- BEARBEITUNGSFORMULAR: Formular zum Bearbeiten und Speichern der Daten -->
        <form method="POST" action="profil.php" class="edit-form">
            <!-- Verstecktes Feld mit der Benutzer-ID (wird beim Speichern übertragen) -->
            <input type="hidden" name="benutzer_id" value="<?php echo htmlspecialchars($userData['benutzer_id']); ?>">
            
            <!-- NAME-FELD: Editierbares Textfeld für den Namen -->
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" 
                       value="<?php echo htmlspecialchars($userData['name']); ?>" required>
            </div>
            
            <!-- VORNAME-FELD: Editierbares Textfeld für den Vornamen -->
            <div class="form-group">
                <label for="vorname">Vorname:</label>
                <input type="text" id="vorname" name="vorname" 
                       value="<?php echo htmlspecialchars($userData['vorname']); ?>" required>
            </div>
            
            <!-- KLASSENNAME-FELD: Dropdown-Menü mit allen verfügbaren Klassen -->
            <div class="form-group">
                <label for="klassen_id">Klassenname:</label>
                <select id="klassen_id" name="klassen_id">
                    <!-- Option für "Keine Klasse" -->
                    <option value="">-- Keine Klasse zugewiesen --</option>
                    <!-- Alle Klassen aus der Datenbank als Optionen ausgeben -->
                    <?php foreach ($klassen as $klasse): ?>
                        <!-- Option mit klassen_id als Wert und klassenname als Anzeigetext -->
                        <option value="<?php echo htmlspecialchars($klasse['klassen_id']); ?>" 
                            <?php echo ($userData['klassen_id'] == $klasse['klassen_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($klasse['klassenname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- EMAIL-FELD: Editierbares Textfeld für die E-Mail-Adresse -->
            <div class="form-group">
                <label for="email">E-Mail:</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>">
                <small class="hint">E-Mail optional — muss mit <code>@bib.de</code> enden, falls angegeben.</small>
            </div>
            
            <!-- PASSWORT-FELD: Editierbares Textfeld für das Passwort -->
            <div class="form-group">
                <label for="passwort">Passwort (leer lassen zum Nicht ändern):</label>
                <input type="password" id="passwort" name="passwort" value="" 
                       placeholder="Neues Passwort eingeben">
            </div>
            
            <!-- SPEICHERN-BUTTON: Button zum Absenden der Änderungen -->
            <button type="submit" name="save" value="1" class="save-button">Speichern</button>
        </form>
    <?php endif; ?>
</body>
</html>