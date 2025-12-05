<?php
try {
    // REST-API Aufruf statt direkter DB-Zugriff
    $apiUrl = "http://localhost/bibapp/restapi.php/benutzer";
    
    $response = file_get_contents($apiUrl);
    $rows = json_decode($response, true);
    
    if ($rows === null) {
        throw new Exception("Ungültige JSON-Response von der API");
    }
    
    // Falls die API einen Error-Response zurückgibt
    if (isset($rows['error'])) {
        throw new Exception($rows['error']);
    }
} catch (Exception $e) {
    $error = "Schade, Noob: " . $e->getMessage();
    $rows = [];
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Datenbank-Test</title>
    <link rel="stylesheet" href="CSS/layout.css">
    <script src="js/main.js"></script>
</head>

<body>
    <h3>EZ Datenbankverbindung</h3>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php else: ?>
        <table id="daten-tabelle">
            <thead>
                <tr>
                    <?php foreach (array_keys($rows[0] ?? []) as $col): ?>
                        <th><?php echo htmlspecialchars($col); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($row as $value): ?>
                            <td><?php echo htmlspecialchars($value); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <video id="success-video"
            width="640"
            height="360"
            controls
            preload="metadata"
            poster="poster.jpg"
            playsinline
            style="display:none;"
            aria-label="one">
            <source src="videos/one.mp4" type="video/mp4">
        </video>
    <?php endif; ?>
</body>

</html>