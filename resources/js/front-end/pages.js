// Page navigation for paged forms. Pages are all visible until this runs (no-JS
// fallback); the non-current ones are then hidden with the `hidden` attribute.

/**
 * Wire Back/Next/indicator for a paged form.
 *
 * @param {HTMLFormElement} form
 * @param {{ validateWithin: (container: Element) => boolean }} validator
 * @returns {{ show: (index: number) => void, current: () => number }}
 */
export function initPages(form, validator) {
  const pages = Array.from(form.querySelectorAll('[data-fb-page]'));
  const back = form.querySelector('[data-fb-page-back]');
  const next = form.querySelector('[data-fb-page-next]');
  const indicator = form.querySelector('[data-fb-page-indicator]');
  const submit = form.querySelector('[type="submit"], .form-button, button[data-fb-submit]');
  const total = pages.length;
  let index = 0;

  // author CSS such as `.button { display: inline-block }` beats the UA [hidden] rule
  const setHidden = (el, hidden) => {
    el.hidden = hidden;
    el.style.display = hidden ? 'none' : '';
  };

  const scrollToTop = () => form.scrollIntoView({ behavior: 'smooth', block: 'start' });

  const render = () => {
    const last = index === total - 1;
    pages.forEach((page, i) => setHidden(page, i !== index));
    if (indicator) {
      const name = pages[index]?.dataset.fbPageName;
      indicator.textContent = `Page ${index + 1} of ${total}${name ? ` · ${name}` : ''}`;
      setHidden(indicator, total < 2);
    }
    if (back) setHidden(back, index === 0);
    if (next) setHidden(next, last);
    if (submit) setHidden(submit, !last);
  };

  const show = (target) => {
    const clamped = Math.max(0, Math.min(total - 1, Number(target) || 0));
    const changed = clamped !== index;
    index = clamped;
    render();
    scrollToTop();
    if (changed) form.dispatchEvent(new CustomEvent('fb:page-change', { detail: { index } }));
  };

  const goNext = () => {
    if (index >= total - 1) return;
    if (!validator.validateWithin(pages[index])) {
      pages[index].querySelector('.form__control--error')?.focus();
      return;
    }
    show(index + 1);
  };

  next?.addEventListener('click', goNext);
  back?.addEventListener('click', () => show(index - 1));

  // Enter inside a field submits implicitly; on an earlier page that means "next".
  // registered before form.js's submit handler, so stopping propagation here wins
  form.addEventListener('submit', (e) => {
    if (index < total - 1) {
      e.preventDefault();
      e.stopImmediatePropagation();
      goNext();
    }
  });

  if (total) render();

  return { show, current: () => index };
}
