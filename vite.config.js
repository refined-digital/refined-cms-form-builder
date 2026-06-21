import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = path.dirname(fileURLToPath(import.meta.url));
const resolve = (p) => path.resolve(root, p);

// Admin editor bundle. Loaded as a plain <script> AFTER core's main.js, so it
// shares core's Vue runtime (window.RefinedCMS.Vue) rather than bundling a second
// copy — a duplicate Vue instance breaks registering components into core's app.
//
// We achieve the share with the standard Rollup mechanism: mark `vue` external
// and map it to the global in an IIFE bundle. (An earlier virtual re-export shim
// collided under minification — "Identifier 'xx' already declared" — because it
// emitted 171 top-level consts that Rollup duplicated alongside vuedraggable.)
export default defineConfig({
  css: {
    preprocessorOptions: {
      scss: {
        loadPaths: ['node_modules', resolve('resources/sass')],
        silenceDeprecations: ['import', 'legacy-js-api', 'global-builtin'],
      },
    },
  },

  build: {
    outDir: 'assets',
    emptyOutDir: false,
    manifest: false,
    cssCodeSplit: false,
    chunkSizeWarningLimit: 2000,
    minify: 'terser',
    rollupOptions: {
      // single input -> IIFE is valid
      input: resolve('resources/js/admin.js'),
      external: ['vue'],
      output: {
        format: 'iife',
        name: 'RefinedFormBuilderAdmin',
        entryFileNames: 'js/form-builder-admin.js',
        assetFileNames: (assetInfo) => {
          const name = assetInfo.names?.[0] ?? assetInfo.name ?? '';
          if (name.endsWith('.css')) {
            return 'css/form-builder-admin.css';
          }
          return 'assets/[name][extname]';
        },
        globals: { vue: 'RefinedCMSVue' },
      },
    },
  },

  plugins: [
    vue({
      template: {
        transformAssetUrls: {
          includeAbsolute: false,
        },
      },
    }),
  ],
});
