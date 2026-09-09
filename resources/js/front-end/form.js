// Public form runtime. One isolated controller instance per form element on the
// page (supports multiple forms per page; no shared mutable state).
//  - conditional logic (Phase 4)
//  - Zod live/blur validation (Phase 5)
//  - AJAX submit + outcome handling (Phase 5)
//  - submit-enable rule, loading, reCAPTCHA v3, gibberish (Phase 9)
import { initConditions } from './conditions';
import { createValidator } from './validation';
import { submitForm, setLoading } from './submit';
import { checkGibberish } from './gibberish';

function hasRequiredFields(form) {
  return form.querySelector('[required], [data-fb-required="1"]') !== null;
}

// Phase 9 submit-enable rule:
//  - if the form has required fields -> enable when all required are valid
//  - else -> enable when at least one field has valid content
//
// The button is marked aria-disabled rather than disabled: a truly disabled
// button swallows the click, so the submit handler never runs and the visitor
// gets no explanation of what's wrong. Clicking while invalid submits, which
// runs validateAll() and paints the per-field messages.
function evaluateSubmitState(form, validator) {
  const btn = form.querySelector('[type="submit"], .form-button, button[data-fb-submit]');
  if (!btn) return;

  // a host app may be shadowing submit.blade.php with a copy that still hardcodes
  // `disabled`; nothing else clears it now, and a stuck-disabled button can never
  // be submitted. the loading check leaves submit.js's own disable alone.
  if (btn.disabled && !btn.classList.contains('button--loading')) {
    btn.disabled = false;
  }

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

  btn.setAttribute('aria-disabled', String(!enable));
  btn.classList.toggle('button--disabled', !enable);
}

// v3 needs api.js?render=<site key> before grecaptcha.execute() exists. Injected
// here rather than from blade so a host layout that already loads it doesn't end
// up with the page fetching it twice.
function loadRecaptcha(siteKey) {
  if (window.grecaptcha || document.querySelector('script[src*="recaptcha/api.js"]')) return;
  const s = document.createElement('script');
  s.src = `https://www.google.com/recaptcha/api.js?render=${encodeURIComponent(siteKey)}`;
  s.async = true;
  document.head.appendChild(s);
}

async function executeRecaptcha(form) {
  const siteKey = form.getAttribute('data-red');
  if (!siteKey) return;
  if (!window.grecaptcha) {
    // the form wants reCAPTCHA but api.js never loaded — the token posts empty
    // and the server rejects it, which reads as a mystery "could not be sent"
    console.warn('[form-builder] reCAPTCHA is enabled on this form but api.js did not load.');
    return;
  }
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

  const siteKey = form.getAttribute('data-red');
  if (siteKey) loadRecaptcha(siteKey);

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
      // the first bad field can be well above the fold on a long form, so the
      // message alone isn't necessarily visible feedback
      form.querySelector('.form__control--error')?.focus({ preventScroll: false });
      return;
    }

    // loading starts before the reCAPTCHA round-trip, not after — that await is
    // the visible delay between clicking submit and the button reacting
    setLoading(form, true);
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
