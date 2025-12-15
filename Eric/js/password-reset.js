document.addEventListener('DOMContentLoaded', () => {
    const forgotForm = document.getElementById('forgotForm');
    const resetForm = document.getElementById('resetForm');
    const messageEl = document.getElementById('message');

    if (forgotForm) {
        forgotForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value.trim();
            if (!email) {
                if (messageEl) { messageEl.style.color = 'red'; messageEl.textContent = 'E-Mail fehlt'; }
                return;
            }

            const url = 'restapi.php/user/requestPasswordReset?email=' + encodeURIComponent(email);

            try {
                const res = await fetch(url, { method: 'GET' });
                const json = await res.json();
                if (messageEl) {
                    messageEl.style.color = json.success ? 'green' : 'red';
                    messageEl.textContent = json.success || json.error || 'Keine Antwort';
                    if (json.debug_link) {
                        const debug = document.createElement('div');
                        debug.style.marginTop = '8px';
                        debug.innerHTML = 'Debug-Link zum Testen: <a href="' + json.debug_link + '">' + json.debug_link + '</a>';
                        messageEl.parentNode.appendChild(debug);
                    }
                } else {
                    alert(json.success || json.error || 'Keine Antwort');
                }
        } catch (err) {
            if (messageEl) { messageEl.style.color = 'red'; messageEl.textContent = 'Fehler beim Senden'; }
            else alert('Fehler beim Senden');
            console.error(err);
        }
    });
}

const params = new URLSearchParams(window.location.search);
const token = params.get('token');

if (token && resetForm) {
        resetForm.style.display = 'block';

        resetForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const password = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;

            if (password !== confirm) {
                if (messageEl) { messageEl.style.color = 'red'; messageEl.textContent = 'Passwörter stimmen nicht überein'; }
                else alert('Passwörter stimmen nicht überein');
                return;
            }

            const url = 'restapi.php/user/resetPassword?token=' + encodeURIComponent(token) + '&password=' + encodeURIComponent(password);

            try {
                const res = await fetch(url, { method: 'GET' });
                const json = await res.json();
                if (messageEl) {
                    messageEl.style.color = json.success ? 'green' : 'red';
                    messageEl.textContent = json.success || json.error || 'Keine Antwort';
                    if (json.success) setTimeout(() => window.location.href = 'index.html', 1800);
                } else {
                    alert(json.success || json.error || 'Keine Antwort');
                    if (json.success) setTimeout(() => window.location.href = 'index.html', 1800);
                }
            } catch (err) {
                if (messageEl) { messageEl.style.color = 'red'; messageEl.textContent = 'Fehler beim Zurücksetzen'; }
                else alert('Fehler beim Zurücksetzen');
                console.error(err);
            }
        });
document.addEventListener('DOMContentLoaded', () => {
    const forgotForm = document.getElementById('forgotForm');
    const resetForm = document.getElementById('resetForm');
    const messageEl = document.getElementById('message');

    function showMessage(text, color = 'black') {
        if (!messageEl) {
            alert(text);
            return;
        }
        messageEl.style.color = color;
        messageEl.textContent = text;
    }

    if (forgotForm) {
        forgotForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const emailInput = document.getElementById('email');
            const email = emailInput ? emailInput.value.trim() : '';

            if (!email) {
                showMessage('Bitte E-Mail eingeben', 'red');
                return;
            }

            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!re.test(email)) {
                showMessage('Ungültige E‑Mail', 'red');
                return;
            }

            showMessage('Sende Link an ' + email + '...', 'black');
            setTimeout(() => {
                showMessage('E-Mail gesendet. Bitte prüfen Sie Ihr Postfach.', 'green');
                forgotForm.reset();
            }, 1000);


        });
    }

    const params = new URLSearchParams(window.location.search);
    const token = params.get('token');

    if (token && resetForm) {
        resetForm.style.display = 'block';

        resetForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const password = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;

            if (!password || !confirm) {
                showMessage('Bitte beide Felder ausfüllen', 'red');
                return;
            }
            if (password !== confirm) {
                showMessage('Passwörter stimmen nicht überein', 'red');
                return;
            }
            if (password.length < 8) {
                showMessage('Passwort zu kurz (mind. 8 Zeichen)', 'red');
                return;
            }

            // Simuliere Passwort-Änderung (kein DB)
            showMessage('Setze Passwort ...', 'black');
            setTimeout(() => {
                showMessage('Passwort wurde geändert. Du kannst dich jetzt anmelden.', 'green');
                resetForm.reset();
            }, 1000);

        });
    }
});
    }
});