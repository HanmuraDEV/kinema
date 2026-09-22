// @ts-check
import { defineConfig } from 'astro/config';

import tailwindcss from '@tailwindcss/vite';
import react from '@astrojs/react';

import vue from '@astrojs/vue';

// https://astro.build/config
export default defineConfig({
  vite: {
    plugins: [tailwindcss()],
    // axios es el cliente HTTP de todas las islas: forzamos su bundling
    // para que su hash sea estable y no caiga en 504 "Outdated Optimize Dep"
    optimizeDeps: {
      include: ['axios']
    }
  },

  integrations: [react(), vue()]
});