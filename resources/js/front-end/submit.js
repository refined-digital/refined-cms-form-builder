// AJAX submit for the public form. FormData -> fetch, with:
//  - loading state on the submit button
//  - 419 (expired CSRF) one-shot retry after refreshing the token
//  - 422 -> map server field errors onto the inputs
//  - success -> outcome handling (message replace / redirect)
//
// reCAPTCHA v3 token injection + the submit-enable rule live in form.js (Phase 9).

const ERROR_CLASS = 'form__control--error';
const ROW_ERROR_CLASS = 'form__row--has-error';
const MSG_CLASS = 'form__error';

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function setLoading(form, loading) {
  const btn = form.querySelector('[type="submit"], .form-button, button');
  if (!btn) return;
  btn.classList.toggle('button--loading', loading);
  btn.disabled = loading;
}

function applyServerErrors(form, errors) {
  Object.entries(errors || {}).forEach(([key, messages]) => {
    // key may be field12 or field12.0 etc.
    const base = key.split('.')[0];
    const el = form.querySelector(`[name="${base}"], [name="${base}[]"]`);
    const row = el?.closest('.form__row');
    if (!el || !row) return;
    el.classList.add(ERROR_CLASS);
    row.classList.add(ROW_ERROR_CLASS);
    let msg = row.querySelector(`.${MSG_CLASS}`);
    if (!msg) {
      msg = document.createElement('div');
      msg.className = MSG_CLASS;
      row.appendChild(msg);
    }
    msg.textContent = Array.isArray(messages) ? messages[0] : messages;
  });
}

function handleSuccess(form, data) {
  // redirect outcome
  if (data?.url) {
    window.location.href = data.url;
    return;
  }

  // message outcome — replace the form (or a declared replacement element)
  const replacementSel = form.getAttribute('data-replacement');
  const target = replacementSel ? document.querySelector(replacementSel) : form;
  if (target && data?.confirmation) {
    const wrap = document.createElement('div');
    wrap.className = 'form__confirmation';
    wrap.innerHTML = data.confirmation;
    target.replaceWith(wrap);
  }
}

async function postForm(form) {
  const body = new FormData(form);
  return fetch(form.getAttribute('action'), {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': csrfToken(),
      Accept: 'application/json',
    },
    body,
  });
}

export async function submitForm(form) {
  setLoading(form, true);
  try {
    let res = await postForm(form);

    // expired CSRF — refresh token once and retry
    if (res.status === 419) {
      try {
        const ping = await fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const html = await ping.text();
        const match = html.match(/name="csrf-token" content="([^"]+)"/);
        if (match) {
          document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', match[1]);
        }
      } catch (e) { /* ignore */ }
      res = await postForm(form);
    }

    if (res.status === 422) {
      const data = await res.json();
      applyServerErrors(form, data.errors || {});
      return { ok: false, status: 422 };
    }

    if (!res.ok) {
      return { ok: false, status: res.status };
    }

    const data = await res.json().catch(() => ({}));
    handleSuccess(form, data);
    return { ok: true, data };
  } finally {
    setLoading(form, false);
  }
}
