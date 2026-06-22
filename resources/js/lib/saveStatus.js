// Shared reactive save status used by the editor's SaveIndicator. Every API
// mutation wraps itself with track() so any save anywhere in the editor surfaces
// the same unintrusive saving → saved → fade indicator.
import { ref } from 'vue';

// '', 'saving', 'saved', 'error'
export const saveStatus = ref('');

let savedTimer = null;
let concurrent = 0;

function clearTimer() {
  if (savedTimer) {
    clearTimeout(savedTimer);
    savedTimer = null;
  }
}

// wrap a promise-returning fn so the indicator reflects its progress
export async function track(promise) {
  clearTimer();
  concurrent += 1;
  saveStatus.value = 'saving';
  try {
    const result = await promise;
    concurrent -= 1;
    if (concurrent <= 0) {
      concurrent = 0;
      saveStatus.value = 'saved';
      // hold "Saved!" briefly, then fade away
      savedTimer = setTimeout(() => { saveStatus.value = ''; }, 1800);
    }
    return result;
  } catch (e) {
    concurrent = Math.max(0, concurrent - 1);
    saveStatus.value = 'error';
    savedTimer = setTimeout(() => { saveStatus.value = ''; }, 3000);
    throw e;
  }
}
