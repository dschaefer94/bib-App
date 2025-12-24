
// === Konfiguration ===
const EVENTS_URL = "http://localhost/bibapp_xampp/Kalender/Kalenderdateien/pbd2h24a/stundenplan.json"; // <- Pfad zu deiner JSON-Quelle anpassen


// === State ===
let allEvents = []; // flache Liste aller Events
let dayOffset = 0;  // 0 = heute, >0 = x Tage in der Zukunft, <0 = x Tage in der Vergangenheit

// === Utils ===

/** Parst 'YYYYMMDDTHHMMSS?' als lokale Zeit (z. B. 20251124T080000). */
function parseCompactLocal(ts) {
  const m = ts.match(/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})?$/);
  if (!m) return null;
  const [_, y, mo, d, h, mi, s] = m;
  return new Date(
    Number(y),
    Number(mo) - 1,
    Number(d),
    Number(h),
    Number(mi),
    s ? Number(s) : 0
  );
}

/** Normalisiert ein Date auf Mitternacht (lokal). */
function atStartOfDay(d) {
  const x = new Date(d);
  x.setHours(0, 0, 0, 0);
  return x;
}

/** Formatiert Zeit wie 08:00–09:30. */
function fmtTimeRange(start, end) {
  const opts = { hour: '2-digit', minute: '2-digit' };
  const s = start.toLocaleTimeString(undefined, opts);
  const e = end.toLocaleTimeString(undefined, opts);
  return `${s}–${e}`;
}

/** Deutsches Datum: Di, 24.12.2025 */
function fmtDayLabel(d) {
  return d.toLocaleDateString('de-DE', {
    weekday: 'short',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
}

/** Filtert Events für das Datum (lokal) und sortiert nach Startzeit. */
function eventsForDay(date) {
  const dayStart = atStartOfDay(date).getTime();
  const dayEnd = dayStart + 24 * 60 * 60 * 1000;
  return allEvents
    .filter(ev => {
      const s = ev.start.getTime();
      return s >= dayStart && s < dayEnd;
    })
    .sort((a, b) => a.start - b.start);
}

// === DOM Referenzen ===
const listEl = document.getElementById('list');
const statusEl = document.getElementById('status');
const dayLabelEl = document.getElementById('dayLabel');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const todayBtn = document.getElementById('todayBtn');

// === Rendering ===
function render() {
  const base = new Date();            // heute (lokal)
  const target = new Date(base);
  target.setDate(base.getDate() + dayOffset);

  // Label dynamisch
  let label;
  if (dayOffset === 0) {
    label = '(heute)';
  } else if (dayOffset === 1) {
    label = `(morgen, ${fmtDayLabel(target)})`;
  } else if (dayOffset === -1) {
    label = `(gestern, ${fmtDayLabel(target)})`;
  } else {
    label = `(${fmtDayLabel(target)})`;
  }
  dayLabelEl.textContent = ` ${label}`;

  // Liste aufbauen
  const todays = eventsForDay(target);
  listEl.innerHTML = '';

  if (todays.length === 0) {
    listEl.hidden = false;
    statusEl.textContent = 'Keine Termine für diesen Tag.';
    return;
  }

  statusEl.textContent = '';
  listEl.hidden = false;

  for (const ev of todays) {
    const article = document.createElement('article');

    const title = document.createElement('div');
    title.textContent = ev.summary || '(Ohne Titel)';
    article.appendChild(title);

    const meta = document.createElement('div');
    const time = fmtTimeRange(ev.start, ev.end);
    const loc = ev.location ? ` • ${ev.location}` : '';
    meta.textContent = `${time}${loc}`;
    article.appendChild(meta);

    // einfache Trennlinie
    const hr = document.createElement('hr');
    article.appendChild(hr);

    listEl.appendChild(article);
  }
}

// === Interaktionen ===
prevBtn.addEventListener('click', () => {
  dayOffset -= 1; // einen Tag zurück
  render();
});

nextBtn.addEventListener('click', () => {
  dayOffset += 1; // einen Tag weiter
  render();
});

todayBtn.addEventListener('click', () => {
  dayOffset = 0; // zurück auf heute
  render();
});

// Bonus: Tastatursteuerung mit Pfeiltasten/0
document.addEventListener('keydown', (e) => {
  if (e.key === 'ArrowLeft') {
    dayOffset -= 1;
    render();
  } else if (e.key === 'ArrowRight') {
    dayOffset += 1;
    render();
  } else if (e.key === '0') {
    dayOffset = 0;
    render();
  }
});

// === Daten laden ===
async function loadEvents() {
  try {
    const res = await fetch(EVENTS_URL, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const raw = await res.json();
    //##################################################################################
    //hier kommt noch die andere JSON rein und die Termine werden aufeinander aufgewogen
    //zuerst gelesen.json von aenderungen.json subtrahieren und dann auf stundenplan.json ersetzen/ergänzen
    //##################################################################################
    const items = [];
    for (const [id, v] of Object.entries(raw)) {
      const start = parseCompactLocal(v.start);
      const end = parseCompactLocal(v.end);
      if (!start || !end) continue;
      items.push({
        id,
        summary: v.summary || '',
        start,
        end,
        location: v.location || ''
      });
    }

    allEvents = items;
    statusEl.textContent = '';
    render();
  } catch (err) {
    console.error(err);
    statusEl.textContent = 'Fehler beim Laden der Termine.';
  }
}

loadEvents();

// Optional: nach Mitternacht automatisch aktualisieren,
// wenn "heute" aktiv ist, damit die Ansicht korrekt bleibt.
setInterval(() => {
  if (dayOffset === 0) render();
}, 60_000);
