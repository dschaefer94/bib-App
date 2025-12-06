<?php
    define('API', 'restAPI.php'); // NICHT VERAENDERN!!!
    $url = "http://localhost/bibapp_xampp/" . API;
    $filepath = "c:\\xampp\\htdocs\\bibapp_xampp\\";

        $defaults = array(
        CURLOPT_URL => $url . '/benutzer',
        // CURLOPT_COOKIEFILE => $filepath . 'cookie.txt', // set cookie file to given file
        // CURLOPT_COOKIEJAR => $filepath . 'cookie.txt', // set same file as cookie jar
        CURLOPT_CUSTOMREQUEST => "GET"
    );
    $ch = curl_init();
    curl_setopt_array($ch, ($defaults));
    curl_exec($ch);
    if(curl_error($ch)) {
        print(curl_error($ch));
    }
    curl_close($ch);

    // session_start();

?>
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