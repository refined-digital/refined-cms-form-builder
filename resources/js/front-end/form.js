// Public form runtime. One isolated controller instance per form element on the
// page (supports multiple forms per page; no shared mutable state).
//  - conditional logic (Phase 4)
//  - Zod live/blur validation (Phase 5)
//  - AJAX submit + outcome handling (Phase 5)
//  - submit-enable rule, loading, reCAPTCHA v3, gibberish (Phase 9)
import { initConditions } from './conditions';
import { createValidator } from './validation';
import { submitForm } from './submit';
import { checkGibberish } from './gibberish';

function hasRequiredFields(form) {
  return form.querySelector('[required], [data-fb-required="1"]') !== null;
}

// Phase 9 submit-enable rule:
//  - if the form has required fields -> enable when all required are valid
//  - else -> enable when at least one field has valid content
function evaluateSubmitState(form, validator) {
  const btn = form.querySelector('[type="submit"], .form-button, button[data-fb-submit]');
  if (!btn) return;

  const controls = validator.controls();
  let enable;

  // use the pure isValid() check so toggling the submit button never paints
  // errors on fields the user hasn't touched yet
  if (hasRequiredFields(form)) {
    enable = controls
      .filter((el) => el.hasAttribute('required') || el.dataset.fbRequired === '1')
      .every((el) => validator.isValid(el));
  } else {
    enable = controls.some((el) => {
      const v = (el.value ?? '').toString().trim();
      return v !== '' && validator.isValid(el);
    });
  }

  btn.disabled = !enable;
}

async function executeRecaptcha(form) {
  const siteKey = form.getAttribute('data-red');
  if (!siteKey || !window.grecaptcha) return;
  await new Promise((res) => window.grecaptcha.ready(res));
  const token = await window.grecaptcha.execute(siteKey, { action: 'submit' });
  let input = form.querySelector('input[name="_captcha"]');
  if (!input) {
    input = document.createElement('input');
    input.type = 'hidden';
    input.name = '_captcha';
    form.appendChild(input);
  }
  input.value = token;
}

function initForm(form) {
  if (form.dataset.fbInit === '1') return;
  form.dataset.fbInit = '1';

  const reevaluateConditions = initConditions(form);
  const validator = createValidator(form);

  const refreshSubmit = () => evaluateSubmitState(form, validator);
  form.addEventListener('input', refreshSubmit);
  form.addEventListener('change', () => { reevaluateConditions(); refreshSubmit(); });
  refreshSubmit();

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const validOk = validator.validateAll();
    const gibberishOk = checkGibberish(form);
    if (!validOk || !gibberishOk) {
      refreshSubmit();
      return;
    }

    await executeRecaptcha(form);
    await submitForm(form);
  });
}

function boot() {
  document.querySelectorAll('form.form--builder, form[data-fb-form]').forEach(initForm);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot);
} else {
  boot();
}

export { initForm };
