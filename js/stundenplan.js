
// ======================
// CONFIG
// ======================
const CONFIG = {
  BASE: 'http://localhost/bibapp_xampp/restapi.php',
  ENDPOINT_NEU: '/Calendar/getCalendar',
  ENDPOINT_AENDERUNGEN: '/Calendar/getChanges',
  USE_QUERY_RANGE: false,
  DEFAULT_CLASS_NAME: 'PBD2H24A',
  WEEK_STARTS_MONDAY: true,
};

// ======================
// Klassennamen beziehen
// ======================
function getClassName() {
  const url = new URL(window.location.href);
  return url.searchParams.get('klasse') || CONFIG.DEFAULT_CLASS_NAME;
}
function buildUrl(path, { start = null, end = null } = {}) {
  const klasse = getClassName();
  const url = new URL(CONFIG.BASE + path);
  url.searchParams.set('klasse', klasse);
  if (CONFIG.USE_QUERY_RANGE && start && end) {
    url.searchParams.set('start', start.toISOString().slice(0,10));
    url.searchParams.set('end', end.toISOString().slice(0,10));
  }
  return url.toString();
}

// ======================
// State
// ======================
let RAW_NEU = [];
let RAW_AEND = [];
let weekStart = startOfWeek(new Date());

// ======================
// Utils: Datum/Zeit
// ======================
function startOfWeek(date) {
  const d = new Date(date);
  d.setHours(0,0,0,0);
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
  const yearStart = new Date(Date.UTC(d.getUTCFullYear(),0,1));
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

// ======================
// Daten holen
// ======================
async function fetchDataForRange(s, e) {
  const neuUrl  = buildUrl(CONFIG.ENDPOINT_NEU,        { start: s, end: e });
  const aendUrl = buildUrl(CONFIG.ENDPOINT_AENDERUNGEN, { start: s, end: e });

  const [neuRes, aendRes] = await Promise.all([ fetch(neuUrl), fetch(aendUrl) ]);
  if (!neuRes.ok)  throw new Error('Fehler beim Laden von stundenplan_neu');
  if (!aendRes.ok) throw new Error('Fehler beim Laden von aenderungen');

  const [neu, aend] = await Promise.all([ neuRes.json(), aendRes.json() ]);
  return { neu, aend };
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
// Transformation
// ======================
function buildWeekEvents(s, e) {
  const changesById = new Map();
  for (const ch of RAW_AEND) changesById.set(ch.termin_id, ch);

  const normalizeEvent = (obj, source = 'neu', change = null) => {
    const isAlt = source === 'alt';
    const start = parseISO(isAlt ? obj.start_alt : obj.start);
    const end   = parseISO(isAlt ? obj.end_alt   : obj.end);
    return {
      id: obj.termin_id,
      summary: isAlt ? obj.summary_alt : obj.summary,
      location: isAlt ? obj.location_alt : obj.location,
      start, end,
      raw: obj,
      status: change?.label ?? null,
      change,
    };
  };

  const baseEvents = RAW_NEU
    .map(ev => normalizeEvent(ev, 'neu', changesById.get(ev.termin_id)))
    .filter(ev => overlaps(ev.start, ev.end, s, e));

  const deletedEvents = RAW_AEND
    .filter(ch => ch.label === 'gelöscht')
    .map(ch => normalizeEvent(ch, 'alt', ch))
    .filter(ev => overlaps(ev.start, ev.end, s, e));

  const all = [...baseEvents, ...deletedEvents];
  all.sort((a,b) => a.start - b.start || a.end - b.end || a.summary.localeCompare(b.summary));

  const byDay = Array.from({length: 7}, () => []);
  for (const ev of all) {
    const idx = dayIndex(ev.start, CONFIG.WEEK_STARTS_MONDAY);
    byDay[idx].push(ev);
  }

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
          timeChanged: old.start.getTime() !== ev.start.getTime() || old.end.getTime() !== ev.end.getTime(),
          locationChanged: old.location !== ev.location,
          old,
        };
      }
    }
  }
  return byDay;
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
function renderGrid(byDay, s) {
  const grid = document.getElementById('grid');
  grid.innerHTML = '';
  for (let i=0;i<7;i++){
    const dayDate = new Date(s); dayDate.setDate(dayDate.getDate() + i);
    const col = document.createElement('div');
    col.className = 'day';
    col.setAttribute('role','region');
    col.setAttribute('aria-label', `${fmtDate(dayDate, true)}`);

    const head = document.createElement('header');
    const weekday = document.createElement('div');
    weekday.className = 'weekday';
    weekday.textContent = new Intl.DateTimeFormat('de-DE', { weekday:'long' }).format(dayDate);
    const dateSpan = document.createElement('div');
    dateSpan.className = 'date';
    dateSpan.textContent = new Intl.DateTimeFormat('de-DE', { day:'2-digit', month:'2-digit' }).format(dayDate);
    head.append(weekday, dateSpan);

    const list = document.createElement('div');
    list.className = 'events';

    const events = byDay[i];
    if (!events.length) {
      const empty = document.createElement('div');
      empty.className = 'empty';
      empty.textContent = 'Keine Termine';
      list.appendChild(empty);
    } else {
      for (const ev of events) {
        list.appendChild(renderEvent(ev));
      }
    }
    col.append(head, list);
    grid.appendChild(col);
  }
}
function renderEvent(ev) {
  const el = document.createElement('article');
  el.className = 'event';
  if (ev.status === 'neu') el.classList.add('neu');
  if (ev.status === 'gelöscht') el.classList.add('geloescht');
  if (ev.status === 'geändert') el.classList.add('geaendert');

  const label = document.createElement('div');
  label.className = 'label';
  label.textContent = ev.status ?? '';

  const summary = document.createElement('div');
  summary.className = 'summary';
  summary.textContent = ev.summary || '(ohne Titel)';

  const meta = document.createElement('div');
  meta.className = 'meta';
  meta.textContent = `${fmtTime(ev.start)} – ${fmtTime(ev.end)}`;

  const location = document.createElement('div');
  location.className = 'location';
  location.textContent = ev.location || '';

  const contentWrap = document.createElement('div');
  contentWrap.className = 'content';
  contentWrap.append(summary, meta, location);

  if (ev.status === 'geändert' && ev.diff) {
    const changes = document.createElement('div');
    changes.className = 'changes';
    if (ev.diff.summaryChanged) {
      const line = document.createElement('div');
      line.innerHTML = `<del>${escapeHtml(ev.diff.old.summary || '(ohne Titel)')}</del> → ${escapeHtml(ev.summary || '(ohne Titel)')}`;
      changes.appendChild(line);
    }
    if (ev.diff.timeChanged) {
      const line = document.createElement('div');
      line.innerHTML = `<del>${fmtTime(ev.diff.old.start)}–${fmtTime(ev.diff.old.end)}</del> → ${fmtTime(ev.start)}–${fmtTime(ev.end)}`;
      changes.appendChild(line);
    }
    if (ev.diff.locationChanged) {
      const line = document.createElement('div');
      line.innerHTML = `<del>${escapeHtml(ev.diff.old.location || '')}</del> → ${escapeHtml(ev.location || '')}`;
      changes.appendChild(line);
    }
    if (changes.childElementCount) contentWrap.appendChild(changes);
  }

  el.append(contentWrap);
  if (ev.status) el.append(label);
  return el;
}
function escapeHtml(s) {
  return String(s ?? '')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

// ======================
// Controller
// ======================
async function render() {
  await ensureDataLoaded();
  const s = weekStart;
  const e = endOfWeek(s);
  renderWeekHeader(s,e);
  const byDay = buildWeekEvents(s,e);
  renderGrid(byDay, s);
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
  console.error(err);
  const grid = document.getElementById('grid');
  grid.innerHTML = `<div class="empty">Fehler beim Laden: ${escapeHtml(err.message)}</div>`;
});
