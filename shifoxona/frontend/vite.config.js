import { defineConfig, loadEnv } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const backendUrl = env.BACKEND_URL || 'http://127.0.0.1:8000';

  // /api so'rovlari Laravel serveriga yuboriladi.
  const proxy = {
    '/api': {
      target: backendUrl,
      changeOrigin: true,
    },
  };

  return {
    plugins: [vue()],
    server: { host: '127.0.0.1', port: 5173, strictPort: true, proxy },
    preview: { host: '127.0.0.1', port: 4173, strictPort: true, proxy },
  };
});
