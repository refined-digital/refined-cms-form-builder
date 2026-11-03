// Public form runtime. One isolated controller instance per form element on the
// page (supports multiple forms per page; no shared mutable state).
//  - conditional logic (Phase 4)
//  - Zod live/blur validation (Phase 5)
//  - AJAX submit + outcome handling (Phase 5)
//  - submit-enable rule, loading, reCAPTCHA v3, gibberish (Phase 9)
//  - pages, "Add another" repeater rows, browser autosave
import { initConditions } from './conditions';
import { createValidator } from './validation';
import { submitForm, setLoading } from './submit';
import { checkGibberish } from './gibberish';
import { initPages } from './pages';
import { initRepeaters } from './repeater';
import { initAutosave, clearAutosave } from './autosave';

// on a paged form the submit button lives on the last page, so only that page's
// controls gate it; earlier pages are validated by Next
function submitScope(form) {
  if (form.dataset.fbPages !== '1') return form;
  const pages = form.querySelectorAll('[data-fb-page]');
  return pages.length ? pages[pages.length - 1] : form;
}

function hasRequiredFields(form) {
  return submitScope(form).querySelector('[required], [data-fb-required="1"]') !== null;
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

  const scope = submitScope(form);
  const controls = validator.controls().filter((el) => scope.contains(el));
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

  // rows exist before conditions and validation look for controls
  initRepeaters(form);
  const reevaluateConditions = initConditions(form);
  const validator = createValidator(form);
  const pages = form.dataset.fbPages === '1' ? initPages(form, validator) : null;

  const refreshSubmit = () => evaluateSubmitState(form, validator);
  form.addEventListener('input', refreshSubmit);
  form.addEventListener('change', () => { reevaluateConditions(); refreshSubmit(); });
  // restore after the listeners above so its change events re-run conditions
  const autosave = form.dataset.fbAutosave === '1';
  if (autosave) initAutosave(form, pages);
  refreshSubmit();

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const validOk = validator.validateAll();
    const gibberishOk = checkGibberish(form);
    if (!validOk || !gibberishOk) {
      refreshSubmit();
      // the first bad field can be well above the fold on a long form, so the
      // message alone isn't necessarily visible feedback
      const firstError = form.querySelector('.form__control--error');
      // the first error may sit on an earlier page that is currently hidden
      const errorPage = firstError?.closest('[data-fb-page]');
      if (pages && errorPage) showPageOf(pages, errorPage);
      firstError?.focus({ preventScroll: false });
      return;
    }

    // loading starts before the reCAPTCHA round-trip, not after — that await is
    // the visible delay between clicking submit and the button reacting
    setLoading(form, true);
    await executeRecaptcha(form);
    const result = await submitForm(form, autosave ? { onSuccess: () => clearAutosave(form) } : {});

    // a server-side field error can sit on an earlier, hidden page
    if (!result.ok && pages) {
      const serverError = form.querySelector('.form__control--error');
      const errorPage = serverError?.closest('[data-fb-page]');
      if (errorPage) {
        showPageOf(pages, errorPage);
        serverError.focus();
      }
    }
  });
}

/**
 * Switch to the page holding an error, only when it isn't already showing: show()
 * scrolls to the form top, which would fight the focus on the error itself.
 *
 * @param {{ show: (index: number) => void, current: () => number }} pages
 * @param {HTMLElement} pageEl
 */
function showPageOf(pages, pageEl) {
  const index = Number(pageEl.dataset.fbPage);
  if (index !== pages.current()) pages.show(index);
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
