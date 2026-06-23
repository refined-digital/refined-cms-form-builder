// Live + blur validation for the public form, built on Zod. Schemas are inferred
// from each rendered input's attributes (type, required, minlength, pattern) so
// the editor's settings drive validation without a separate client contract.
//
// Errors show a red border (form__control--error) + an inline message. We do NOT
// use core's legacy FormValidate.js/FormBuilder.js for builder forms.
import { z } from 'zod';
import { isFieldConditionallyHidden } from './conditions';
import { passwordRulesFor, firstPasswordFailure, updatePasswordChecklist } from './passwordRules';

const ERROR_CLASS = 'form__control--error';
const ROW_ERROR_CLASS = 'form__row--has-error';
const MSG_CLASS = 'form__error';

// Build a Zod schema for a single control element from its attributes.
function schemaForControl(el, label) {
  const name = label || 'This field';
  const required = el.hasAttribute('required') || el.dataset.fbRequired === '1';
  const type = (el.getAttribute('type') || el.tagName).toLowerCase();

  let schema = z.string();

  // format checks skip an empty value (an optional field left blank is fine);
  // the required check below is what enforces presence.
  if (type === 'email') {
    schema = z.string().refine(
      (v) => v === '' || z.string().email().safeParse(v).success,
      `${name} must be a valid email address.`,
    );
  } else if (type === 'number' || el.inputMode === 'decimal') {
    schema = z.string().refine((v) => v === '' || !Number.isNaN(Number(v)), `${name} must be a number.`);
  }

  const min = el.getAttribute('minlength') || el.dataset.fbMin;
  if (min) {
    schema = schema.refine((v) => v === '' || v.length >= Number(min), `${name} must be at least ${min} characters.`);
  }

  // strong-password rules (when the field opted in) — invalid until all pass
  const pwRules = passwordRulesFor(el);
  if (pwRules.length) {
    schema = schema.refine(
      (v) => v === '' || firstPasswordFailure(pwRules, v) === null,
      (v) => ({ message: `${name} is not strong enough — ${firstPasswordFailure(pwRules, v)}.` }),
    );
  }

  if (required) {
    schema = schema.refine((v) => v !== null && v !== undefined && `${v}`.trim() !== '', `The ${name} field is required.`);
  } else {
    schema = schema.optional();
  }

  return schema;
}

function controlValue(el) {
  if (el.type === 'checkbox' || el.type === 'radio') {
    const group = el.form.querySelectorAll(`[name="${el.name}"]`);
    const checked = Array.from(group).find((n) => n.checked);
    return checked ? checked.value : '';
  }
  return el.value ?? '';
}

function getRow(el) {
  return el.closest('.form__row');
}

function customMessage(el) {
  const row = getRow(el);
  return el.dataset.fbError || row?.dataset?.fbError || null;
}

function showError(el, message) {
  const row = getRow(el);
  el.classList.add(ERROR_CLASS);
  if (row) {
    row.classList.add(ROW_ERROR_CLASS);
    let msg = row.querySelector(`.${MSG_CLASS}`);
    if (!msg) {
      msg = document.createElement('div');
      msg.className = MSG_CLASS;
      row.appendChild(msg);
    }
    msg.textContent = message;
  }
}

function clearError(el) {
  const row = getRow(el);
  el.classList.remove(ERROR_CLASS);
  if (row) {
    row.classList.remove(ROW_ERROR_CLASS);
    const msg = row.querySelector(`.${MSG_CLASS}`);
    if (msg) msg.remove();
  }
}

export function createValidator(form) {
  // every named control that isn't a framework field
  const controls = () => Array.from(
    form.querySelectorAll('input[name^="field"], select[name^="field"], textarea[name^="field"]')
  ).filter((el) => el.type !== 'hidden');

  const labelFor = (el) => {
    const row = getRow(el);
    return row?.getAttribute('data-required-label') || row?.querySelector('.form__label')?.textContent?.trim() || 'This field';
  };

  // pure validity check — NO UI side effects. used by the submit-enable gate so
  // it can run on load without painting errors on untouched fields.
  const isValid = (el) => {
    const row = getRow(el);
    if (el.disabled || (row && (isFieldConditionallyHidden(row) || row.style.display === 'none'))) {
      return true;
    }
    return schemaForControl(el, labelFor(el)).safeParse(controlValue(el)).success;
  };

  // validate one control AND reflect the result in the UI (border + message).
  // only call from interaction (blur / touched input) or submit.
  const validateControl = (el) => {
    const row = getRow(el);
    if (el.disabled || (row && (isFieldConditionallyHidden(row) || row.style.display === 'none'))) {
      clearError(el);
      return true;
    }

    const schema = schemaForControl(el, labelFor(el));
    const result = schema.safeParse(controlValue(el));
    if (result.success) {
      clearError(el);
      return true;
    }
    const message = customMessage(el) || result.error.issues[0]?.message || 'Invalid value.';
    showError(el, message);
    return false;
  };

  const validateAll = () => {
    let ok = true;
    controls().forEach((el) => {
      if (!validateControl(el)) ok = false;
    });
    return ok;
  };

  // wire blur + live (live only after the field has been touched)
  const touched = new WeakSet();
  form.addEventListener('blur', (e) => {
    const el = e.target;
    if (el.matches('input[name^="field"], select[name^="field"], textarea[name^="field"]')) {
      touched.add(el);
      validateControl(el);
    }
  }, true);

  form.addEventListener('input', (e) => {
    const el = e.target;
    // tick the password checklist live, before the field is "touched"
    updatePasswordChecklist(el);
    if (touched.has(el)) validateControl(el);
  });

  return { validateAll, validateControl, isValid, controls };
}
