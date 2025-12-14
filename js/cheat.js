//Florians Code
// hier die URL fürs lokale Testen ändern
//Daniel: 
const url = "http://localhost/bibapp_xampp/restAPI.php/";
//const url = "http://localhost/SDP/bib-App/restAPI.php/";


// GET: Klassenliste holen
async function getKlassen() {
  const res = await fetch(url + "class"); // ClassController -> getClass()
  return res.json(); // erwartet ein Array mit klassen_id, klassenname, ical_link (Json-Link(null)),
  //muss noch alles außer klassenname herausschmeißen
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

document.getElementById('cheatbutton').addEventListener('click', function(event) {
    event.preventDefault(); 
    window.location.href = 'startseite.php';
});

