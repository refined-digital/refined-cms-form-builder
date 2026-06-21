# Phase 0 — Build & Integration Foundation

> Enables form-builder to ship Vue components that mount inside core's existing admin app.
> Required before Phase 2 (the Vue editor). Can be built in parallel with Phase 1.

## Goal

Give form-builder its own Vue 3 + Vite build, and add a registration hook to `core` so
satellite packages contribute components/directives into core's single Vue app (mounted
on `#app`) rather than booting a second app.

## Key facts

- Core entry `core/resources/js/main.js` hard-imports ~27 components, registers them with
  `app.component(...)`, then `app.mount('#app')`. Exposes `window.app` (Pinia proxy),
  `window.eventBus` (mitt), `window.dragula`, `window.swal`. Builds to `assets/`, published
  to `public/vendor/refined/core/`; admin loads it as a plain `<script>`.
- No registration hook exists today. `vuedraggable` 4.1 + `dragula` 3.7 already in core deps.
- form-builder currently only compiles SCSS via `webpack.mix.js` — no JS build.

## 1. Core: component-registration hook

In `core/resources/js/main.js`, expose a global registrar and **defer the mount** so
package bundles that load after core can register before mount:

```js
window.RefinedCMS = {
  app, pinia, ui, config, eventBus,
  registerComponent(name, c) { app.component(name, c); },
  registerComponents(map) { Object.entries(map).forEach(([n, c]) => app.component(n, c)); },
  booted: false,
  boot() { if (!this.booted) { app.mount('#app'); this.booted = true; } },
};
```

Update `core/.../views/layouts/master.blade.php` to load core's bundle, then any
package admin bundles, then call `window.RefinedCMS.boot()` last.

## 2. Core: package admin-asset enqueue

Add (or reuse) an aggregator so a package can register a built admin JS/CSS URL that
master.blade.php emits. The service provider already uses `PackageAggregate` /
`ModuleAggregate` / `RouteAggregate` — follow that pattern (e.g. `AdminAssetAggregate`).
If a suitable aggregator already exists, reuse it instead of adding one.

## 3. form-builder: Vue 3 + Vite build

- New `vite.config.js` building `resources/js/admin.js` → `assets/js/form-builder-admin.js`
  (+ CSS). Keep the existing front-end `webpack.mix.js` SCSS build for `form.css`.
- `package.json`: add `vue`, `vite`, `@vitejs/plugin-vue`, `vuedraggable`; add `build` /
  `watch` scripts. Node is pinned to v24.10.0.
- `resources/js/admin.js` imports the editor components and calls
  `window.RefinedCMS.registerComponents({ 'rd-fb-editor': FormBuilderEditor, ... })`.
  It does **not** create its own Vue app.
- `FormBuilderServiceProvider` publishes built assets to
  `public/vendor/refined/form-builder/` and registers them via the core asset aggregator.

## Critical files

- `core/resources/js/main.js`
- `core/.../views/layouts/master.blade.php`
- core asset aggregate (find or create)
- `form-builder/vite.config.js` (new)
- `form-builder/package.json`
- `form-builder/resources/js/admin.js` (new)
- `form-builder/src/Module/Providers/FormBuilderServiceProvider.php`

## Verification

Build core + form-builder, publish assets, load any admin page; confirm `window.RefinedCMS`
exists and a trivial `rd-fb-test` component renders inside core's app.
