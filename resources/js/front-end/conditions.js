// Conditional logic engine for the public form. Reads each field row's
// data-fb-conditions (JSON written by the editor) and shows/hides/enables/
// disables the field based on other fields' current values.
//
// Rule shape (matches the editor + visibility_rules column):
// { action: 'show'|'hide'|'enable'|'disable', logic: 'and'|'or',
//   rules: [ { field: <fieldId>, operator, value } ] }
//
// rule.field is the field id; the input name is `field<id>`.

function inputValue(form, fieldId) {
  const name = `field${fieldId}`;
  const nodes = form.querySelectorAll(`[name="${name}"], [name="${name}[]"]`);
  if (!nodes.length) return { value: '', checkedAny: false };

  // checkboxes / radios: collect checked values
  const first = nodes[0];
  if (first.type === 'checkbox' || first.type === 'radio') {
    const checked = Array.from(nodes).filter((n) => n.checked);
    return {
      value: checked.map((n) => n.value).join(','),
      checkedAny: checked.length > 0,
    };
  }

  return { value: (first.value ?? '').toString(), checkedAny: !!first.value };
}

function evalRule(form, rule) {
  const { value, checkedAny } = inputValue(form, rule.field);
  const target = (rule.value ?? '').toString();

  switch (rule.operator) {
    case 'equals': return value === target;
    case 'not_equals': return value !== target;
    case 'contains': return value.toLowerCase().includes(target.toLowerCase());
    case 'empty': return value === '';
    case 'not_empty': return value !== '';
    case 'checked': return checkedAny;
    case 'unchecked': return !checkedAny;
    case 'gt': return parseFloat(value) > parseFloat(target);
    case 'lt': return parseFloat(value) < parseFloat(target);
    default: return false;
  }
}

function evalGroup(form, config) {
  if (!config.rules || !config.rules.length) return true;
  const results = config.rules
    .filter((r) => r.field != null)
    .map((r) => evalRule(form, r));
  if (!results.length) return true;
  return config.logic === 'or' ? results.some(Boolean) : results.every(Boolean);
}

function applyAction(row, action, matched) {
  const inputs = row.querySelectorAll('input, select, textarea');

  switch (action) {
    case 'show':
      row.style.display = matched ? '' : 'none';
      row.dataset.fbHidden = matched ? '' : '1';
      break;
    case 'hide':
      row.style.display = matched ? 'none' : '';
      row.dataset.fbHidden = matched ? '1' : '';
      break;
    case 'enable':
      inputs.forEach((i) => { i.disabled = !matched; });
      row.dataset.fbHidden = '';
      break;
    case 'disable':
      inputs.forEach((i) => { i.disabled = matched; });
      row.dataset.fbHidden = '';
      break;
    default:
      break;
  }
}

export function initConditions(form) {
  const rows = Array.from(form.querySelectorAll('[data-fb-conditions]'));
  if (!rows.length) return () => {};

  const parsed = rows
    .map((row) => {
      try {
        return { row, config: JSON.parse(row.getAttribute('data-fb-conditions')) };
      } catch (e) {
        return null;
      }
    })
    .filter(Boolean);

  const evaluate = () => {
    parsed.forEach(({ row, config }) => {
      const matched = evalGroup(form, config);
      applyAction(row, config.action, matched);
    });
  };

  form.addEventListener('input', evaluate);
  form.addEventListener('change', evaluate);
  evaluate(); // initial state

  return evaluate;
}

// a field hidden by conditions should not be validated/required
export function isFieldConditionallyHidden(row) {
  return row?.dataset?.fbHidden === '1';
}
