// Basis-URL für API
const url = "./restAPI.php";

// POST: neuen Benutzer anlegen
async function createUser(payload) {
  const res = await fetch(url + "/user", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload)
  });
  return res.json();
}

// Formular-Submit
document.querySelector("#userForm").addEventListener("submit", async (e) => {
  e.preventDefault();

  // Felder auslesen
  const email = document.querySelector("#email").value;
  const password = document.querySelector("#password").value;
  const passwordConfirm = document.querySelector("#passwordConfirm").value; // NEU
  const name = document.querySelector("#name").value;
  const vorname = document.querySelector("#vorname").value;
  const klassenname = document.querySelector("#klasseSelect").value;

  const registrierungsFeedback = document.querySelector("#registrierungsFeedback");

  // --- VALIDIERUNG START ---
  
  // 1. Check: E-Mail Endung @bib.de
  if (!email.toLowerCase().endsWith("@bib.de")) {
    registrierungsFeedback.textContent = "Fehler: Nur E-Mail-Adressen mit @bib.de sind erlaubt.";
    registrierungsFeedback.style.color = "red";
    registrierungsFeedback.style.display = "block";
    return; // Stoppt die Funktion hier
  }

  // 2. Check: Passwörter identisch
  if (password !== passwordConfirm) {
    registrierungsFeedback.textContent = "Fehler: Die Passwörter stimmen nicht überein.";
    registrierungsFeedback.style.color = "red";
    registrierungsFeedback.style.display = "block";
    return; // Stoppt die Funktion hier
  }

  // --- VALIDIERUNG ENDE ---

  const payload = { email, passwort: password, name, vorname, klassenname };

  try {
    const response = await createUser(payload);
    const { benutzerAngelegt, grund } = response;

    if (!benutzerAngelegt) { 
      registrierungsFeedback.textContent = grund;
      registrierungsFeedback.style.color = "red";
      registrierungsFeedback.style.display = "block";
    } else {
      registrierungsFeedback.textContent = "Benutzer erfolgreich angelegt!";
      registrierungsFeedback.style.color = "green";
      registrierungsFeedback.style.display = "block";
      e.target.reset(); // Formular leeren
    }
  } catch (err) {
    console.error("Fehler beim Benutzer anlegen:", err);
    registrierungsFeedback.textContent = "Fehler: " + err.message;
    registrierungsFeedback.style.color = "red";
    registrierungsFeedback.style.display = "block";
  }
});

// GET: Klassenliste holen
async function getKlassen() {
  const res = await fetch(url + "/class");
  return res.json();
}

// Dropdown befüllen
async function fillKlasseSelect() {
  const select = document.querySelector("#klasseSelect");
  try {
    let rows = await getKlassen();

    if (!Array.isArray(rows)) {
      rows = Object.values(rows || {});
    }

    select.innerHTML = '<option value="">Bitte wählen…</option>';

    rows.forEach(k => {
      const opt = document.createElement("option");
      opt.textContent = k.klassenname;
      select.appendChild(opt);
    });
  } catch (err) {
    console.error("Fehler beim Laden der Klassen:", err);
    select.innerHTML = '<option value="">Fehler beim Laden der Klassen</option>';
  }
}

// Beim Laden ausführen
document.addEventListener("DOMContentLoaded", fillKlasseSelect);