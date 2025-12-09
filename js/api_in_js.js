// hier die URL fürs lokale Testen ändern
const url = "http://localhost/bibapp_xampp/restAPI.php/";
async function getProjectData(url) {
  // Default options are marked with *
  const response = await fetch(url, {
    method: "GET", // *GET, POST, PUT, DELETE, etc.
  });
  return response.json(); // parses JSON response into native JavaScript objects
}
let linkElement = document.querySelector("#showUser");
linkElement.addEventListener("click", e => {
  e.preventDefault();
  getProjectData(url + "user").then((data) => {
    console.log(data); // JSON data parsed by `data.json()` call
    let main = document.querySelector("main");
    console.log(main);
    let c = "<ul>";
    data.forEach(d => {
      //hier unten wird ein index mithilfe des Keys des assoziativen Arrays ausgegeben
      c += "<li>" + d.benutzer_id + ", " + d.email + "</li>";
    });

    c += "</ul>";

    main.innerHTML = c;
  });
});



// POST: neuen Benutzer anlegen
async function createUser(payload) {
  const res = await fetch(url + "user", {
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

  const created = await createUser(payload);

  // Meldungs-Div auswählen
  const registrierungsFeedback = document.querySelector("#registrierungsFeedback");

  if (!created.benutzer_id) { // null oder falsy
    registrierungsFeedback.textContent = "Den Benutzer gibt es bereits.";
    registrierungsFeedback.style.color = "red";
  } else {
    registrierungsFeedback.textContent = "Benutzer erfolgreich angelegt!";
    registrierungsFeedback.style.color = "green";
  }
  // Formular zurücksetzen
  e.target.reset();
});




// GET: Klassenliste holen
async function getKlassen() {
  const res = await fetch(url + "class"); // ClassController -> getClass()
  return res.json(); // erwartet ein Array
}

// Dropdown befüllen (nur Name)
async function fillKlasseSelect() {
  const select = document.querySelector("#klasseSelect");
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
}

// Beim Laden ausführen
document.addEventListener("DOMContentLoaded", fillKlasseSelect);










