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
document.getElementById('forgotForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value;
    
    const response = await fetch('restapi.php/user/requestPasswordReset', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
    });

    const result = await response.json();
    alert(result.success || result.error);
});

const params = new URLSearchParams(window.location.search);
const token = params.get('token');

if (token) {
    document.getElementById('forgotForm').style.display = 'none';
    document.getElementById('resetForm').style.display = 'block';

    document.getElementById('resetForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const password = document.getElementById('newPassword').value;

        const response = await fetch('restapi.php/user/resetPassword', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token, password })
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
    }
});