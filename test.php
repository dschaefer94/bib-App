<?php
// Datenbankverbindungs-Konfiguration laden
require 'Database.php';

// Benutzer_id aus der URL-Parameter auslesen (GET-Anfrage) oder null setzen
$benutzer_id = $_GET['benutzer_id'] ?? null;
// Array für die abgerufenen Datenbankdaten initialisieren
$rows = [];
// Array für alle verfügbaren Klassen initialisieren
$klassen = [];
// Erfolgsmeldung definieren
$success_message = '';

// Try-Block: Alle Operationen, die Fehler verursachen könnten
try {
    // Verbindung zur MySQL-Datenbank mit PDO erstellen
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // PDO konfigurieren, um Exceptions bei Fehlern zu werfen
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SPEICHERN: Wenn das Formular per POST abgesendet wurde und der Save-Button geklickt wurde
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
        // POST-Daten auslesen (mit Standardwert null/leer falls nicht vorhanden)
        $post_benutzer_id = $_POST['benutzer_id'] ?? null;
        $name = $_POST['name'] ?? '';
        $vorname = $_POST['vorname'] ?? '';
        $klassen_id = $_POST['klassen_id'] ?? null;
        $email = $_POST['email'] ?? '';
        $passwort = $_POST['passwort'] ?? '';

        // EMAIL-VALIDIERUNG: Überprüfung ob Email mit @bib.de endet
        if (!str_ends_with($email, '@bib.de')) {
            // Falls Email nicht mit @bib.de endet: Fehlermeldung setzen und nicht speichern
            $error = "Fehler: Die E-Mail-Adresse muss mit @bib.de enden!";
        }
        // FELDVALIDIERUNG: Alle erforderlichen Felder müssen gefüllt sein
        elseif ($post_benutzer_id && $name && $vorname && $email) {
            // UPDATE PERSOENLICHE_DATEN: Name, Vorname und Klasse aktualisieren
            $update_stmt = $pdo->prepare("UPDATE PERSOENLICHE_DATEN SET name = ?, vorname = ?, klassen_id = ? WHERE benutzer_id = ?");
            // Parameterbindung und Ausführung: ? werden durch die Werte ersetzt (sichere Abfrage)
            $update_stmt->execute([$name, $vorname, $klassen_id ?: null, $post_benutzer_id]);
            
            // UPDATE BENUTZER-TABELLE: Email und optional Passwort aktualisieren
            if ($passwort) {
                // FALL 1: Wenn Passwort geändert werden soll - Email UND Passwort aktualisieren
                $benutzer_update = $pdo->prepare("UPDATE BENUTZER SET email = ?, passwort = ? WHERE benutzer_id = ?");
                $benutzer_update->execute([$email, $passwort, $post_benutzer_id]);
            } else {
                // FALL 2: Wenn Passwort leer ist - nur Email aktualisieren
                $benutzer_update = $pdo->prepare("UPDATE BENUTZER SET email = ? WHERE benutzer_id = ?");
                $benutzer_update->execute([$email, $post_benutzer_id]);
            }
            
            // Erfolgsmeldung setzen wenn alles gespeichert wurde
            $success_message = "Daten erfolgreich aktualisiert!";
            // Benutzer_id setzen für die nachfolgende Abfrage
            $benutzer_id = $post_benutzer_id;
        }
    }

    // ABFRAGEN: Wenn eine Benutzer_id vorhanden ist
    if ($benutzer_id) {
        // SELECT-Statement mit JOIN vorbereiten: Persönliche Daten + Klassendaten + Benutzer-Daten kombinieren
        $stmt = $pdo->prepare("SELECT pd.benutzer_id, pd.name, pd.vorname, pd.klassen_id, k.klassenname, b.email, b.passwort
                               FROM PERSOENLICHE_DATEN pd 
                               LEFT JOIN KLASSEN k ON pd.klassen_id = k.klassen_id
                               LEFT JOIN BENUTZER b ON pd.benutzer_id = b.benutzer_id
                               WHERE pd.Benutzer_id = ?");
        // SQL-Statement ausführen mit der Benutzer_id
        $stmt->execute([$benutzer_id]);
        // Alle Ergebnisse als assoziatives Array abrufen
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Wenn keine Datensätze gefunden wurden: Fehlermeldung setzen
        if (empty($rows)) {
            $error = "Kein Datensatz mit Benutzer_id: " . htmlspecialchars($benutzer_id) . " gefunden.";
        }
    } else {
        // Wenn keine Benutzer_id eingegeben wurde: Hinweismeldung setzen
        $error = "Bitte geben Sie eine Benutzer_id an (z.B. ?benutzer_id=1)";
    }
    
    // KLASSEN LADEN: Alle verfügbaren Klassen aus der Datenbank abfragen
    $klassen_stmt = $pdo->query("SELECT klassen_id, klassenname FROM KLASSEN ORDER BY klassenname");
    $klassen = $klassen_stmt->fetchAll(PDO::FETCH_ASSOC);
// FEHLERBEHANDLUNG: Wenn ein Datenbankfehler auftritt
} catch (PDOException $e) {
    // Fehlermeldung mit Exception-Details setzen
    $error = "Schade, Noob: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Datenbank-Test</title>
    <link rel="stylesheet" href="layout.css">
    <script src="main.js"></script>
</head>

<body>
    <h3>EZ Datenbankverbindung</h3>
    
    <!-- SUCHFORMULAR: Eingabeformular für die Benutzer-ID -->
    <div class="search-container">
        <form method="GET" action="">
            <label for="benutzer_id">Benutzer ID:</label>
            <!-- Input-Feld für die Benutzer-ID mit gespeichertem Wert -->
            <input type="number" id="benutzer_id" name="benutzer_id" min="1" value="<?php echo htmlspecialchars($_GET['benutzer_id'] ?? ''); ?>" placeholder="z.B. 1">
            <!-- Button zum Absenden der Suchanfrage -->
            <button type="submit">Suchen</button>
        </form>
    </div>

    <!-- FEHLERBEHANDLUNG: Zeige Fehlermeldung wenn kein Datensatz gefunden -->
    <?php if (isset($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <!-- ERFOLGREICHE ABFRAGE: Zeige das Bearbeitungsformular wenn Datensätze gefunden wurden -->
    <?php elseif (!empty($rows)): ?>
        <!-- ERFOLGSMELDUNG: Zeige die Meldung wenn Daten gespeichert wurden -->
        <?php if ($success_message): ?>
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