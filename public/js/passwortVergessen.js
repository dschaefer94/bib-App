// Passwort Vergessen - Workaround Lösung
document.addEventListener('DOMContentLoaded', function() {
    const emailForm = document.getElementById('emailForm');
    const resetForm = document.getElementById('resetForm');
    const emailStep = document.getElementById('emailStep');
    const passwordStep = document.getElementById('passwordStep');
    const backBtn = document.getElementById('backBtn');
    
    const emailInput = document.getElementById('email');
    const emailError = document.getElementById('emailError');
    const emailSuccess = document.getElementById('emailSuccess');
    const resetError = document.getElementById('resetError');
    const resetSuccess = document.getElementById('resetSuccess');

    // Hilfsfunktion: Nachricht anzeigen
    function showMessage(element, message, isError = true) {
        element.textContent = message;
        element.style.display = 'block';
        if (!isError) {
            setTimeout(() => element.style.display = 'none', 3000);
        }
    }

    // Hilfsfunktion: Nachricht verstecken
    function hideMessage(element) {
        element.style.display = 'none';
    }

    // Schritt 1: Email-Verifizierung
    emailForm.addEventListener('submit', function(e) {
        e.preventDefault();
        hideMessage(emailError);
        hideMessage(emailSuccess);

        const email = emailInput.value.trim();

        if (!email) {
            showMessage(emailError, 'Bitte geben Sie eine E-Mail-Adresse ein.', true);
            return;
        }

        // GET-Request zur Überprüfung der Email in der Datenbank
        fetch(`passwortVergessen.php?email=${encodeURIComponent(email)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Email gefunden - Passwort-Formular anzeigen
                    showMessage(emailSuccess, 'E-Mail verifiziert! Geben Sie Ihr neues Passwort ein.', false);
                    document.getElementById('resetEmail').value = email;
                    
                    setTimeout(() => {
                        emailStep.style.display = 'none';
                        passwordStep.style.display = 'block';
                    }, 1500);
                } else {
                    showMessage(emailError, data.error || 'E-Mail nicht gefunden.', true);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage(emailError, 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.', true);
            });
    });

    // Schritt 2: Passwort zurücksetzen
    resetForm.addEventListener('submit', function(e) {
        e.preventDefault();
        hideMessage(resetError);
        hideMessage(resetSuccess);

        const email = document.getElementById('resetEmail').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        // Validierung
        if (newPassword !== confirmPassword) {
            showMessage(resetError, 'Die Passwörter stimmen nicht überein.', true);
            return;
        }

        if (newPassword.length < 6) {
            showMessage(resetError, 'Das Passwort muss mindestens 6 Zeichen lang sein.', true);
            return;
        }

        // POST-Request zum Aktualisieren des Passworts
        const formData = new FormData();
        formData.append('email', email);
        formData.append('passwort', newPassword);
        formData.append('action', 'reset');

        fetch('passwortVergessen.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(resetSuccess, data.message || 'Passwort erfolgreich geändert!', false);
                setTimeout(() => {
                    window.location.href = 'index.html';
                }, 2000);
            } else {
                showMessage(resetError, data.error || 'Fehler beim Ändern des Passworts.', true);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage(resetError, 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.', true);
        });
    });

    // Zurück-Button
    backBtn.addEventListener('click', function() {
        emailStep.style.display = 'block';
        passwordStep.style.display = 'none';
        emailForm.reset();
        resetForm.reset();
        hideMessage(emailError);
        hideMessage(emailSuccess);
        hideMessage(resetError);
        hideMessage(resetSuccess);
    });
});
