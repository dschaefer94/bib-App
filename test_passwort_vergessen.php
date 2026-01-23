<?php
/**
 * Eric Kleim
 * Test-Datei für Passwort Vergessen Funktion
 * 
 * Diese Datei ermöglicht es, die Passwort-Vergessen-Funktion zu testen
 * ohne dass man ein echtes Frontend durchlaufen musst.
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test - Passwort Vergessen</title>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .test-section {
            margin: 30px 0;
            padding: 20px;
            background: #f9f9f9;
            border-left: 4px solid #007bff;
            border-radius: 5px;
        }
        .test-section h2 {
            margin-top: 0;
            color: #007bff;
        }
        .test-input {
            display: flex;
            gap: 10px;
            margin: 10px 0;
        }
        input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover { background: #0056b3; }
        .response {
            margin-top: 15px;
            padding: 15px;
            background: #e8f4f8;
            border: 1px solid #b3d9e8;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>

<div class="test-container">
    <h1>🧪 Test - Passwort Vergessen Funktion</h1>
    
    <div class="test-section">
        <h2>1️⃣ Test: Email-Verifizierung (GET)</h2>
        <p>Prüft, ob eine Email in der Datenbank existiert</p>
        <div class="test-input">
            <input type="email" id="testEmail" placeholder="test@example.com" value="">
        </div>
        <button onclick="testEmailVerification()">Email prüfen</button>
        <div id="response1" class="response" style="display: none;"></div>
    </div>

    <div class="test-section">
        <h2>2️⃣ Test: Passwort-Reset (POST)</h2>
        <p>Ändert das Passwort für einen Benutzer</p>
        <div class="test-input">
            <input type="email" id="testResetEmail" placeholder="test@example.com" value="">
        </div>
        <div class="test-input">
            <input type="password" id="testNewPassword" placeholder="Neues Passwort" value="testpass123">
        </div>
        <button onclick="testPasswordReset()">Passwort ändern</button>
        <div id="response2" class="response" style="display: none;"></div>
    </div>

    <div class="test-section">
        <h2>💡 Test-Tipps</h2>
        <ul>
            <li>Für Test 1: Eine Email eingeben, die in der Datenbank existiert</li>
            <li>Für Test 2: Same Email + neues Passwort testen</li>
            <li>Browser-Konsole öffnen (F12) für zusätzliche Logs</li>
            <li>Die Response zeigt, ob die Operation erfolgreich war</li>
        </ul>
    </div>

    <div class="test-section" style="border-left-color: #6c757d;">
        <h2>📊 Debugging-Infos</h2>
        <p><strong>Datenbankverbindung:</strong> localhost</p>
        <p><strong>Datenbank:</strong> stundenplan_db</p>
        <p><strong>Tabelle:</strong> benutzer</p>
        <p><strong>Spalten:</strong> benutzer_id, email, passwort</p>
        <p><span class="info">💡 Tipp: Überprüfe, ob deine Mitschüler-Emails in der Datenbank existieren!</span></p>
    </div>
</div>

<script>
    function testEmailVerification() {
        const email = document.getElementById('testEmail').value.trim();
        const responseDiv = document.getElementById('response1');

        if (!email) {
            responseDiv.innerHTML = '<span class="error">❌ Bitte geben Sie eine Email ein!</span>';
            responseDiv.style.display = 'block';
            return;
        }

        // Relative URL (im selben Verzeichnis)
        fetch('passwortVergessen.php?email=' + encodeURIComponent(email))
            .then(response => response.json())
            .then(data => {
                console.log('Response:', data);
                const html = data.success 
                    ? `<span class="success">✅ Email gefunden!</span><br><br>Benutzer-ID: ${data.benutzer_id}`
                    : `<span class="error">❌ Fehler: ${data.error}</span>`;
                responseDiv.innerHTML = html;
                responseDiv.style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                responseDiv.innerHTML = `<span class="error">❌ Fehler bei der Anfrage: ${error.message}</span>`;
                responseDiv.style.display = 'block';
            });
    }

    function testPasswordReset() {
        const email = document.getElementById('testResetEmail').value.trim();
        const password = document.getElementById('testNewPassword').value;
        const responseDiv = document.getElementById('response2');

        if (!email || !password) {
            responseDiv.innerHTML = '<span class="error">❌ Bitte füllen Sie alle Felder aus!</span>';
            responseDiv.style.display = 'block';
            return;
        }

        if (password.length < 6) {
            responseDiv.innerHTML = '<span class="error">❌ Passwort muss mindestens 6 Zeichen lang sein!</span>';
            responseDiv.style.display = 'block';
            return;
        }

        const formData = new FormData();
        formData.append('email', email);
        formData.append('passwort', password);
        formData.append('action', 'reset');

        fetch('passwortVergessen.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Response:', data);
            const html = data.success 
                ? `<span class="success">✅ ${data.message}</span>`
                : `<span class="error">❌ Fehler: ${data.error}</span>`;
            responseDiv.innerHTML = html;
            responseDiv.style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            responseDiv.innerHTML = `<span class="error">❌ Fehler bei der Anfrage: ${error.message}</span>`;
            responseDiv.style.display = 'block';
        });
    }

    // Auto-focus auf erstes Input beim Laden
    window.addEventListener('load', () => {
        document.getElementById('testEmail').focus();
    });
</script>

</body>
</html>
