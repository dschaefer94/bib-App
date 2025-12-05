<?php
/**
 * View: Benutzer bearbeiten
 * Zeigt das Formular zur Bearbeitung von Benutzerdaten an
 * 
 * Verfügbare Variablen (aus Controller):
 * - $userData: array mit Benutzerdaten (benutzer_id, name, vorname, klassen_id, klassenname, email, passwort)
 * - $klassen: array mit allen Klassen (klassen_id, klassenname)
 * - $success_message: string (optional)
 * - $error: string (optional)
 */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Benutzer bearbeiten</title>
    <link rel="stylesheet" href="layout.css">
    <script src="main.js"></script>
</head>
<body>
    <h3>EZ Datenbankverbindung</h3>
    
    <!-- SUCHFORMULAR: Eingabeformular für die Benutzer-ID -->
    <div class="search-container">
        <form method="GET" action="/SDP/bib-App/test.php">
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
        <form method="POST" action="/SDP/bib-App/test.php" class="edit-form">
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
