import { defineConfig } from 'vite';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = path.dirname(fileURLToPath(import.meta.url));
const resolve = (p) => path.resolve(root, p);

// Front-end public form styles (was laravel-mix form.scss -> form.css). Kept in
// a separate config so the admin bundle can be a single-input IIFE (see
// vite.config.js). Phase 5 will add the front-end JS entry here too.
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
      input: {
        form: resolve('resources/sass/form.scss'),
        'form-builder-front-end': resolve('resources/js/front-end/form.js'),
      },
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/chunks/[name].js',
        assetFileNames: (assetInfo) => {
          const name = assetInfo.names?.[0] ?? assetInfo.name ?? '';
          if (name.endsWith('.css')) {
            return 'css/form.css';
          }
          return 'assets/[name][extname]';
        },
      },
    },
  },
});
