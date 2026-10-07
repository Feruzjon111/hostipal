import { test, expect } from '@playwright/test';

async function login(page, email) {
  await page.goto('/login');
  await page.getByLabel('Email manzili').fill(email);
  await page.getByLabel('Parol', { exact: true }).fill('Password123!');
  await page.getByRole('button', { name: 'Tizimga kirish', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'So‘nggi qabullar' })).toBeVisible();
}

async function removeRecord(page, buttonName) {
  page.once('dialog', (dialog) => dialog.accept());
  await page.getByRole('button', { name: buttonName, exact: true }).click();
}

for (const role of ['admin', 'director', 'doctor']) {
  for (const n of [1, 2]) {
    test(`${role}${n}: login, scoped list, details and logout`, async ({ page }) => {
      const pageErrors = [];
      page.on('pageerror', (error) => pageErrors.push(error.message));
      await login(page, `${role}${n}@shifo.uz`);
      if (role === 'admin' && n === 1) {
        await page.screenshot({ path: '../docs/screenshots/dashboard.png', fullPage: true });
      }
      await page.getByRole('link', { name: 'Bemorlar', exact: true }).click();
      await expect(
        page.getByText(`${role === 'admin' ? 24 : 12} ta yozuvdan`, { exact: false }),
      ).toBeVisible();
      await page.locator('tbody .person').first().click();
      await expect(page.getByRole('heading', { name: 'Shaxsiy ma’lumotlar' })).toBeVisible();
      await expect(page.getByRole('link', { name: 'Tahrirlash', exact: true })).toHaveCount(
        role === 'director' ? 0 : 1,
      );
      await page.getByRole('button', { name: 'Tizimdan chiqish' }).click();
      await expect(page.getByRole('heading', { name: 'Tizimga kirish' })).toBeVisible();
      expect(pageErrors).toEqual([]);
    });
  }
}

test('doctor completes patient creation, search, update and deletion', async ({ page }) => {
  await login(page, 'doctor1@shifo.uz');
  await page.getByRole('link', { name: 'Bemor qo‘shish', exact: true }).click();
  await page.getByLabel('Ism', { exact: true }).fill('E2E');
  await page.getByLabel('Familiya', { exact: true }).fill('Sinov');
  await page.getByLabel('Otasining ismi', { exact: true }).fill('Test o‘g‘li');
  await page.getByLabel('Pasport', { exact: true }).fill('AB7654321');
  await page.getByLabel('JShShIR (PIN)', { exact: true }).fill('01234567890123');
  await page.getByLabel('Viloyat', { exact: true }).selectOption({ label: 'Toshkent shahri' });
  await page
    .getByLabel('Tuman / shahar', { exact: true })
    .selectOption({ label: 'Yunusobod tumani' });
  await page.getByLabel('Manzil', { exact: true }).fill('Sinov ko‘chasi, 10-uy');
  await page
    .getByLabel('Kasallik turi', { exact: true })
    .selectOption({ label: 'O‘tkir respirator infeksiya' });
  await page.getByRole('button', { name: 'Bemorni ro‘yxatga olish' }).click();
  await expect(
    page.getByRole('heading', { name: 'Sinov E2E', exact: true, level: 1 }),
  ).toBeVisible();
  await expect(page.getByText('01234567890123', { exact: true })).toBeVisible();
  await page.getByRole('link', { name: 'Tahrirlash', exact: true }).click();
  await page.getByLabel('Qabul holati').selectOption('completed');
  await page.getByRole('button', { name: 'O‘zgarishlarni saqlash' }).click();
  await expect(page.locator('.patient-summary .badge')).toHaveText('Yakunlangan');
  await page.getByRole('link', { name: 'Bemorlar ro‘yxatiga qaytish' }).click();
  await page.getByLabel('Bemor qidirish').fill('AB7654321');
  await expect(page.locator('tbody tr')).toHaveCount(1);
  await page.locator('tbody .person').click();
  await removeRecord(page, 'O‘chirish');
  await expect(page.getByRole('heading', { name: 'Bemorlar', exact: true })).toBeVisible();
});

test('director adds, edits, inspects and removes own doctor', async ({ page }) => {
  await login(page, 'director1@shifo.uz');
  await page.getByRole('link', { name: 'Shifokorlar', exact: true }).click();
  await page.getByRole('button', { name: 'Shifokor qo‘shish', exact: true }).click();
  const dialog = page.getByRole('dialog');
  await dialog.getByLabel('To‘liq ism').fill('Brauzer Shifokori');
  await dialog.getByLabel('Email', { exact: true }).fill('e2e@shifo.uz');
  await dialog.getByLabel('Parol', { exact: true }).fill('NewPassword!');
  await dialog.getByLabel('Mutaxassislik').fill('Terapevt');
  await dialog.getByRole('button', { name: 'Saqlash', exact: true }).click();
  await expect(page.getByRole('button', { name: 'Brauzer Shifokori e2e@shifo.uz' })).toBeVisible();
  await page.getByRole('button', { name: 'Brauzer Shifokorini tahrirlash' }).click();
  await dialog.getByLabel('Telefon').fill('+998 90 555 55 55');
  await dialog.getByRole('button', { name: 'Saqlash', exact: true }).click();
  await expect(dialog).toHaveCount(0);
  await page.getByRole('button', { name: 'Brauzer Shifokorini ko‘rish' }).click();
  await expect(dialog.getByText('+998 90 555 55 55', { exact: true })).toBeVisible();
  await dialog.getByRole('button', { name: 'Yopish', exact: true }).click();
  await removeRecord(page, 'Brauzer Shifokorini o‘chirish');
  await expect(page.getByRole('button', { name: 'Brauzer Shifokori e2e@shifo.uz' })).toHaveCount(0);
});

test('admin adds hospital and director, then removes both', async ({ page }) => {
  await login(page, 'admin1@shifo.uz');
  await page.getByRole('link', { name: 'Shifoxonalar', exact: true }).click();
  await page.getByRole('button', { name: 'Shifoxona qo‘shish', exact: true }).click();
  const dialog = page.getByRole('dialog');
  await dialog.getByLabel('Shifoxona nomi').fill('Brauzer shifoxonasi');
  await dialog.getByLabel('Viloyat', { exact: true }).selectOption({ label: 'Toshkent shahri' });
  await dialog.getByLabel('Tuman / shahar').selectOption({ label: 'Yunusobod tumani' });
  await dialog.getByLabel('STIR (TIN)').fill('999123456');
  await dialog.getByRole('button', { name: 'Saqlash', exact: true }).click();
  await expect(
    page.getByRole('button', { name: 'Brauzer shifoxonasi', exact: true }),
  ).toBeVisible();
  await page.getByRole('link', { name: 'Rahbarlar', exact: true }).click();
  await page.getByRole('button', { name: 'Rahbar qo‘shish', exact: true }).click();
  await dialog.getByLabel('To‘liq ism').fill('Brauzer Rahbari');
  await dialog.getByLabel('Email', { exact: true }).fill('e2e-director@shifo.uz');
  await dialog.getByLabel('Parol', { exact: true }).fill('NewPassword!');
  await dialog
    .getByLabel('Shifoxona', { exact: true })
    .selectOption({ label: 'Brauzer shifoxonasi' });
  await dialog.getByRole('button', { name: 'Saqlash', exact: true }).click();
  await expect(
    page.getByRole('button', { name: 'Brauzer Rahbari e2e-director@shifo.uz' }),
  ).toBeVisible();
  await removeRecord(page, 'Brauzer Rahbarini o‘chirish');
  await expect(dialog).toHaveCount(0);
  await page.getByRole('link', { name: 'Shifoxonalar', exact: true }).click();
  await removeRecord(page, 'Brauzer shifoxonasini o‘chirish');
  await expect(page.getByRole('button', { name: 'Brauzer shifoxonasi', exact: true })).toHaveCount(
    0,
  );
});

test('mobile layout and CSRF protection', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/login');
  const response = await page.request.post('/api/login', {
    data: { email: 'doctor1@shifo.uz', password: 'Password123!' },
  });
  expect(response.status()).toBe(419);
  await login(page, 'doctor1@shifo.uz');
  await page.getByRole('button', { name: 'Menyuni ochish' }).click();
  await page.getByRole('link', { name: 'Bemorlar', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Bemorlar', exact: true })).toBeVisible();
  await expect(page.locator('tbody tr')).toHaveCount(10);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(
    true,
  );
  await page.screenshot({
    path: '../docs/screenshots/mobile.png',
    fullPage: true,
    animations: 'disabled',
  });
});
