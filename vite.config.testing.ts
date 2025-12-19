import { defineConfig, type UserConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';
import { fileURLToPath } from 'url';

// Fix __dirname in ESM/TypeScript
const __dirname = fileURLToPath(new URL('.', import.meta.url));

const config: UserConfig = defineConfig({
  server: {
    port: 5174, // Use a dedicated port for testing
    strictPort: true, // Exit if port is already in use
    host: 'localhost', // Ensure Vite dev server is accessible via localhost
  },
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.ts'],
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@': resolve(__dirname, './resources/js'),
    },
  },
});

export default config;
