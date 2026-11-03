// Browser autosave: keeps a copy of the visitor's answers in localStorage so a
// long form survives a reload or a closed tab. Entry shape:
// { savedAt, page, values: { [name]: value | value[] } }
import { setRows, rowIndexes } from './repeater';

const SKIP_NAMES = ['_token', '_captcha', 'hname', 'htime'];
const DEBOUNCE_MS = 400;
const DAY_MS = 24 * 60 * 60 * 1000;

// per form: pending save timer, and whether saving has been stopped
const state = new WeakMap();

function storageKey(form) {
  return `fb:${form.dataset.fbForm}:${window.location.pathname}`;
}

function readEntry(key) {
  try {
    const raw = window.localStorage.getItem(key);
    return raw ? JSON.parse(raw) : null;
  } catch (e) {
    return null;
  }
}

function writeEntry(key, entry) {
  try {
    window.localStorage.setItem(key, JSON.stringify(entry));
  } catch (e) { /* storage full or blocked: autosave is best effort */ }
}

function removeEntry(key) {
  try {
    window.localStorage.removeItem(key);
  } catch (e) { /* ignore */ }
}

function controlsNamed(form, name) {
  return Array.from(form.querySelectorAll(`[name="${CSS.escape(name)}"]`));
}

// hidden inputs are server-controlled, files can't be stored, passwords must not be
function isStorable(el) {
  const type = (el.type || '').toLowerCase();
  return type !== 'hidden' && type !== 'file' && type !== 'password';
}

/**
 * Collect the storable values of a form.
 *
 * @param {HTMLFormElement} form
 * @returns {Object<string, string|string[]>}
 */
function collect(form) {
  const values = {};
  const data = new FormData(form);

  for (const [name, value] of data.entries()) {
    if (SKIP_NAMES.includes(name) || typeof value !== 'string') continue;
    const controls = controlsNamed(form, name);
    if (!controls.length || !controls.every(isStorable)) continue;

    const multi = name.endsWith('[]') || controls[0].type === 'checkbox' || controls[0].multiple;
    if (name in values) {
      values[name] = [].concat(values[name], value);
    } else {
      values[name] = multi ? [value] : value;
    }
  }

  // an unticked group is absent from FormData; record it so a default-ticked
  // box the visitor unticked doesn't come back ticked
  form.querySelectorAll('input[type="checkbox"][name], input[type="radio"][name]').forEach((el) => {
    if (el.disabled || el.name in values || SKIP_NAMES.includes(el.name)) return;
    values[el.name] = [];
  });

  return values;
}

/**
 * Write one saved value into the controls carrying that name.
 *
 * @param {HTMLElement[]} controls
 * @param {string|string[]} value
 */
function fill(controls, value) {
  const list = [].concat(value ?? []).map(String);
  controls.forEach((el) => {
    if (el.type === 'checkbox' || el.type === 'radio') {
      el.checked = list.includes(el.value);
    } else if (el.tagName === 'SELECT' && el.multiple) {
      Array.from(el.options).forEach((o) => { o.selected = list.includes(o.value); });
    } else {
      el.value = list[0] ?? '';
    }
  });
}

/**
 * Grow each repeater to hold its saved rows. Saved row indexes can have gaps
 * (a removed row is never re-indexed), so the saved rows are mapped in order
 * onto the rendered rows rather than leaving empty rows for the gaps.
 *
 * @param {HTMLFormElement} form
 * @param {Object<string, string|string[]>} values
 * @returns {Object<string, string|string[]>} values with repeater names remapped
 */
function restoreRepeaters(form, values) {
  let remapped = { ...values };

  form.querySelectorAll('[data-fb-repeater]').forEach((repeater) => {
    const base = repeater.dataset.fbRepeater;
    const prefix = `${base}[`;
    const saved = new Set();
    Object.keys(remapped).forEach((name) => {
      const match = name.startsWith(prefix) && name.slice(base.length).match(/^\[(\d+)\]/);
      if (match) saved.add(Number(match[1]));
    });
    if (!saved.size) return;

    const ordered = [...saved].sort((a, b) => a - b);
    setRows(repeater, ordered.length);
    const rendered = rowIndexes(repeater);

    const next = {};
    Object.entries(remapped).forEach(([name, value]) => {
      const match = name.startsWith(prefix) && name.slice(base.length).match(/^\[(\d+)\](.*)$/);
      if (!match) {
        next[name] = value;
        return;
      }
      const target = rendered[ordered.indexOf(Number(match[1]))];
      if (target !== undefined) next[`${base}[${target}]${match[2]}`] = value;
    });
    remapped = next;
  });

  return remapped;
}

/**
 * Forget the saved copy of a form and stop saving it (after a successful submit).
 *
 * @param {HTMLFormElement} form
 */
export function clearAutosave(form) {
  const s = state.get(form);
  if (s) {
    clearTimeout(s.timer);
    s.stopped = true;
  }
  removeEntry(storageKey(form));
}

/**
 * Save the form to localStorage as the visitor types, restore it on load, and
 * wire the Start over link.
 *
 * @param {HTMLFormElement} form
 * @param {{ show: (index: number) => void, current: () => number }|null} [pages]
 */
export function initAutosave(form, pages = null) {
  if (state.has(form)) return;
  const s = { timer: null, stopped: false, restoring: false };
  state.set(form, s);

  const key = storageKey(form);
  const days = Number(form.dataset.fbAutosaveDays) || 30;

  const save = () => {
    if (s.stopped) return;
    writeEntry(key, { savedAt: Date.now(), page: pages ? pages.current() : 0, values: collect(form) });
  };

  const schedule = () => {
    if (s.stopped || s.restoring) return;
    clearTimeout(s.timer);
    s.timer = setTimeout(save, DEBOUNCE_MS);
  };

  const entry = readEntry(key);
  // a savedAt in the future (clock change, tampering) would never expire, so it counts as invalid
  const now = Date.now();
  if (entry && (typeof entry.savedAt !== 'number' || entry.savedAt > now || now - entry.savedAt > days * DAY_MS)) {
    removeEntry(key);
  } else if (entry && entry.values && typeof entry.values === 'object') {
    // restoring must not refresh savedAt: expiry counts from the visitor's last change
    s.restoring = true;
    try {
      const values = restoreRepeaters(form, entry.values);
      const touched = [];
      Object.entries(values).forEach(([name, value]) => {
        const controls = controlsNamed(form, name).filter(isStorable);
        if (!controls.length) return;
        fill(controls, value);
        touched.push(...controls);
      });
      touched.forEach((el) => el.dispatchEvent(new Event('change', { bubbles: true })));
      if (pages && Number(entry.page) > 0) pages.show(Number(entry.page));
    } finally {
      s.restoring = false;
    }
  }

  form.addEventListener('input', schedule);
  form.addEventListener('change', schedule);
  form.addEventListener('fb:page-change', schedule);

  form.querySelectorAll('[data-fb-start-over]').forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      clearAutosave(form);
      // stop the browser's own form state restoration bringing the answers back
      form.reset();
      window.location.reload();
    });
  });
}
