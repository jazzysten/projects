import { defineConfig } from 'vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'

// https://vite.dev/config/
export default defineConfig({
  plugins: [svelte()],
  server: {
    proxy: {
      '/api': {
        target: 'http://jazz/projects/backend/',
        changeOrigin: true
      },
      '/backend': {
        target: 'http://jazz/projects/',
        changeOrigin: true
      }
    }
  }
})