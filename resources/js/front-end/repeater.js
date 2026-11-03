// "Add another" repeater rows. Markup contract:
//
// <div class="form__repeater" data-fb-repeater="field83">
//   <div data-fb-repeater-rows></div>
//   <template data-fb-repeater-template>
//     <div class="form__repeater-row" data-fb-repeater-row>
//       <!-- inputs named field83[__i__][key] -->
//       <button type="button" class="form__repeater-remove" data-fb-repeater-remove>Remove</button>
//     </div>
//   </template>
//   <button type="button" class="button form__repeater-add" data-fb-repeater-add>Add another</button>
// </div>
//
// Removing a row never re-indexes the others, so indexes can have gaps; the
// server tolerates that.

// author CSS such as `.button { display: inline-block }` beats the UA [hidden] rule
function setHidden(el, hidden) {
  el.hidden = hidden;
  el.style.display = hidden ? 'none' : '';
}

function rowsOf(repeater) {
  const container = repeater.querySelector('[data-fb-repeater-rows]');
  return container ? Array.from(container.querySelectorAll(':scope > [data-fb-repeater-row]')) : [];
}

/**
 * The row index a row was rendered with, read from its inputs' names when the
 * row came from the server rather than the template.
 *
 * @param {HTMLElement} repeater
 * @param {HTMLElement} row
 * @returns {number|null}
 */
function indexOfRow(repeater, row) {
  if (row.dataset.fbRepeaterIndex !== undefined) return Number(row.dataset.fbRepeaterIndex);
  const base = repeater.dataset.fbRepeater;
  for (const el of row.querySelectorAll('[name]')) {
    if (el.name.startsWith(`${base}[`)) {
      const match = el.name.slice(base.length).match(/^\[(\d+)\]/);
      if (match) {
        row.dataset.fbRepeaterIndex = match[1];
        return Number(match[1]);
      }
    }
  }
  return null;
}

/**
 * The indexes of a repeater's rows, in display order.
 *
 * @param {HTMLElement} repeater
 * @returns {number[]}
 */
export function rowIndexes(repeater) {
  return rowsOf(repeater).map((row) => indexOfRow(repeater, row)).filter((i) => i !== null);
}

function refreshRemove(repeater) {
  const rows = rowsOf(repeater);
  rows.forEach((row) => {
    row.querySelectorAll('[data-fb-repeater-remove]').forEach((btn) => {
      if (btn.closest('[data-fb-repeater-row]') === row) setHidden(btn, rows.length < 2);
    });
  });
}

function notify(repeater) {
  repeater.closest('form')?.dispatchEvent(new Event('input', { bubbles: true }));
}

/**
 * Append one row from the template, using the next free index.
 *
 * @param {HTMLElement} repeater
 * @returns {HTMLElement|null} the new row
 */
function addRow(repeater) {
  const template = repeater.querySelector('template[data-fb-repeater-template]');
  const container = repeater.querySelector('[data-fb-repeater-rows]');
  if (!template || !container) return null;

  const used = rowIndexes(repeater);
  const index = used.length ? Math.max(...used) + 1 : 0;
  // one past the highest number shown, so a remove then add never repeats one;
  // server rendered rows carry no number and show their position
  const shown = rowsOf(repeater).map((r, i) => Number(r.dataset.fbRepeaterNumber) || i + 1);
  const number = shown.length ? Math.max(...shown) + 1 : 1;

  const holder = document.createElement('div');
  holder.innerHTML = template.innerHTML
    .replaceAll('__i__', String(index))
    .replaceAll('{{n}}', String(number));
  const row = holder.querySelector('[data-fb-repeater-row]');
  if (!row) return null;

  row.dataset.fbRepeaterIndex = String(index);
  row.dataset.fbRepeaterNumber = String(number);
  container.appendChild(row);
  return row;
}

function removeRow(repeater, row) {
  if (rowsOf(repeater).length < 2) return;
  row.remove();
}

/**
 * Grow or shrink a repeater to exactly `count` rows (never below one). Extra
 * rows come off the end.
 *
 * @param {HTMLElement} repeater
 * @param {number} count
 */
export function setRows(repeater, count) {
  const target = Math.max(1, Number(count) || 1);
  while (rowsOf(repeater).length < target) {
    if (!addRow(repeater)) break;
  }
  let rows = rowsOf(repeater);
  while (rows.length > target) {
    rows[rows.length - 1].remove();
    rows = rowsOf(repeater);
  }
  refreshRemove(repeater);
}

/**
 * Wire every repeater in the form: one row on load if none, Add another and
 * Remove buttons.
 *
 * @param {HTMLFormElement} form
 */
export function initRepeaters(form) {
  form.querySelectorAll('[data-fb-repeater]').forEach((repeater) => {
    if (repeater.dataset.fbRepeaterInit === '1') return;
    repeater.dataset.fbRepeaterInit = '1';

    if (!rowsOf(repeater).length) addRow(repeater);
    refreshRemove(repeater);

    repeater.addEventListener('click', (e) => {
      const add = e.target.closest('[data-fb-repeater-add]');
      if (add && add.closest('[data-fb-repeater]') === repeater) {
        e.preventDefault();
        const row = addRow(repeater);
        refreshRemove(repeater);
        row?.querySelector('input:not([type="hidden"]), select, textarea')?.focus();
        notify(repeater);
        return;
      }

      const remove = e.target.closest('[data-fb-repeater-remove]');
      if (remove && remove.closest('[data-fb-repeater]') === repeater) {
        e.preventDefault();
        removeRow(repeater, remove.closest('[data-fb-repeater-row]'));
        refreshRemove(repeater);
        notify(repeater);
      }
    });
  });
}
