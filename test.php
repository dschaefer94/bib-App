<?php
require 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT * FROM Benutzer LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
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