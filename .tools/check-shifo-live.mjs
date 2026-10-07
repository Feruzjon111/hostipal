import { chromium } from '../shifoxona/frontend/node_modules/@playwright/test/index.mjs';

const browser = await chromium.launch({
  executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
});

try {
  const page = await browser.newPage();
  await page.goto('http://127.0.0.1:5173/login');
  await page.getByLabel('Email manzili').fill('admin1@shifo.uz');
  await page.getByLabel('Parol', { exact: true }).fill('Password123!');
  await page.getByRole('button', { name: 'Tizimga kirish', exact: true }).click();
  await page.waitForURL('http://127.0.0.1:5173/');
  await page.locator('tbody tr').first().waitFor();

  const result = await page.evaluate(async () => {
    const response = await fetch('/api/dashboard', { headers: { Accept: 'application/json' } });
    return { status: response.status, data: await response.json() };
  });
  if (result.status !== 200 || result.data.total < 24) {
    throw new Error('Live dashboard check failed.');
  }
  console.log(JSON.stringify({ frontend: 5173, backend: 8000, login: 'OK', patients: result.data.total }));
} finally {
  await browser.close();
}
