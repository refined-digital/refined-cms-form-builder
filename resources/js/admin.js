// form-builder admin editor bundle.
//
// loaded as a plain <script> AFTER core's main.js but BEFORE DOMContentLoaded,
// so it registers its rd-fb-* components into core's single Vue app (exposed as
// window.RefinedCMS) before that app mounts. it does NOT create its own Vue app.
import './admin/styles.scss';

import FormBuilderEditor from './components/FormBuilderEditor.vue';

const components = {
  'rd-fb-editor': FormBuilderEditor,
};

const register = () => {
  if (!window.RefinedCMS || typeof window.RefinedCMS.registerComponents !== 'function') {
    console.error('[form-builder] window.RefinedCMS.registerComponents is unavailable; ensure core main.js loaded first.');
    return;
  }
  window.RefinedCMS.registerComponents(components);
};

register();
