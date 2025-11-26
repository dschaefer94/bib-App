
<?php
header("Content-Type: application/json; charset=UTF-8");
require 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT * FROM deine_tabelle LIMIT 10");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
