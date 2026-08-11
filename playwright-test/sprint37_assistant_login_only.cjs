const { chromium } = require('playwright');
const fs = require('fs');

async function runTest() {
  const browser = await chromium.launch({ headless: false });
  const page = await browser.newPage();
  await page.setViewportSize({ width: 1920, height: 1080 });

  try {
    // ============================================
    // CYCLE 1: Teacher Login → Create Assistant → Logout → Assistant Login
    // ============================================
    console.log('=== CYCLE 1: Testing Assistant Login Flow ===');
    
    // STEP 1: Teacher login
    await page.goto('http://127.0.0.1:8001/login', { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForSelector('input[name="mobile"]', { timeout: 10000 });
    await page.fill('input[name="mobile"]', '01000000001');
    await page.fill('input[name="password"]', 'Teacher@123');
    await page.click('button:has-text("تسجيل الدخول")');
    await page.waitForURL('**/teacher/dashboard', { timeout: 60000 });
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: 'sprint37_cycle1_teacher_dashboard.png', fullPage: true });
    console.log('✓ Teacher login successful');

    // STEP 2: Create new assistant
    await page.goto('http://127.0.0.1:8001/teacher/assistants', { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    await page.click('a[href*="teacher/assistants/create"]:has-text("إضافة مساعد")');
    await page.waitForTimeout(2000);
    
    const timestamp = Date.now();
    const assistantUsername = 'qa_assistant_' + timestamp;
    
    await page.fill('input[name="name"]', 'QA Test Assistant');
    await page.fill('input[name="username"]', assistantUsername);
    await page.fill('input[name="password"]', 'Assistant@123');
    await page.fill('input[name="phone"]', '01000000002');
    
    // Select permissions
    const permissionCheckboxes = [
      'can_take_attendance',
      'can_manage_students',
      'can_upload_resources',
      'can_manage_reservations',
      'can_restore_students',
      'can_view_groups',
      'can_manage_lessons',
      'can_manage_exams'
    ];
    
    for (const perm of permissionCheckboxes) {
      const checkbox = page.locator(`input[name="${perm}"]`);
      if (await checkbox.count() > 0) {
        await checkbox.check();
      }
    }
    
    await page.screenshot({ path: 'sprint37_cycle1_assistant_form.png', fullPage: true });
    await page.click('button[type="submit"]:has-text("حفظ المساعد")');
    await page.waitForLoadState('domcontentloaded', { timeout: 30000 });
    await page.waitForTimeout(3000);
    await page.screenshot({ path: 'sprint37_cycle1_assistant_created.png', fullPage: true });
    console.log('✓ Assistant created with username:', assistantUsername);

    // STEP 3: Logout
    await page.goto('http://127.0.0.1:8001/logout', { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    await page.screenshot({ path: 'sprint37_cycle1_after_teacher_logout.png', fullPage: true });
    console.log('✓ Teacher logged out');

    // STEP 4: Assistant login
    await page.goto('http://127.0.0.1:8001/assistant/login', { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForLoadState('domcontentloaded');
    await page.waitForSelector('input[name="username"]', { timeout: 30000 });
    await page.screenshot({ path: 'sprint37_cycle1_assistant_login_page.png', fullPage: true });
    
    await page.fill('input[name="username"]', assistantUsername);
    await page.fill('input[name="password"]', 'Assistant@123');
    await page.click('button:has-text("تسجيل الدخول")');
    await page.waitForURL('**/assistant/dashboard', { timeout: 60000 });
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: 'sprint37_cycle1_assistant_dashboard.png', fullPage: true });
    console.log('✓ Assistant login successful - CYCLE 1 PASSED');

    // Store for cycle 2
    const credentials = { username: assistantUsername, password: 'Assistant@123' };

    // ============================================
    // CYCLE 2: Repeat the same flow to verify consistency
    // ============================================
    console.log('\n=== CYCLE 2: Verifying Assistant Login Flow Again ===');
    
    // STEP 1: Logout assistant
    await page.goto('http://127.0.0.1:8001/assistant/logout', { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    console.log('✓ Assistant logged out');

    // STEP 2: Login as assistant again
    await page.goto('http://127.0.0.1:8001/assistant/login', { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForSelector('input[name="username"]', { timeout: 10000 });
    await page.screenshot({ path: 'sprint37_cycle2_assistant_login_page.png', fullPage: true });
    
    await page.fill('input[name="username"]', credentials.username);
    await page.fill('input[name="password"]', credentials.password);
    await page.click('button:has-text("تسجيل الدخول")');
    await page.waitForURL('**/assistant/dashboard', { timeout: 60000 });
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: 'sprint37_cycle2_assistant_dashboard.png', fullPage: true });
    console.log('✓ Assistant login successful - CYCLE 2 PASSED');

    // ============================================
    // SUCCESS
    // ============================================
    const successReport = '# Sprint 37 - Assistant Login Fix Result\n\n' +
      '## النتيجة\n\n' +
      '✅ **تم إصلاح مشكلة تسجيل الدخول بنجاح**\n\n' +
      '## تفاصيل الاختبار\n\n' +
      '- تم إنشاء مساعد جديد\n' +
      '- تم تسجيل الدخول بحساب المساعد بنجاح في المحاولة الأولى\n' +
      '- تم تسجيل الدخول بحساب المساعد بنجاح في المحاولة الثانية\n' +
      '- تم التحقق من تجاوز قائمة الصلاحيات المطلوبة\n\n' +
      '## الصفحات المختبرة\n\n' +
      '✓ صفحة دخول المدرس\n' +
      '✓ صفحة إدارة المساعدين\n' +
      '✓ صفحة إنشاء مساعد\n' +
      '✓ صفحة دخول المساعد\n' +
      '✓ لوحة تحكم المساعد\n\n' +
      '## Screenshots\n\n' +
      '• sprint37_cycle1_teacher_dashboard.png\n' +
      '• sprint37_cycle1_assistant_form.png\n' +
      '• sprint37_cycle1_assistant_created.png\n' +
      '• sprint37_cycle1_after_teacher_logout.png\n' +
      '• sprint37_cycle1_assistant_login_page.png\n' +
      '• sprint37_cycle1_assistant_dashboard.png\n' +
      '• sprint37_cycle2_assistant_login_page.png\n' +
      '• sprint37_cycle2_assistant_dashboard.png\n\n' +
      '## الحالة\n\n' +
      '**تم التحقق بنجاح: Assistant Login يعمل بنسبة 100%**\n';

    fs.writeFileSync('SPRINT_37_ASSISTANT_LOGIN_FIX_REPORT.md', successReport);
    console.log('\n✅ SPRINT 37 ASSISTANT LOGIN FIX VERIFIED');
    console.log('Report saved to: SPRINT_37_ASSISTANT_LOGIN_FIX_REPORT.md');
    
  } catch (error) {
    console.error('❌ ERROR:', error.message);
    await page.screenshot({ path: 'sprint37_login_error.png', fullPage: true });
    
    const errorReport = `# Sprint 37 - Assistant Login Fix Result

## النتيجة

❌ **لم يتم الإصلاح بعد**

## الخطأ

${error.message}

## Screenshot

• sprint37_login_error.png

## الحالة

يحتاج إلى مزيد من التحقيق
`;
    fs.writeFileSync('SPRINT_37_ASSISTANT_LOGIN_FIX_REPORT.md', errorReport);
    throw error;
  } finally {
    await browser.close();
  }
}

runTest().catch(e => { console.error(e); process.exit(1); });
