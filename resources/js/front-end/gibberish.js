// Front-end gibberish (keyboard-mashing) heuristic. Mirrors the server-side
// Rules/Gibberish.php definition — the SERVER is authoritative; this is a fast
// pre-check that surfaces the same inline error before submit.
//
// Applies to single-line Text and Textarea controls unless the field opts out
// (data-fb-gibberish="0"). Tune thresholds in ONE place to match the server.
const ROW_ERROR_CLASS = 'form__row--has-error';
const ERROR_CLASS = 'form__control--error';
const MSG_CLASS = 'form__error';
const MESSAGE = 'This looks like spam. Please enter a valid response.';

// shared thresholds (keep in sync with Gibberish.php)
const MIN_LENGTH = 8;        // ignore short values
const MIN_VOWEL_RATIO = 0.15; // words with almost no vowels look mashed
const MAX_CONSONANT_RUN = 5;  // long consonant runs look mashed

export function looksLikeGibberish(value) {
  const v = (value || '').trim();
  if (v.length < MIN_LENGTH) return false;

  // ignore things with structure: spaces, urls, emails, numbers-heavy
  if (/\s/.test(v) && v.split(/\s+/).length >= 2) {
    // multi-word — evaluate each long word
    return v.split(/\s+/).some((w) => w.length >= MIN_LENGTH && wordIsGibberish(w));
  }
  return wordIsGibberish(v);
}

function wordIsGibberish(word) {
  const letters = word.replace(/[^a-z]/gi, '');
  if (letters.length < MIN_LENGTH) return false;

  const vowels = (letters.match(/[aeiou]/gi) || []).length;
  const vowelRatio = vowels / letters.length;
  if (vowelRatio < MIN_VOWEL_RATIO) return true;

  // long consonant run
  const longRun = new RegExp(`[bcdfghjklmnpqrstvwxyz]{${MAX_CONSONANT_RUN + 1},}`, 'i');
  if (longRun.test(letters)) return true;

  // long run of a single repeated char (aaaaaa) or alternating (ababab)
  if (/(.)\1{4,}/.test(word)) return true;

  return false;
}

export function checkGibberish(form) {
  let ok = true;
  const controls = form.querySelectorAll(
    'input[name^="field"][type="text"], textarea[name^="field"]'
  );
  controls.forEach((el) => {
    const row = el.closest('.form__row');
    if (el.dataset.fbGibberish === '0' || row?.dataset?.fbGibberish === '0') return;
    if (el.disabled) return;
    if (looksLikeGibberish(el.value)) {
      ok = false;
      el.classList.add(ERROR_CLASS);
      if (row) {
        row.classList.add(ROW_ERROR_CLASS);
        let msg = row.querySelector(`.${MSG_CLASS}`);
        if (!msg) {
          msg = document.createElement('div');
          msg.className = MSG_CLASS;
          row.appendChild(msg);
        }
        msg.textContent = MESSAGE;
      }
    }
  });
  return ok;
}
