<?php
/**
 * Eric Kleim
 * Passwort Vergessen - Workaround Lösung
 * 
 * GET: Prüft, ob eine Email in der Datenbank existiert
 * POST: Aktualisiert das Passwort für einen Benutzer
 */

// Abhängigkeiten laden
require_once __DIR__ . '/Model/Database.php';
require_once __DIR__ . '/Library/Msg.php';

use ppb\Model\Database;

header('Content-Type: application/json');

$response = [
    'success' => false,
    'error' => null,
    'message' => null
];

try {
    // Datenbankverbindung herstellen
    $db = new class extends Database {};
    $pdo = $db->linkDB();

    // GET-Request: Email-Verifizierung
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $email = isset($_GET['email']) ? trim($_GET['email']) : null;

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['error'] = 'Ungültige E-Mail-Adresse.';
            echo json_encode($response);
            exit;
        }

        // Überprüfe, ob die Email in der Datenbank existiert
        $stmt = $pdo->prepare("SELECT benutzer_id FROM benutzer WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $response['success'] = true;
            $response['benutzer_id'] = $user['benutzer_id'];
        } else {
            $response['error'] = 'Diese E-Mail-Adresse existiert nicht in unserem System.';
        }
    }

    // POST-Request: Passwort aktualisieren
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = isset($_POST['email']) ? trim($_POST['email']) : null;
        $passwort = isset($_POST['passwort']) ? $_POST['passwort'] : null;

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['error'] = 'Ungültige E-Mail-Adresse.';
            echo json_encode($response);
            exit;
        }

        if (!$passwort || strlen($passwort) < 6) {
            $response['error'] = 'Das Passwort muss mindestens 6 Zeichen lang sein.';
            echo json_encode($response);
            exit;
        }

        // Passwort hashen
        $hashedPassword = password_hash($passwort, PASSWORD_DEFAULT);

        // Passwort in der Datenbank aktualisieren
        $stmt = $pdo->prepare("UPDATE benutzer SET passwort = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Passwort erfolgreich geändert! Sie werden weitergeleitet...';
        } else {
            $response['error'] = 'Fehler beim Ändern des Passworts. Bitte versuchen Sie es erneut.';
        }
    } else {
        http_response_code(405);
        $response['error'] = 'Ungültige Anfragemethode.';
    }

} catch (PDOException $e) {
    $response['error'] = 'Datenbankfehler: ' . $e->getMessage();
    error_log("DB Error in passwortVergessen.php: " . $e->getMessage());
} catch (Exception $e) {
    $response['error'] = 'Ein Fehler ist aufgetreten.';
    error_log("Error in passwortVergessen.php: " . $e->getMessage());
}

echo json_encode($response);
exit;
?>
