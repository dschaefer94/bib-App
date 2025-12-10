
<?php
// Datenbank-Daten, damit diese nicht hardgecodet im index.php rumliegt.
$host = "localhost";
$dbname = "stundenplan_db";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Verbindung zur Datenbank erfolgreich!";
} catch (PDOException $e) {
    die("Fehler bei der Verbindung: " . $e->getMessage());
}
?>