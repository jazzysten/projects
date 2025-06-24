import { defineConfig } from 'vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'
import autoprefixer from 'autoprefixer'
import path from 'path'

export default defineConfig({
  plugins: [svelte()],
  server: {
    proxy: {
      '/api': {
        target: 'http://jazz/projects/backend/public/',
        changeOrigin: true
      },
      '/backend': {
        target: 'http://jazz/projects/',
        changeOrigin: true
      }
    },
    fs: { allow: ['.'] },
    historyApiFallback: true
  },
  preview: {
    historyApiFallback: true
  },
  build: {
    outDir: 'public',
    emptyOutDir: true,
    rollupOptions: {
      output: {
        entryFileNames: 'assets/[name].js',
        chunkFileNames: 'assets/[name].js',
        assetFileNames: 'assets/[name][extname]'
      }
    }
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src')
    }
  },
  base: './'
})