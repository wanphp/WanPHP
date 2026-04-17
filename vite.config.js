import {defineConfig} from 'vite';
import path from 'path'

export default defineConfig({
  base: '/assets/',
  publicDir: 'var/public',
  resolve: {
    alias: {
      '@core': path.resolve(__dirname, 'vendor/wanphp/core/js'),
      '@pages': path.resolve(__dirname, 'var/views/pages'),
      '@spa': path.resolve(__dirname, 'var/views/spa'),
      '@plugins': path.resolve(__dirname, 'wanphp/plugins'),
    },
  },
  build: {
    outDir: 'public/assets',
    cssCodeSplit: false,
    emptyOutDir: true,
    assetsInlineLimit: 0,
    assetsDir: '',
    manifest: true,
    rollupOptions: {
      input: {
        app: 'var/resources/js/app.js',
        spa: 'var/resources/js/spa.js'
      }
    },
  }
});