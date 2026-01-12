const API = 'https://dein-host/restapi.php';

async function login(email, password) {
  const res = await fetch(`${API}/auth`, {
    method: 'POST',
    credentials: 'include', // Session-Cookie senden!
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  });
  return res.json();
}

async function me() {
  const res = await fetch(`${API}/auth/me`, {
    method: 'GET',
    credentials: 'include'
  });
  // 200 -> JSON, 401 -> Fehler
  return res.json().catch(async () => ({ error: await res.text() }));
}

async function logout() {
  const res = await fetch(`${API}/auth/logout`, {
    method: 'POST',
    credentials: 'include'
  });
  return res.json();
}

// Beispiel:
document.addEventListener('DOMContentLoaded', async () => {
  console.log(await login('daniel@example.com', '1234'));
  console.log(await me());
  console.log(await logout());
});

