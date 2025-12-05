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
require_once __DIR__ . '/Controller/BenutzerController.php';

use ppb\Controller\BenutzerController;

// ========== CONTROLLER INSTANZIIEREN ==========
$controller = new BenutzerController();

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
            <p class="success"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>
        
        <!-- BEARBEITUNGSFORMULAR: Formular zum Bearbeiten und Speichern der Daten -->
        <form method="POST" action="" class="edit-form">
            <!-- Verstecktes Feld mit der Benutzer-ID (wird beim Speichern übertragen) -->
            <input type="hidden" name="benutzer_id" value="<?php echo htmlspecialchars($rows[0]['benutzer_id']); ?>">
            
            <!-- NAME-FELD: Editierbares Textfeld für den Namen -->
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($rows[0]['name']); ?>" required>
            </div>
            
            <!-- VORNAME-FELD: Editierbares Textfeld für den Vornamen -->
            <div class="form-group">
                <label for="vorname">Vorname:</label>
                <input type="text" id="vorname" name="vorname" value="<?php echo htmlspecialchars($rows[0]['vorname']); ?>" required>
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
                            <?php echo ($rows[0]['klassen_id'] == $klasse['klassen_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($klasse['klassenname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- EMAIL-FELD: Editierbares Textfeld für die E-Mail-Adresse -->
            <div class="form-group">
                <label for="email">E-Mail:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($rows[0]['email'] ?? ''); ?>" required>
            </div>
            
            <!-- PASSWORT-FELD: Editierbares Textfeld für das Passwort -->
            <div class="form-group">
                <label for="passwort">Passwort (leer lassen zum Nicht ändern):</label>
                <input type="password" id="passwort" name="passwort" value="" placeholder="Neues Passwort eingeben">
            </div>
            
            <!-- SPEICHERN-BUTTON: Button zum Absenden der Änderungen -->
            <button type="submit" name="save" value="1" class="save-button">Speichern</button>
        </form>
    <?php endif; ?>
</body>

</html>