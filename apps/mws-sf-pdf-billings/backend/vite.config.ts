// https://symfony-vite.pentatrion.com/fr/stimulus/symfony-ux#symfony-ux-svelte
import { defineConfig } from 'vite'

import symfonyPlugin from 'vite-plugin-symfony';
// import { svelte } from '@sveltejs/vite-plugin-svelte'
import { sveltekit } from '@sveltejs/kit/vite';

export default defineConfig({
  plugins: [
    // svelte(), 
    sveltekit(),
    symfonyPlugin({
      stimulus: true
    }),
  ],
  build: {
    rollupOptions: {
      input: {
        "app": "./assets/app.js",
      }
    },
  },
});