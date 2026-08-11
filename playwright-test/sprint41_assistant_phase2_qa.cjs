const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();
  const base = 'http://127.0.0.1:8000';

  const results = {
    passed: [],
    failed: [],
    bugs: []
  };

  async function goto(path) {
    await page.goto(base + path, { waitUntil: 'networkidle', timeout: 30000 });
  }

  async function screenshot(name) {
    await page.screenshot({ path: `sprint41_${name}.png` });
  }

  // ---------- Phase 1: Teacher Login ----------
  console.log('=== Teacher Login ===');
  await goto('/login');
  await page.fill('input[name="mobile"]', 'teacher@bayan.local');
  await page.fill('input[name="password"]', 'Teacher@123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(2000);
  await screenshot('teacher_dashboard');
  if (page.url().includes('/teacher/dashboard')) {
    results.passed.push('Teacher login');
  } else {
    results.failed.push('Teacher login');
  }

  // ---------- Phase 3: Assistant Logout ----------
  console.log('=== Assistant Logout (skip, not created yet) ===');
  // Note: we are still teacher, need to create assistant then logout teacher to login as assistant.
  // We'll continue as teacher then go to assistant login.

  // ---------- Phase 4: Assistant Login ----------
  console.log('=== Assistant Login ===');
  await goto('/assistant/login');
  await page.waitForTimeout(2000);
  await screenshot('assistant_login_page');
  await page.fill('input[name="username"]', 'assistant@bayan.local');
  await page.fill('input[name="password"]', 'Assistant@123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);
  await screenshot('assistant_dashboard');
  if (page.url().includes('/assistant/dashboard')) {
    results.passed.push('Assistant login');
  } else {
    results.failed.push('Assistant login');
  }

  // ---------- Dashboard cards verification ----------
  console.log('=== Dashboard Cards ===');
  const expectedCards = [
    'تسجيل الحضور',
    'إدارة الطلاب',
    'مشاهدة المجموعات',
    'إدارة الدروس',
    'رفع الموارد',
    'إدارة الحجوزات',
    'إدارة الامتحانات',
    'الإشعارات',
    'الملف الشخصي'
  ];
  for (const card of expectedCards) {
    const el = page.locator(`text=${card}`);
    if (await el.count() > 0) {
      results.passed.push(`Dashboard card: ${card}`);
    } else {
      results.failed.push(`Dashboard card: ${card}`);
    }
  }

  // ---------- Workflow: Groups ----------
  console.log('=== Groups ===');
  await goto('/assistant/groups');
  await page.waitForTimeout(2000);
  await screenshot('groups');
  if (page.url().includes('/assistant/groups')) results.passed.push('Groups page');
  else results.failed.push('Groups page');

  // ---------- Workflow: Attendance ----------
  console.log('=== Attendance ===');
  await goto('/assistant/attendance');
  await page.waitForTimeout(2000);
  await screenshot('attendance');
  if (page.url().includes('/assistant/attendance')) results.passed.push('Attendance page');
  else results.failed.push('Attendance page');
  await goto('/assistant/attendance/create');
  await page.waitForTimeout(2000);
  await screenshot('attendance_create');
  if (page.url().includes('/assistant/attendance/create')) results.passed.push('Attendance create page');
  else results.failed.push('Attendance create page');

  // ---------- Workflow: Students ----------
  console.log('=== Students ===');
  await goto('/assistant/students');
  await page.waitForTimeout(2000);
  await screenshot('students');
  if (page.url().includes('/assistant/students')) results.passed.push('Students page');
  else results.failed.push('Students page');

  // ---------- Workflow: Lessons ----------
  console.log('=== Lessons ===');
  await goto('/assistant/lessons');
  await page.waitForTimeout(2000);
  await screenshot('lessons');
  if (page.url().includes('/assistant/lessons')) results.passed.push('Lessons page');
  else results.failed.push('Lessons page');
  await goto('/assistant/lessons/create');
  await page.waitForTimeout(2000);
  await screenshot('lessons_create');
  if (page.url().includes('/assistant/lessons/create')) results.passed.push('Lessons create page');
  else results.failed.push('Lessons create page');

  // ---------- Workflow: Resources ----------
  console.log('=== Resources ===');
  await goto('/assistant/resources/create');
  await page.waitForTimeout(2000);
  await screenshot('resources_create');
  if (page.url().includes('/assistant/resources/create')) results.passed.push('Resources create page');
  else results.failed.push('Resources create page');

  // ---------- Workflow: Reservations ----------
  console.log('=== Reservations ===');
  await goto('/assistant/reservations/create');
  await page.waitForTimeout(2000);
  await screenshot('reservations_create');
  if (page.url().includes('/assistant/reservations/create')) results.passed.push('Reservations create page');
  else results.failed.push('Reservations create page');

  // ---------- Workflow: Exams ----------
  console.log('=== Exams ===');
  await goto('/assistant/exams');
  await page.waitForTimeout(2000);
  await screenshot('exams');
  if (page.url().includes('/assistant/exams')) results.passed.push('Exams page');
  else results.failed.push('Exams page');

  // ---------- Workflow: Question Bank ----------
  console.log('=== Question Bank ===');
  await goto('/assistant/question-bank');
  await page.waitForTimeout(2000);
  await screenshot('question_bank');
  if (page.url().includes('/assistant/question-bank')) results.passed.push('Question Bank page');
  else results.failed.push('Question Bank page');

  // ---------- Workflow: Notifications ----------
  console.log('=== Notifications ===');
  await goto('/assistant/notifications');
  await page.waitForTimeout(2000);
  await screenshot('notifications');
  if (page.url().includes('/assistant/notifications')) results.passed.push('Notifications page');
  else results.failed.push('Notifications page');

  // ---------- Workflow: Profile ----------
  console.log('=== Profile ===');
  await goto('/assistant/profile');
  await page.waitForTimeout(2000);
  await screenshot('profile');
  if (page.url().includes('/assistant/profile')) results.passed.push('Profile page');
  else results.failed.push('Profile page');

  // ---------- Summary ----------
  console.log('=== RESULTS ===');
  console.log('Passed:', results.passed.length, results.passed);
  console.log('Failed:', results.failed.length, results.failed);
  console.log('Bugs:', results.bugs.length, results.bugs);

  await browser.close();
})();