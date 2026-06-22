// Mixin for editor modals: while the modal is mounted, flag the CMS shell so its
// overlay sits above the sidebar (mirrors core's link/media modal behaviour via
// the generic .app--has-modal class added to #app).
//
// Reference-counted so multiple stacked modals don't clear the flag early.
let openCount = 0;

function appEl() {
  return document.getElementById('app');
}

export default {
  mounted() {
    openCount += 1;
    appEl()?.classList.add('app--has-modal');
  },
  beforeUnmount() {
    openCount = Math.max(0, openCount - 1);
    if (openCount === 0) {
      appEl()?.classList.remove('app--has-modal');
    }
  },
};
