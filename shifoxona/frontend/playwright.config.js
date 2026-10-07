import { defineConfig } from '@playwright/test';
import { existsSync } from 'node:fs';
import { resolve } from 'node:path';

const chromePath = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
let executablePath = process.env.SHIFO_BROWSER;
if (!executablePath && process.platform === 'win32' && existsSync(chromePath)) {
  executablePath = chromePath;
}

export default defineConfig({
  testDir: './tests/e2e',
  workers: 1,
  timeout: 30000,
  reporter: 'list',
  globalSetup: './tests/e2e/setup.js',
  use: {
    baseURL: 'http://127.0.0.1:5174',
    browserName: 'chromium',
    launchOptions: { executablePath },
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
  },
  webServer: [
    {
      command:
        'php -S 127.0.0.1:8001 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php',
      cwd: resolve('../backend/public'),
      url: 'http://127.0.0.1:8001/up',
      stdout: 'ignore',
      stderr: 'ignore',
      env: {
        APP_ENV: 'local',
        DB_CONNECTION: 'sqlite',
        DB_DATABASE: resolve('../backend/database/e2e.sqlite'),
        SESSION_DRIVER: 'database',
        CACHE_STORE: 'database',
      },
    },
    {
      command: 'npm run preview -- --port 5174',
      url: 'http://127.0.0.1:5174',
      stdout: 'ignore',
      stderr: 'ignore',
      env: { BACKEND_URL: 'http://127.0.0.1:8001' },
    },
  ],
});
