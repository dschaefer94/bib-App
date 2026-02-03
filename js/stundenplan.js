
// ======================
// CONFIG
// ======================
const CONFIG = {
  BASE: window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + '/restAPI.php',
  ENDPOINT_NEU: '/Calendar/getCalendar',
  ENDPOINT_AENDERUNGEN: '/Calendar/getChanges',
  // NEU:
  ENDPOINT_GELESEN: '/Calendar/getNotedChanges',      // GET
  ENDPOINT_GELESEN_WRITE: '/Calendar/writeNotedChanges', // POST
  USE_QUERY_RANGE: false,
  DEFAULT_CLASS_NAME: 'PBD2H24A',
  WEEK_STARTS_MONDAY: true,
};

const TIME_SLOTS = [
  { start: "08:00", end: "09:30" },
  { start: "09:50", end: "11:20" },
  { start: "11:30", end: "13:00" },
  { start: "13:45", end: "15:15" },
  { start: "15:30", end: "17:00" }
];

// ======================
// Klassennamen beziehen
// ======================
function getClassName() {
  const url = new URL(window.location.href);
  const klassFromUrl = url.searchParams.get('klasse');
  if (klassFromUrl) {
    return klassFromUrl;
  }

  // Versuche aus sessionStorage zu bekommen
  const storedUser = sessionStorage.getItem('user');
  if (storedUser) {
    try {
      const user = JSON.parse(storedUser);
      if (user.klassenname) {
        return user.klassenname;
      }
    } catch (e) {
      console.error('Failed to parse user from sessionStorage:', e);
    }
  }

  // Fallback auf Default
  return CONFIG.DEFAULT_CLASS_NAME;
}

function buildUrl(path, { start = null, end = null } = {}) {
  const klasse = getClassName();
  const url = new URL(CONFIG.BASE + path);
  url.searchParams.set('klasse', klasse);
  if (CONFIG.USE_QUERY_RANGE && start && end) {
    url.searchParams.set('start', start.toISOString().slice(0, 10));
    url.searchParams.set('end', end.toISOString().slice(0, 10));
  }
  return url.toString();
}

// ======================
// State
// ======================
let RAW_NEU = [];
let RAW_AEND = [];
let READ_IDS = new Set(); // Set<string>
let READS_LOADED = false;

let weekStart = startOfWeek(new Date());

// ======================
// Utils: Datum/Zeit
// ======================
function startOfWeek(date) {
  const d = new Date(date);
  d.setHours(0, 0, 0, 0);
  const day = d.getDay(); // 0=So,1=Mo,...
  const diff = CONFIG.WEEK_STARTS_MONDAY ? ((day + 6) % 7) : day;
  d.setDate(d.getDate() - diff);
  return d;
}

function endOfWeek(date) {
  const s = startOfWeek(date);
  const e = new Date(s);
  e.setDate(e.getDate() + 7);
  return e;
}

function fmtTime(dt) {
  return new Intl.DateTimeFormat('de-DE', { hour: '2-digit', minute: '2-digit' }).format(dt);
}

function fmtDate(dt, withWeekday = false) {
  const opts = withWeekday
    ? { weekday: 'long', day: '2-digit', month: '2-digit' }
    : { day: '2-digit', month: '2-digit' };
  return new Intl.DateTimeFormat('de-DE', opts).format(dt);
}

function isoWeekNumber(date) {
  const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
  const dayNum = d.getUTCDay() || 7;
  d.setUTCDate(d.getUTCDate() + 4 - dayNum);
  const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
  const weekNo = Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
  return { week: weekNo, year: d.getUTCFullYear() };
}

function parseISO(s) { return new Date(s); }

function overlaps(aStart, aEnd, bStart, bEnd) {
  return aEnd > bStart && aStart < bEnd;
}

function dayIndex(date, weekStartsMonday) {
  const js = date.getDay();
  return weekStartsMonday ? ((js + 6) % 7) : js;
}

function escapeHtml(s) {
  return String(s ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

// ======================
// Daten holen
// ======================
async function fetchDataForRange(s, e) {
  const neuUrl = buildUrl(CONFIG.ENDPOINT_NEU, { start: s, end: e });
  const aendUrl = buildUrl(CONFIG.ENDPOINT_AENDERUNGEN, { start: s, end: e });
  const [neuRes, aendRes] = await Promise.all([fetch(neuUrl), fetch(aendUrl)]);
  if (!neuRes.ok) throw new Error('Fehler beim Laden von stundenplan_neu: ' + neuRes.status);
  if (!aendRes.ok) throw new Error('Fehler beim Laden von aenderungen: ' + aendRes.status);
  try {
    const [neu, aend] = await Promise.all([neuRes.json(), aendRes.json()]);
    return { neu, aend };
  } catch (e) {
    console.error('JSON parse error from getCalendar/getChanges:', e);
    throw new Error('JSON-Parse Fehler beim Laden der Termine: ' + e.message);
  }
}

async function ensureDataLoaded() {
  if (RAW_NEU.length && RAW_AEND.length) return;
  const s = startOfWeek(new Date());
  const e = endOfWeek(s);
  const { neu, aend } = await fetchDataForRange(s, e);
  RAW_NEU = Array.isArray(neu) ? neu : (neu?.data ?? []);
  RAW_AEND = Array.isArray(aend) ? aend : (aend?.data ?? []);
}

// ======================
// Reads (gelesene Änderungen)
// ======================
async function fetchReadIds() {
  const url = buildUrl(CONFIG.ENDPOINT_GELESEN);
  try {
    const res = await fetch(url);
    if (!res.ok) throw new Error('Fehler beim Laden der Gelesen-Liste: ' + res.status);
    const json = await res.json();
    const arr = Array.isArray(json) ? json : (json?.data ?? []);
    READ_IDS = new Set(
      arr.map(x => String(x?.termin_id ?? x)).map(s => s.trim()).filter(Boolean)
    );
    READS_LOADED = true;
  } catch (e) {
    console.error('JSON parse error from getNotedChanges:', e);
    console.error('URL was:', url);
    throw new Error('JSON-Parse Fehler beim Laden der Gelesen-Liste: ' + e.message);
  }
}

async function ensureReadsLoaded() {
  if (READS_LOADED) return;
  await fetchReadIds();
}

async function markAsRead(terminId) {
  const url = CONFIG.BASE + CONFIG.ENDPOINT_GELESEN_WRITE;
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' }, // oder x-www-form-urlencoded (siehe Kommentar)
    body: JSON.stringify({ termin_id: String(terminId) })
    // Alternative für PHP-Form:
    // headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    // body: new URLSearchParams({ termin_id: String(terminId) })
  });
  if (!res.ok) throw new Error('Konnte Termin nicht als gelesen markieren');
  READ_IDS.add(String(terminId));   // lokal aktualisieren
  await render();                    // neu zeichnen
}

// ======================
// Transformation / Week-Build (Raster bleibt wochenbasiert)
// ======================
function buildWeekEvents(s, e) {
  // Nur UNGELESENE Änderungen berücksichtigen
  const changesById = new Map();
  for (const ch of RAW_AEND) {
    if (!READ_IDS.has(String(ch.termin_id))) {
      changesById.set(String(ch.termin_id), ch);
    }
  }

  const normalizeEvent = (obj, source = 'neu', change = null) => {
    const isAlt = source === 'alt';
    const start = parseISO(isAlt ? obj.start_alt : obj.start);
    const end = parseISO(isAlt ? obj.end_alt : obj.end);
    return {
      id: String(obj.termin_id),
      summary: isAlt ? obj.summary_alt : obj.summary,
      location: isAlt ? obj.location_alt : obj.location,
      start, end,
      raw: obj,
      status: change?.label ?? null,
      change,
    };
  };

  // Basis-Events (immer aus GET /Calendar/getCalendar)
  const baseEvents = RAW_NEU
    .map(ev => normalizeEvent(ev, 'neu', changesById.get(String(ev.termin_id))))
    .filter(ev => overlaps(ev.start, ev.end, s, e));

  // Gelöschte Events nur aus Aenderungen und nur wenn UNGELESEN
  const deletedEvents = RAW_AEND
    .filter(ch => ch.label === 'gelöscht' && !READ_IDS.has(String(ch.termin_id)))
    .map(ch => normalizeEvent(ch, 'alt', ch))
    .filter(ev => overlaps(ev.start, ev.end, s, e));

  const all = [...baseEvents, ...deletedEvents];

  // Sortierung (Start -> End -> Titel)
  all.sort((a, b) =>
    (a.start - b.start) || (a.end - b.end) || a.summary.localeCompare(b.summary)
  );

  // In Tage einsortieren
  const byDay = Array.from({ length: 7 }, () => []);
  for (const ev of all) {
    const idx = dayIndex(ev.start, CONFIG.WEEK_STARTS_MONDAY);
    byDay[idx].push(ev);
  }

  // Diffs berechnen für "geändert"
  for (const day of byDay) {
    for (const ev of day) {
      if (ev.status === 'geändert' && ev.change) {
        const old = {
          summary: ev.change.summary_alt,
          start: parseISO(ev.change.start_alt),
          end: parseISO(ev.change.end_alt),
          location: ev.change.location_alt,
        };
        ev.diff = {
          summaryChanged: old.summary !== ev.summary,
          timeChanged: (old.start.getTime() !== ev.start.getTime()) || (old.end.getTime() !== ev.end.getTime()),
          locationChanged: old.location !== ev.location,
          old,
        };
      }
    }
  }

  return byDay;
}

// ======================
// Global: alle UNGELESENEN Änderungen (ohne Wochenfilter)
// ======================
function getGlobalUnreadChanges() {
  const jetzt = new Date(); // Aktueller Zeitpunkt für den Vergleich

  // Map der aktuellen (neuen) Events, um für "neu"/"geändert" die aktuelle Seite zeigen zu können
  const neuById = new Map(RAW_NEU.map(ev => [String(ev.termin_id), ev]));

  // NUR ungelesene Changes, die noch nicht in der Vergangenheit liegen
  const unread = RAW_AEND.filter(ch => {
    const istUngelesen = !READ_IDS.has(String(ch.termin_id));

    // Wir prüfen das Ende des Termins. 
    // Falls 'end' (neu) nicht da ist (z.B. bei gelöscht), nehmen wir 'end_alt'.
    const terminEnde = new Date(ch.end || ch.end_alt);
    const istZukuenftig = terminEnde >= jetzt;

    return istUngelesen && istZukuenftig;
  });

  // Normalisieren: für Anzeige brauchen wir je nach label unterschiedliche Felder
  return unread.map(ch => {
    const id = String(ch.termin_id);
    const label = ch.label; // 'neu' | 'geändert' | 'gelöscht'

    const neu = neuById.get(id);

    const view = {
      id,
      status: label,
      new: {
        summary: neu?.summary ?? ch.summary,
        start: neu ? new Date(neu.start) : (ch.start ? new Date(ch.start) : null),
        end: neu ? new Date(neu.end) : (ch.end ? new Date(ch.end) : null),
        location: neu?.location ?? ch.location,
      },
      old: {
        summary: ch.summary_alt ?? null,
        start: ch.start_alt ? new Date(ch.start_alt) : null,
        end: ch.end_alt ? new Date(ch.end_alt) : null,
        location: ch.location_alt ?? null,
      }
    };

    if (label === 'gelöscht') {
      view.display = {
        date: view.old.start ?? view.new.start,
        start: view.old.start ?? view.new.start,
        end: view.old.end ?? view.new.end,
        title: view.old.summary ?? view.new.summary ?? '(ohne Titel)',
        loc: view.old.location ?? view.new.location ?? '',
      };
    } else {
      view.display = {
        date: view.new.start ?? view.old.start,
        start: view.new.start ?? view.old.start,
        end: view.new.end ?? view.old.end,
        title: view.new.summary ?? view.old.summary ?? '(ohne Titel)',
        loc: view.new.location ?? view.old.location ?? '',
      };
    }

    if (label === 'geändert') {
      view.diff = {
        summaryChanged: (view.old.summary ?? '') !== (view.new.summary ?? ''),
        timeChanged: !!(view.old.start && view.new.start && view.old.start.getTime() !== view.new.start.getTime())
          || !!(view.old.end && view.new.end && view.old.end.getTime() !== view.new.end.getTime()),
        locationChanged: (view.old.location ?? '') !== (view.new.location ?? '')
      };
    }

    return view;
  });
}

// ======================
// Übersichtsliste (global, ohne Wochenfilter)
// ======================
function ensureChangesListHost() {
  let host = document.getElementById('changesList');
  if (!host) {
    const grid = document.getElementById('grid');
    host = document.createElement('section');
    host.id = 'changesList';
    host.className = 'changes-list';
    grid.insertAdjacentElement('afterend', host);
  }
  return host;
}

function injectChangesListStyles() {
  if (document.getElementById('changesListStyle')) return;
  const css = `
  .changes-list{margin-top:24px;background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:12px;}
  .changes-list h2{margin:0 0 10px 0;font-size:16px;}
  .changes-list .item{display:grid;grid-template-columns:1fr auto;gap:8px 12px;padding:10px 0;border-top:1px solid var(--line);}
  .changes-list .item:first-child{border-top:none;}
  .changes-list .meta{color:var(--muted);font-size:13px;}
  .label-chip{display:inline-block;border:1px solid var(--line);border-radius:999px;padding:2px 8px;font-size:12px;color:var(--muted);background:#0f131a;margin-left:8px;}
  .label-chip.neu{border-color:var(--green-bd);color:var(--green-bd);}
  .label-chip.geaendert{border-color:var(--yellow-bd);color:var(--yellow-bd);}
  .label-chip.geloescht{border-color:var(--red-bd);color:var(--red-bd);}
  .mark-read{justify-self:end;align-self:start;padding:6px 10px;font-size:13px;border-radius:8px;border:1px solid var(--line);background:#0f131a;color:var(--text);cursor:pointer;}
  .mark-read:hover{background:#0c1017;}
  .event .actions{margin-top:8px;display:flex;gap:8px;}
  .event .actions .mark-read{padding:6px 10px;font-size:12px;}
  `;
  const style = document.createElement('style');
  style.id = 'changesListStyle';
  style.textContent = css;
  document.head.appendChild(style);
}

function renderChangesListGlobal() {
  injectChangesListStyles();
  const host = ensureChangesListHost();
  host.innerHTML = '';

  const h = document.createElement('h2');
  h.textContent = 'Geänderte Termine (alle ungelesen)';
  host.appendChild(h);

  const changes = getGlobalUnreadChanges();

  if (!changes.length) {
    const empty = document.createElement('div');
    empty.className = 'empty';
    empty.textContent = 'Keine ungelesenen Änderungen';
    host.appendChild(empty);
    return;
  }

  // Optional: Sortierung nach Datum (alt → neu)
  changes.sort((a, b) => {
    const da = a.display.date ? a.display.date.getTime() : 0;
    const db = b.display.date ? b.display.date.getTime() : 0;
    return da - db || String(a.display.title).localeCompare(String(b.display.title));
  });

  for (const c of changes) {
    const item = document.createElement('div');
    item.className = 'item';

    const left = document.createElement('div');
    const title = document.createElement('div');
    title.className = 'summary';
    title.textContent = c.display.title || '(ohne Titel)';

    const chip = document.createElement('span');
    chip.className = `label-chip ${c.status === 'geändert' ? 'geaendert' : (c.status === 'gelöscht' ? 'geloescht' : 'neu')}`;
    chip.textContent = c.status;
    title.appendChild(chip);

    const meta = document.createElement('div');
    meta.className = 'meta';
    const d = c.display.date ? fmtDate(c.display.date, true) : '';
    const timeRange = (c.display.start && c.display.end)
      ? `${fmtTime(c.display.start)}–${fmtTime(c.display.end)}`
      : '';
    meta.textContent = `${d}${timeRange ? ' · ' + timeRange : ''}${c.display.loc ? ' · ' + c.display.loc : ''}`;

    // Diff-Zeilen für 'geändert'
    if (c.status === 'geändert' && c.diff) {
      if (c.diff.summaryChanged) {
        const line = document.createElement('div');
        line.className = 'meta';
        line.innerHTML = `<del>${escapeHtml(c.old.summary || '(ohne Titel)')}</del> → ${escapeHtml(c.new.summary || '(ohne Titel)')}`;
        meta.appendChild(document.createElement('br'));
        meta.appendChild(line);
      }
      if (c.diff.timeChanged && c.old.start && c.old.end && c.new.start && c.new.end) {
        const line = document.createElement('div');
        line.className = 'meta';
        line.innerHTML = `<del>${fmtTime(c.old.start)}–${fmtTime(c.old.end)}</del> → ${fmtTime(c.new.start)}–${fmtTime(c.new.end)}`;
        meta.appendChild(document.createElement('br'));
        meta.appendChild(line);
      }
      if (c.diff.locationChanged) {
        const line = document.createElement('div');
        line.className = 'meta';
        line.innerHTML = `<del>${escapeHtml(c.old.location || '')}</del> → ${escapeHtml(c.new.location || '')}`;
        meta.appendChild(document.createElement('br'));
        meta.appendChild(line);
      }
    }

    left.append(title, meta);

    const right = document.createElement('div');
    const btn = document.createElement('button');
    btn.className = 'mark-read';
    btn.textContent = 'Als gelesen markieren';
    btn.addEventListener('click', async () => {
      btn.disabled = true;
      try { await markAsRead(c.id); }
      catch (err) { console.error(err); btn.disabled = false; }
    });
    right.appendChild(btn);

    item.append(left, right);
    host.appendChild(item);
  }
}

// ======================
// Render
// ======================
function renderWeekHeader(s, e) {
  const titleEl = document.getElementById('weekTitle');
  const rangeEl = document.getElementById('weekRange');
  const { week, year } = isoWeekNumber(s);
  const rangeTxt = `${fmtDate(s)} – ${fmtDate(new Date(e.getTime() - 1))} ${year}`;
  titleEl.textContent = `KW ${week} · ${year}`;
  rangeEl.textContent = rangeTxt;
}

// ======================
// Neuer Render-Controller für feste Slots
// ======================

function getSlotIndex(date) {
  const timeStr = fmtTime(date); // HH:MM
  const [h, m] = timeStr.split(':').map(Number);
  const totalMinutes = h * 60 + m;

  // Wir ordnen die Termine basierend auf der Startzeit den Slots zu
  if (totalMinutes < 590) return 0; // 08:00 (bis 09:50 Puffer)
  if (totalMinutes < 690) return 1; // 09:50
  if (totalMinutes < 820) return 2; // 11:30
  if (totalMinutes < 930) return 3; // 13:45
  return 4; // 15:30
}

function renderGrid(byDay, s) {
  const grid = document.getElementById('grid');
  if (!grid) return;
  grid.innerHTML = '';
  
  // Grid-Container Styling
  Object.assign(grid.style, {
    display: 'flex',
    gap: '12px',
    overflowX: 'auto',
    alignItems: 'flex-start',
    padding: '0',
    width: '100%'
  });

  // --- ZEITSPALTE (Ganz links) ---
  const timeCol = document.createElement('div');
  Object.assign(timeCol.style, {
    position: 'sticky',
    left: '0',
    zIndex: '20',
    background: 'var(--bg)',
    flex: '0 0 50px',
    marginTop: '45px', 
    borderRight: '1px solid var(--line)',
    paddingRight: '8px'
  });
  
  const timeGrid = document.createElement('div');
  Object.assign(timeGrid.style, {
    display: 'grid',
    gridTemplateRows: 'repeat(5, 140px)',
    gap: '10px',
    padding: '12px 0'
  });

  TIME_SLOTS.forEach(slot => {
    const label = document.createElement('div');
    label.style.textAlign = 'right';
    label.innerHTML = `
      <div style="font-size: 12px; font-weight: 700; color: var(--text);">${slot.start}</div>
      <div style="font-size: 10px; color: var(--muted);">${slot.end}</div>
    `;
    timeGrid.appendChild(label);
  });
  timeCol.appendChild(timeGrid);
  grid.appendChild(timeCol);

  // --- TAGE ---
  for (let i = 0; i < 5; i++) { 
    const dayDate = new Date(s); 
    dayDate.setDate(dayDate.getDate() + i);
    
    const col = document.createElement('div');
    col.className = 'day';
    Object.assign(col.style, {
      flex: '0 0 280px',
      minWidth: '280px',
      background: 'var(--panel)',
      borderRadius: '12px',
      border: '1px solid var(--line)',
      display: 'flex',
      flexDirection: 'column'
    });

    const head = document.createElement('header');
    head.style.padding = '10px';
    head.style.borderBottom = '1px solid var(--line)';
    head.innerHTML = `
      <div style="font-weight:700; font-size:14px;">${new Intl.DateTimeFormat('de-DE', { weekday: 'long' }).format(dayDate)}</div>
      <div style="color:var(--muted); font-size:12px;">${fmtDate(dayDate)}</div>
    `;

    const list = document.createElement('div');
    list.className = 'events';
    Object.assign(list.style, {
      display: 'grid',
      gridTemplateRows: 'repeat(5, 140px)', // Definiert die 5 Zeitfenster
      gap: '10px',
      padding: '12px',
      position: 'relative',
      minHeight: 'auto' // WICHTIG: Kein extra Platz nach unten
    });

    // WICHTIG: HIER WURDEN DIE LEEREN BACKGROUND-BLÖCKE ENTFERNT

    const events = byDay[i];
    if (events) {
      for (const ev of events) {
        const startIdx = getSlotIndex(ev.start);
        const endIdx = getSlotIndex(ev.end);
        const el = renderEvent(ev);
        
        // Das Event wird exakt in die Reihe des Zeitlots gesetzt
        const span = (endIdx > startIdx) ? (endIdx - startIdx + 1) : 1;
        el.style.gridRow = `${startIdx + 1} / span ${span}`;
        el.style.zIndex = "2";
        el.style.margin = "0"; 
        list.appendChild(el);
      }
    }

    col.append(head, list);
    grid.appendChild(col);
  }
}

// Deine renderEvent Funktion bleibt fast gleich, wir optimieren nur das CSS etwas
function renderEvent(ev) {
  const el = document.createElement('article');
  el.className = 'event';

  // Verhindert, dass die Event-Boxen bei wenig Inhalt schrumpfen
  el.style.height = '100%';
  el.style.margin = '0';

  const normalized = String(ev.summary || "").trim();
  if (normalized.startsWith("*")) el.classList.add("special");
  if (ev.status === 'neu') el.classList.add('neu');
  if (ev.status === 'gelöscht') el.classList.add('geloescht');
  if (ev.status === 'geändert') el.classList.add('geaendert');

  const contentWrap = document.createElement('div');
  contentWrap.className = 'content';

  contentWrap.innerHTML = `
    <div class="summary">${ev.summary || '(ohne Titel)'}</div>
    <div class="meta">${fmtTime(ev.start)} – ${fmtTime(ev.end)}</div>
    <div class="location">${ev.location || ''}</div>
  `;

  // Diff-Logik für Änderungen
  if (ev.status === 'geändert' && ev.diff) {
    const changes = document.createElement('div');
    changes.className = 'changes';
    if (ev.diff.summaryChanged) changes.innerHTML += `<div><del>${escapeHtml(ev.diff.old.summary)}</del></div>`;
    if (ev.diff.timeChanged) changes.innerHTML += `<div><del>${fmtTime(ev.diff.old.start)}–${fmtTime(ev.diff.old.end)}</del></div>`;
    if (changes.childElementCount) contentWrap.appendChild(changes);
  }

  el.append(contentWrap);

  // Mark-as-read Button
  if (ev.status && !READ_IDS.has(String(ev.id))) {
    const btn = document.createElement('button');
    btn.className = 'mark-read';
    btn.textContent = 'Gelesen';
    btn.style.marginTop = '5px';
    btn.onclick = async (e) => {
      e.stopPropagation();
      await markAsRead(ev.id);
    };
    el.append(btn);
  }

  return el;
}

function renderExamList() {
  const host = document.getElementById("examList");
  if (!host) return;

  host.innerHTML = "";

  const h = document.createElement("h2");
  h.textContent = "Kommende Klausuren";
  host.appendChild(h);

  // Alle Klausuren aus NEU-Daten
  const exams = RAW_NEU
    .filter(ev => String(ev.summary || "").trim().startsWith("*"))
    .map(ev => ({
      id: ev.termin_id,
      summary: ev.summary,
      start: new Date(ev.start),
      end: new Date(ev.end),
      location: ev.location
    }))
    .filter(ev => ev.start >= new Date()) // nur zukünftige Klausuren
    .sort((a, b) => a.start - b.start);

  if (!exams.length) {
    const empty = document.createElement("div");
    empty.className = "empty";
    empty.textContent = "Keine kommenden Klausuren";
    host.appendChild(empty);
    return;
  }

  for (const ex of exams) {
    const item = document.createElement("div");
    item.className = "exam-item";

    const title = document.createElement("div");
    title.className = "summary";
    title.textContent = ex.summary.replace(/^\*/, ""); // Stern entfernen
    item.appendChild(title);

    const meta = document.createElement("div");
    meta.className = "meta";
    meta.textContent =
      `${fmtDate(ex.start, true)} · ${fmtTime(ex.start)}–${fmtTime(ex.end)}` +
      (ex.location ? ` · ${ex.location}` : "");
    item.appendChild(meta);

    host.appendChild(item);
  }
}


// ======================
// Controller
// ======================
async function render() {
  await ensureDataLoaded();
  await ensureReadsLoaded();           // wichtig: zuerst Gelesen-IDs laden
  const s = weekStart;
  const e = endOfWeek(s);
  renderWeekHeader(s, e);
  const byDay = buildWeekEvents(s, e); // Grid: weiterhin wochenbasiert
  renderGrid(byDay, s);

  // Globale Liste – unabhängig von Woche
  renderChangesListGlobal();
  renderExamList();
}

function hookButtons() {
  document.getElementById('prevWeekBtn').addEventListener('click', () => {
    weekStart.setDate(weekStart.getDate() - 7);
    render();
  });
  document.getElementById('nextWeekBtn').addEventListener('click', () => {
    weekStart.setDate(weekStart.getDate() + 7);
    render();
  });
  document.getElementById('todayBtn').addEventListener('click', () => {
    weekStart = startOfWeek(new Date());
    render();
  });
}

hookButtons();
render().catch(err => {
  console.error('Render error:', err);
  const grid = document.getElementById('grid');
  grid.innerHTML = `<div class="empty">Fehler beim Laden: ${escapeHtml(err.message)}</div>`;
});
