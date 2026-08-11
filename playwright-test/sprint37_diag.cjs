const { chromium } = require('playwright');
const fs = require('fs');

const BASE = 'http://127.0.0.1:8001';

async function run() {
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext();
  const page = await ctx.newPage();

  const result = { steps: [], errors: [] };

  try {
    // Step 1: Teacher login
    console.log('Step 1: Teacher login...');
    await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForSelector('input[name="mobile"]', { timeout: 15000 });
    await page.fill('input[name="mobile"]', '01000000001');
    await page.fill('input[name="password"]', 'Teacher@123');
    await page.click('button:has-text("تسجيل الدخول")');
    await page.waitForURL('**/teacher/dashboard', { timeout: 60000 });
    console.log('  ✓ Teacher logged in');
    result.steps.push('teacher_login: OK');

    // Step 2: Create assistant
    console.log('Step 2: Create assistant...');
    await page.goto(`${BASE}/teacher/assistants/create`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForSelector('input[name="username"]', { timeout: 15000 });

    const ts = Date.now();
    const username = 'diag_' + ts;
    await page.fill('input[name="name"]', 'Diag Assistant');
    await page.fill('input[name="username"]', username);
    await page.fill('input[name="password"]', 'Assistant@123');
    const phoneInput = page.locator('input[name="phone"]');
    if (await phoneInput.count() > 0) await phoneInput.fill('01000000099');

    const perms = ['can_take_attendance','can_manage_students','can_upload_resources','can_manage_reservations','can_restore_students','can_view_groups','can_manage_lessons','can_manage_exams'];
    for (const p of perms) {
      const cb = page.locator(`input[name="${p}"]`);
      if (await cb.count() > 0) { try { await cb.check({ force: true }); } catch(e){} }
    }

    await page.click('button[type="submit"]', { timeout: 30000 });
    await page.waitForLoadState('domcontentloaded', { timeout: 30000 });
    console.log('  ✓ Assistant created:', username);
    result.assistant_username = username;
    result.steps.push('create_assistant: OK');

    // Step 3: Teacher logout
    console.log('Step 3: Teacher logout...');
    // Try POST logout
    const logoutUrl = `${BASE}/logout`;
    const resp = await page.request.post(logoutUrl, { maxRedirects: 5 });
    console.log('  Logout status:', resp.status());
    result.steps.push('teacher_logout: OK (status ' + resp.status() + ')');

    // Step 4: Assistant login page
    console.log('Step 4: Load assistant login page...');
    await page.goto(`${BASE}/assistant/login`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    const pageUrl = page.url();
    const pageTitle = await page.title();
    const pageContent = await page.content();
    console.log('  URL:', pageUrl);
    console.log('  Title:', pageTitle);
    console.log('  Has username input:', pageContent.includes('name="username"'));
    console.log('  Content length:', pageContent.length);

    // Check for exception indicators
    const hasException = pageContent.includes('exception') || pageContent.includes('Exception') || pageContent.includes('Whoops') || pageContent.includes('Stack trace') || pageContent.includes('Ignition');
    console.log('  Has exception text:', hasException);

    if (hasException) {
      // Save full content
      fs.writeFileSync('tmp_assistant_login_exception.html', pageContent);
      console.log('  Exception page saved to tmp_assistant_login_exception.html');
      result.errors.push('Exception on assistant login page');
    }

    result.steps.push('assistant_login_page: ' + (pageContent.includes('name="username"') ? 'OK' : 'MISSING_INPUT'));

    // Step 5: Fill login form and submit
    console.log('Step 5: Submit assistant login...');
    if (pageContent.includes('name="username"')) {
      await page.fill('input[name="username"]', username);
      await page.fill('input[name="password"]', 'Assistant@123');
      await page.click('button[type="submit"]:has-text("تسجيل الدخول")', { timeout: 15000 });

      // Wait for navigation or error
      await page.waitForTimeout(5000);
      const afterUrl = page.url();
      const afterContent = await page.content();
      console.log('  After login URL:', afterUrl);
      console.log('  After login content length:', afterContent.length);

      const afterHasException = afterContent.includes('exception') || afterContent.includes('Exception') || afterContent.includes('Whoops') || afterContent.includes('Stack trace') || afterContent.includes('Ignition') || afterContent.includes('Fatal');
      console.log('  After login has exception:', afterHasException);

      if (afterHasException) {
        fs.writeFileSync('tmp_assistant_dashboard_exception.html', afterContent);
        console.log('  Exception page saved to tmp_assistant_dashboard_exception.html');
        result.errors.push('Exception after assistant login POST');
      }

      if (afterUrl.includes('assistant/dashboard')) {
        console.log('  ✓ Assistant login SUCCESS');
        result.steps.push('assistant_login: OK');
      } else {
        console.log('  ✗ Assistant login FAILED - redirected to:', afterUrl);
        result.steps.push('assistant_login: FAILED');
        fs.writeFileSync('tmp_assistant_login_result.html', afterContent);
      }
    }

  } catch (err) {
    console.error('ERROR:', err.message);
    result.errors.push(err.message);
    try {
      const content = await page.content();
      fs.writeFileSync('tmp_diag_error.html', content);
      const url = page.url();
      console.log('  Error page URL:', url);
      console.log('  Error page saved to tmp_diag_error.html');
    } catch(e) {}
  } finally {
    await browser.close();
  }

  fs.writeFileSync('tmp_diag_result.json', JSON.stringify(result, null, 2));
  console.log('\nResult saved to tmp_diag_result.json');
}

run().catch(e => { console.error(e); process.exit(1); });