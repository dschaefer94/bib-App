<?php
require 'Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $db = new Database();
    $pdo = $db->linkDB();

    $stmt = $pdo->prepare("SELECT * FROM bib_users_test WHERE username = ?");
    $stmt->execute([$username]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $password === $user['password_hash']) {
        echo "✅ Login erfolgreich! User ID: " . $user['user_id'];
    } else {
        echo "❌ Login fehlgeschlagen";
    }
}
?>
<!-- Im Frontend nur HTML & JS -->
 <!-- Fetch aufruf gegen PHP -->

<form method="POST">
    <input type="text" name="username" placeholder="Username"><br><br>
    <input type="password" name="password" placeholder="Password"><br><br>
    <button type="submit">Login</button>
</form>
