// Basis-URL für API
const url = "./restapi.php";

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

  const email = document.querySelector("#email").value;
  const password = document.querySelector("#password").value;
  const name = document.querySelector("#name").value;
  const vorname = document.querySelector("#vorname").value;
  const klassenname = document.querySelector("#klasseSelect").value;

  const payload = { email, passwort: password, name, vorname, klassenname };
  const registrierungsFeedback = document.querySelector("#registrierungsFeedback");

  try {
    //erwartete Rückgabe: JSON mit benutzer_ID und email
    const response = await createUser(payload);
    const [{ benutzerAngelegt, grund }] = response;

    if (!benutzerAngelegt) { 
      registrierungsFeedback.textContent = grund;
      registrierungsFeedback.style.color = "red";
      registrierungsFeedback.style.display = "block";
    } else {
      registrierungsFeedback.textContent = "Benutzer erfolgreich angelegt!";
      registrierungsFeedback.style.color = "green";
      registrierungsFeedback.style.display = "block";
      e.target.reset();
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
  const res = await fetch(url + "/class"); // ClassController -> getClass()
  return res.json(); // erwartet ein Array mit klassen_id, klassenname, ical_link (Json-Link(null)),
  //muss noch alles außer klassenname herausschmeißen
}

// Dropdown befüllen (nur Name)
async function fillKlasseSelect() {
  const select = document.querySelector("#klasseSelect");
  try {
    let rows = await getKlassen();

    // Minimal robust: in Array verwandeln, falls nötig
    if (!Array.isArray(rows)) {
      rows = Object.values(rows || {});
    }

    // Platzhalter als echte Option
    select.innerHTML = '<option value="">Bitte wählen…</option>';

    rows.forEach(k => {
      const opt = document.createElement("option");
      opt.textContent = k.klassenname; // Nur Name anzeigen
      select.appendChild(opt);
    });
  } catch (err) {
    console.error("Fehler beim Laden der Klassen:", err);
    select.innerHTML = '<option value="">Fehler beim Laden der Klassen</option>';
  }
}

// Beim Laden ausführen
document.addEventListener("DOMContentLoaded", fillKlasseSelect);










