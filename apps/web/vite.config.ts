import path from 'path';
import fs from 'node:fs';
import { defineConfig, type Plugin } from 'vite';
import react from '@vitejs/plugin-react';

function technicianHtmlPlugin(): Plugin {
  return {
    name: 'technician-html-rename',
    closeBundle() {
      const techHtmlPath = path.resolve(__dirname, 'dist/technician/technician.html');
      const targetIndexPath = path.resolve(__dirname, 'dist/technician/index.html');
      if (fs.existsSync(techHtmlPath)) {
        fs.copyFileSync(techHtmlPath, targetIndexPath);
      }
    },
  };
}

export default defineConfig(({ mode }) => {
  const isTechnician = mode === 'technician' || process.env.VITE_APP_TARGET === 'technician';
  const outDir = isTechnician ? 'dist/technician' : 'dist/admin';
  const inputHtml = isTechnician
    ? path.resolve(__dirname, 'technician.html')
    : path.resolve(__dirname, 'index.html');

  return {
    plugins: [
      react(),
      isTechnician && technicianHtmlPlugin(),
    ].filter(Boolean),
    resolve: {
      alias: {
        '@': path.resolve(__dirname, './src'),
      },
    },
    build: {
      outDir,
      emptyOutDir: true,
      target: 'es2022',
      chunkSizeWarningLimit: 1000,
      sourcemap: false,
      rollupOptions: {
        input: inputHtml,
        maxParallelFileOps: 2,
        output: {
          manualChunks(id) {
            if (id.includes('invoiceAssets')) {
              return 'invoice-assets';
            }
            if (id.includes('node_modules')) {
              if (id.includes('echarts')) {
                return 'vendor-echarts';
              }
              if (id.includes('lucide-react')) {
                return 'vendor-icons';
              }
              if (id.includes('xlsx')) {
                return 'vendor-xlsx';
              }
              if (id.includes('react-hook-form') || id.includes('@hookform')) {
                return 'vendor-forms';
              }
              if (id.includes('react-router-dom') || id.includes('@remix-run')) {
                return 'vendor-router';
              }
              if (id.includes('react') || id.includes('react-dom')) {
                return 'vendor-react';
              }
              if (id.includes('@tanstack')) {
                return 'vendor-tanstack';
              }
              return 'vendor-common';
            }
          },
        },
      },
    },
    server: {
      host: '0.0.0.0',
      port: 3000,
      proxy: {
        '/api': {
          target: 'http://127.0.0.1:4000',
          changeOrigin: true,
        },
        '/health': {
          target: 'http://127.0.0.1:4000',
          changeOrigin: true,
        },
        '/ready': {
          target: 'http://127.0.0.1:4000',
          changeOrigin: true,
        },
      },
    },
    // @ts-ignore
    test: {
      globals: true,
      environment: 'jsdom',
      pool: 'forks',
      poolOptions: {
        forks: {
          singleFork: true,
          execArgv: ['--max-old-space-size=4096'],
        },
      },
    },
  };
});
