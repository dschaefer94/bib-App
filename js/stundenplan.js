const url = "http://localhost/bibapp_xampp/restAPI.php/";

// Annahme: Benutzerdaten sind im sessionStorage gespeichert nach dem Login
// const benutzer_id = sessionStorage.getItem('benutzer_id');
// const klasse = sessionStorage.getItem('klasse');
// const vorname = sessionStorage.getItem('vorname');
// const nachname = sessionStorage.getItem('nachname');

const benutzer_id = "1";
const klasse = "pbd2h24a";
const vorname = "Lydia";
const nachname = "Schäfer";

const benutzername = vorname + " " + nachname;

if (vorname || nachname) {
  document.getElementById('name').innerText = benutzername;
}
if (klasse) {
  document.getElementById('klasse').innerText = klasse;
}

// GET: Stundenplan holen
async function getStundenplan(benutzer_id) {
  const res = await fetch(url + "stundenplan/" + benutzer_id); // TimetableController -> getTimetableByClassName(klassenname)
  return res.json(); // erwartet ein Array mit stundenplan_id, klassenname, ical_link
}
