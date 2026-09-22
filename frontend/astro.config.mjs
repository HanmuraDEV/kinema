// @ts-check
import { defineConfig } from 'astro/config';

import tailwindcss from '@tailwindcss/vite';
import react from '@astrojs/react';

import vue from '@astrojs/vue';

import node from '@astrojs/node';

// https://astro.build/config
export default defineConfig({
  // App dinámica (perfiles, listas por slug): SSR en dev y build.
  // El prerender estático solo servía las rutas de getStaticPaths (404 al resto).
  output: 'server',
  adapter: node({
    mode: 'standalone'
  }),
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