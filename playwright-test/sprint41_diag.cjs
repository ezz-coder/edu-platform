const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext({ viewport: { width: 1280, height: 700 } });
  const page = await context.newPage();
  const base = 'http://127.0.0.1:8000';

  async function goto(path) {
    await page.goto(base + path, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForTimeout(1000);
    await page.screenshot({ path: `sprint41_${path.replace(/\//g, '_')}.png` });
  }

  // 1) Teacher login
  await goto('/login');
  await page.fill('input[name="mobile"]', 'teacher@bayan.local');
  await page.fill('input[name="password"]', 'Teacher@123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(2000);

  // 2) Go to assistant login
  await goto('/assistant/login');
  await page.fill('input[name="username"]', 'assistant@bayan.local');
  await page.fill('input[name="password"]', 'Assistant@123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);

  // 3) Visit key pages
  await goto('/assistant/dashboard');
  await goto('/assistant/groups');
  await goto('/assistant/attendance');
  await goto('/assistant/attendance/create');
  await goto('/assistant/students');
  await goto('/assistant/lessons');
  await goto('/assistant/lessons/create');
  await goto('/assistant/resources/create');
  await goto('/assistant/reservations/create');
  await goto('/assistant/exams');
  await goto('/assistant/question-bank');
  await goto('/assistant/notifications');
  await goto('/assistant/profile');

  await browser.close();
})();