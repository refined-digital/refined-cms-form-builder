// Front-end mirror of the PHP PasswordRules helper. Rules are emitted onto the
// password input's row as data-fb-password-rules (JSON); this evaluates a value
// against them and drives both validation and the live checklist.

/** Parse the data-fb-password-rules JSON off a control's row. Returns [] if none. */
export function passwordRulesFor(el) {
  const row = el.closest('.form__row');
  const raw = row?.dataset?.fbPasswordRules;
  if (!raw) return [];
  try {
    return JSON.parse(raw);
  } catch (e) {
    return [];
  }
}

/** Does a value satisfy one normalised rule? */
export function rulePasses(rule, value) {
  if (rule.type === 'length') {
    const len = value.length;
    if (rule.min != null && len < rule.min) return false;
    if (rule.max != null && len > rule.max) return false;
    return true;
  }
  // regex rule — patterns are stored without delimiters
  try {
    return new RegExp(rule.pattern).test(value);
  } catch (e) {
    return true; // a bad pattern shouldn't block the user
  }
}

/** The first failing rule's label, or null when all pass. */
export function firstPasswordFailure(rules, value) {
  for (const rule of rules) {
    if (!rulePasses(rule, value)) return rule.label;
  }
  return null;
}

/**
 * Update the live checklist under a password field, ticking each rule met.
 * Safe to call on every keystroke.
 */
export function updatePasswordChecklist(el) {
  const row = el.closest('.form__row');
  const list = row?.querySelector('[data-fb-password-checklist]');
  if (!list) return;

  const rules = passwordRulesFor(el);
  const value = el.value ?? '';
  rules.forEach((rule) => {
    const item = list.querySelector(`[data-fb-rule="${rule.key}"]`);
    if (item) item.classList.toggle('form__password-rule--met', rulePasses(rule, value));
  });
}
