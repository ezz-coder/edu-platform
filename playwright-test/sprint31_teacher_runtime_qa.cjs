/**
 * Sprint 31 — Teacher Runtime QA (Phase 1)
 * Real browser-based testing of all teacher features.
 * Browser is the single source of truth.
 */
const { chromium } = require('playwright');
const fs = require('fs');

const BASE = 'http://127.0.0.1:8000';
const RESULTS = [];
const BUGS = [];
const NOT_IMPLEMENTED = [];

let bugCounter = 0;

function log(msg) {
  console.log(`[${new Date().toISOString()}] ${msg}`);
}

function addResult(page, status, details) {
  RESULTS.push({ page, status, details, timestamp: new Date().toISOString() });
  const icon = status === 'PASS' ? '✅' : status === 'FAIL' ? '❌' : '⚠️';
  log(`${icon} ${page}: ${status} — ${details}`);
}

function addBug(page, description, error = null) {
  bugCounter++;
  const bug = { id: `BUG-${bugCounter}`, page, description, error: error ? String(error).substring(0, 500) : null };
  BUGS.push(bug);
  log(`🐛 ${bug.id} on ${page}: ${description}${error ? ' | ' + bug.error : ''}`);
  return bug;
}

function addNotImplemented(feature) {
  NOT_IMPLEMENTED.push(feature);
  log(`🚫 NOT IMPLEMENTED: ${feature}`);
}

async function safeGoto(page, url) {
  try {
    const resp = await page.goto(url, { waitUntil: 'networkidle', timeout: 15000 });
    return resp;
  } catch (e) {
    return null;
  }
}

async function getPageText(page) {
  return await page.evaluate(() => document.body ? document.body.innerText : '');
}

async function hasErrorOnPage(page) {
  const text = await getPageText(page);
  const errorPatterns = [
    'Fatal error', 'Parse error', 'Undefined variable', 'Undefined index',
    'Method Illuminate\\', 'Call to undefined', 'Class "App\\',
    'could not be converted', 'ArgumentCountError', 'TypeError',
    'SQLSTATE', 'syntax error, unexpected', 'ViewException',
    'array_key_exists', 'Attempt to read property',
  ];
  for (const p of errorPatterns) {
    if (text.includes(p)) return p;
  }
  return null;
}

async function loginAsTeacher(page) {
  log('🔐 Logging in as teacher...');
  await safeGoto(page, `${BASE}/login`);
  await page.waitForLoadState('domcontentloaded');
  await page.waitForTimeout(1500);

  // Login form uses "mobile" field, not "email"
  const mobileInput = await page.$('input[name="mobile"]');
  const passwordInput = await page.$('input[name="password"]');

  if (!mobileInput || !passwordInput) {
    addBug('Login', 'Login form fields not found (mobile/password)');
    return false;
  }

  await mobileInput.fill('01000000001');
  await passwordInput.fill('Teacher@123');

  // Submit form - use Promise to handle navigation
  const submitBtn = await page.$('button[type="submit"]');
  if (submitBtn) {
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 15000 }).catch(() => {}),
      submitBtn.click(),
    ]);
  } else {
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 15000 }).catch(() => {}),
      passwordInput.press('Enter'),
    ]);
  }

  await page.waitForLoadState('domcontentloaded');
  await page.waitForTimeout(2000);
  const url = page.url();
  const text = await getPageText(page);

  if (url.includes('/teacher/dashboard') || url.includes('/teacher')) {
    log('✅ Login successful — redirected to teacher area');
    return true;
  }

  if (text.includes('هذه الحسابات') || text.includes('accounts') || text.includes('تبديل')) {
    log('ℹ️ Login may require account selection');
    return true;
  }

  addBug('Login', `Login failed — URL: ${url}`, text.substring(0, 300));
  return false;
}

async function testPageLoad(page, name, url) {
  log(`📄 Testing page load: ${name} (${url})`);
  const resp = await safeGoto(page, url);
  await page.waitForTimeout(800);

  if (!resp) {
    addBug(name, `Page failed to load: ${url}`);
    addResult(name, 'FAIL', 'Page did not load');
    return false;
  }

  const status = resp.status();
  if (status >= 500) {
    addBug(name, `Server error ${status} on ${url}`);
    addResult(name, 'FAIL', `HTTP ${status}`);
    return false;
  }

  if (status === 403) {
    addResult(name, 'WARN', `HTTP 403 — access denied (may be expected)`);
    return false;
  }

  if (status === 404) {
    addBug(name, `404 Not Found: ${url}`);
    addResult(name, 'FAIL', 'HTTP 404');
    return false;
  }

  const errPattern = await hasErrorOnPage(page);
  if (errPattern) {
    addBug(name, `PHP/View error detected: ${errPattern}`, await getPageText(page));
    addResult(name, 'FAIL', `Error: ${errPattern}`);
    return false;
  }

  addResult(name, 'PASS', `HTTP ${status}`);
  return true;
}

async function testDashboard(page) {
  const ok = await testPageLoad(page, 'Teacher Dashboard', `${BASE}/teacher/dashboard`);
  if (!ok) return;

  // Check statistics cards
  const text = await getPageText(page);
  const expectedItems = ['المجموعات', 'الطلاب', 'الامتحانات', 'الحضور'];
  let found = 0;
  for (const item of expectedItems) {
    if (text.includes(item)) found++;
  }
  addResult('Dashboard Stats', found >= 2 ? 'PASS' : 'WARN', `Found ${found}/${expectedItems.length} expected items`);

  // Test all navigation links from dashboard
  const links = await page.$$('a[href*="/teacher/"]');
  const navLinks = [];
  for (const link of links) {
    const href = await link.getAttribute('href');
    if (href && !navLinks.includes(href) && !href.includes('logout')) {
      navLinks.push(href);
    }
  }
  log(`🔗 Found ${navLinks.length} unique teacher navigation links`);
  addResult('Dashboard Navigation', 'PASS', `${navLinks.length} links found`);
}

async function testGroupsCRUD(page) {
  log('📚 Testing Groups CRUD...');

  // List
  await testPageLoad(page, 'Groups List', `${BASE}/teacher/groups`);

  // Create form
  const createOk = await testPageLoad(page, 'Groups Create', `${BASE}/teacher/groups/create`);
  if (createOk) {
    // Test form validation - submit empty
    const form = await page.$('form');
    if (form) {
      const submitBtn = await form.$('button[type="submit"], input[type="submit"]');
      if (submitBtn) {
        await submitBtn.click();
        await page.waitForTimeout(1500);
        const text = await getPageText(page);
        if (text.includes('مطلوب') || text.includes('required') || text.includes('يجب')) {
          addResult('Groups Validation', 'PASS', 'Validation works on empty submit');
        } else {
          addResult('Groups Validation', 'WARN', 'No validation message on empty submit');
        }
      }
    }
  }

  // Try to create a real group
  await safeGoto(page, `${BASE}/teacher/groups/create`);
  await page.waitForTimeout(800);

  const titleInput = await page.$('input[name="title"]');
  if (titleInput) {
    await titleInput.fill('QA Test Group - Sprint31');
    const descInput = await page.$('textarea[name="description"], input[name="description"]');
    if (descInput) await descInput.fill('Test group for Sprint 31 QA');
    const dayInput = await page.$('select[name="day"], input[name="day"]');
    if (dayInput) {
      const tag = await dayInput.evaluate(el => el.tagName);
      if (tag === 'SELECT') await dayInput.selectOption({ index: 1 });
      else await dayInput.fill('Saturday');
    }
    const startInput = await page.$('input[name="start_time"]');
    if (startInput) await startInput.fill('10:00');
    const endInput = await page.$('input[name="end_time"]');
    if (endInput) await endInput.fill('12:00');
    const genderSelect = await page.$('select[name="gender_type"]');
    if (genderSelect) await genderSelect.selectOption({ index: 1 });
    const gradeInput = await page.$('input[name="grade_level"], select[name="grade_level"]');
    if (gradeInput) {
      const tag = await gradeInput.evaluate(el => el.tagName);
      if (tag === 'SELECT') await gradeInput.selectOption({ index: 1 });
      else await gradeInput.fill('Grade 10');
    }
    const maxInput = await page.$('input[name="max_students"]');
    if (maxInput) await maxInput.fill('20');

    const submitBtn = await page.$('button[type="submit"]');
    if (submitBtn) {
      await submitBtn.click();
      await page.waitForTimeout(2000);
      const url = page.url();
      if (url.includes('/teacher/groups') && !url.includes('create')) {
        addResult('Groups Create', 'PASS', 'Group created successfully');
      } else {
        const err = await hasErrorOnPage(page);
        if (err) addBug('Groups Create', `Error after submit: ${err}`);
        addResult('Groups Create', err ? 'FAIL' : 'WARN', `URL after submit: ${url}`);
      }
    }
  } else {
    addResult('Groups Create', 'WARN', 'Title input not found');
  }

  // Show first group
  await safeGoto(page, `${BASE}/teacher/groups`);
  await page.waitForTimeout(800);
  const showLink = await page.$('a[href*="/teacher/groups/"]:not([href*="create"]):not([href*="edit"]):not([href*="cards"])');
  if (showLink) {
    const href = await showLink.getAttribute('href');
    await testPageLoad(page, 'Groups Show', `${BASE}${href}`);
  } else {
    addResult('Groups Show', 'WARN', 'No group show link found');
  }

  // Edit
  await safeGoto(page, `${BASE}/teacher/groups`);
  await page.waitForTimeout(800);
  const editLink = await page.$('a[href*="/teacher/groups/"][href*="/edit"]');
  if (editLink) {
    const href = await editLink.getAttribute('href');
    await testPageLoad(page, 'Groups Edit', `${BASE}${href}`);
  } else {
    addResult('Groups Edit', 'WARN', 'No group edit link found');
  }
}

async function testStudents(page) {
  log('👥 Testing Students...');

  await testPageLoad(page, 'Students List', `${BASE}/teacher/students`);

  // Test search
  await safeGoto(page, `${BASE}/teacher/students`);
  await page.waitForTimeout(800);
  const searchInput = await page.$('input[name="search"], input[type="search"], input[placeholder*="بحث"], input[placeholder*="search"]');
  if (searchInput) {
    await searchInput.fill('test');
    await searchInput.press('Enter');
    await page.waitForTimeout(1500);
    addResult('Students Search', 'PASS', 'Search submitted');
  } else {
    addResult('Students Search', 'WARN', 'Search input not found');
  }

  // Test filter
  await safeGoto(page, `${BASE}/teacher/students`);
  await page.waitForTimeout(800);
  const filterSelect = await page.$('select[name="group_id"], select[name="status"]');
  if (filterSelect) {
    await filterSelect.selectOption({ index: 1 });
    await page.waitForTimeout(1500);
    addResult('Students Filter', 'PASS', 'Filter applied');
  } else {
    addResult('Students Filter', 'WARN', 'Filter select not found');
  }

  // Show student
  await safeGoto(page, `${BASE}/teacher/students`);
  await page.waitForTimeout(800);
  const showLink = await page.$('a[href*="/teacher/students/"]:not([href*="move"]):not([href*="remove"]):not([href*="restore"]):not([href*="transfer"]):not([href*="card"]):not([href*="history"]):not([href*="bulk"])');
  if (showLink) {
    const href = await showLink.getAttribute('href');
    await testPageLoad(page, 'Students Show', `${BASE}${href}`);
  } else {
    addResult('Students Show', 'WARN', 'No student show link found');
  }
}

async function testAttendance(page) {
  log('📋 Testing Attendance...');

  await testPageLoad(page, 'Attendance List', `${BASE}/teacher/attendance`);
  await testPageLoad(page, 'Attendance Create', `${BASE}/teacher/attendance/create`);

  // Show existing session
  await safeGoto(page, `${BASE}/teacher/attendance`);
  await page.waitForTimeout(800);
  const showLink = await page.$('a[href*="/teacher/attendance/"]:not([href*="create"])');
  if (showLink) {
    const href = await showLink.getAttribute('href');
    await testPageLoad(page, 'Attendance Show', `${BASE}${href}`);
  } else {
    addResult('Attendance Show', 'WARN', 'No attendance session link found');
  }
}

async function testLessons(page) {
  log('📖 Testing Lessons...');

  await testPageLoad(page, 'Lessons List', `${BASE}/teacher/lessons`);
  await testPageLoad(page, 'Lessons Create', `${BASE}/teacher/lessons/create`);
  await testPageLoad(page, 'Lessons Recycle Bin', `${BASE}/teacher/lessons/recycle-bin`);

  // Edit
  await safeGoto(page, `${BASE}/teacher/lessons`);
  await page.waitForTimeout(800);
  const editLink = await page.$('a[href*="/teacher/lessons/"][href*="/edit"]');
  if (editLink) {
    const href = await editLink.getAttribute('href');
    await testPageLoad(page, 'Lessons Edit', `${BASE}${href}`);
  } else {
    addResult('Lessons Edit', 'WARN', 'No lesson edit link found');
  }
}

async function testResources(page) {
  log('📎 Testing Resources...');

  await testPageLoad(page, 'Resources List', `${BASE}/teacher/resources`);
  await testPageLoad(page, 'Resources Create', `${BASE}/teacher/resources/create`);
  await testPageLoad(page, 'Resources Recycle Bin', `${BASE}/teacher/resources/recycle-bin`);

  // Edit
  await safeGoto(page, `${BASE}/teacher/resources`);
  await page.waitForTimeout(800);
  const editLink = await page.$('a[href*="/teacher/resources/"][href*="/edit"]');
  if (editLink) {
    const href = await editLink.getAttribute('href');
    await testPageLoad(page, 'Resources Edit', `${BASE}${href}`);
  } else {
    addResult('Resources Edit', 'WARN', 'No resource edit link found');
  }

  // Search
  await safeGoto(page, `${BASE}/teacher/resources`);
  await page.waitForTimeout(800);
  const searchInput = await page.$('input[name="search"], input[type="search"]');
  if (searchInput) {
    await searchInput.fill('test');
    await searchInput.press('Enter');
    await page.waitForTimeout(1500);
    addResult('Resources Search', 'PASS', 'Search submitted');
  } else {
    addResult('Resources Search', 'WARN', 'Search input not found');
  }
}

async function testExams(page) {
  log('📝 Testing Exams...');

  await testPageLoad(page, 'Exams List', `${BASE}/teacher/exams`);
  await testPageLoad(page, 'Exams Create', `${BASE}/teacher/exams/create`);
  await testPageLoad(page, 'Exams Recycle Bin', `${BASE}/teacher/exams/recycle-bin`);

  // Questions page
  await safeGoto(page, `${BASE}/teacher/exams`);
  await page.waitForTimeout(800);
  const examLink = await page.$('a[href*="/teacher/exams/"]:not([href*="create"]):not([href*="recycle"]):not([href*="edit"]):not([href*="attempt"]):not([href*="publish"]):not([href*="close"]):not([href*="archive"]):not([href*="duplicate"]):not([href*="restore"]):not([href*="force"])');
  if (examLink) {
    const href = await examLink.getAttribute('href');
    await testPageLoad(page, 'Exams Questions', `${BASE}${href}`);

    // Attempts
    await testPageLoad(page, 'Exams Attempts', `${BASE}${href}/attempts`);
    // Edit
    await testPageLoad(page, 'Exams Edit', `${BASE}${href}/edit`);
    // Time edit
    await testPageLoad(page, 'Exams Time Edit', `${BASE}${href}/time`);
  } else {
    addResult('Exams Questions', 'WARN', 'No exam link found');
  }
}

async function testQuestionBank(page) {
  log('🏦 Testing Question Bank...');

  await testPageLoad(page, 'Question Bank List', `${BASE}/teacher/question-bank`);
  await testPageLoad(page, 'Question Bank Create', `${BASE}/teacher/question-bank/create`);
  await testPageLoad(page, 'Question Bank Recycle Bin', `${BASE}/teacher/question-bank/recycle-bin`);

  // Edit
  await safeGoto(page, `${BASE}/teacher/question-bank`);
  await page.waitForTimeout(800);
  const editLink = await page.$('a[href*="/teacher/question-bank/"][href*="/edit"]');
  if (editLink) {
    const href = await editLink.getAttribute('href');
    await testPageLoad(page, 'Question Bank Edit', `${BASE}${href}`);
  } else {
    addResult('Question Bank Edit', 'WARN', 'No question bank edit link found');
  }
}

async function testReservations(page) {
  log('📦 Testing Reservations...');

  await testPageLoad(page, 'Reservations List', `${BASE}/teacher/reservations`);
  await testPageLoad(page, 'Reservations Create', `${BASE}/teacher/reservations/create`);
  await testPageLoad(page, 'Reservations Recycle Bin', `${BASE}/teacher/reservations/recycle-bin`);

  // Show
  await safeGoto(page, `${BASE}/teacher/reservations`);
  await page.waitForTimeout(800);
  const showLink = await page.$('a[href*="/teacher/reservations/"]:not([href*="create"]):not([href*="recycle"]):not([href*="edit"]):not([href*="restore"]):not([href*="force"]):not([href*="archive"]):not([href*="reopen"]):not([href*="deliver"])');
  if (showLink) {
    const href = await showLink.getAttribute('href');
    await testPageLoad(page, 'Reservations Show', `${BASE}${href}`);
    // Edit
    await testPageLoad(page, 'Reservations Edit', `${BASE}${href}/edit`);
  } else {
    addResult('Reservations Show', 'WARN', 'No reservation show link found');
  }
}

async function testAssistants(page) {
  log('🤝 Testing Assistants...');

  await testPageLoad(page, 'Assistants List', `${BASE}/teacher/assistants`);
  await testPageLoad(page, 'Assistants Create', `${BASE}/teacher/assistants/create`);

  // Edit
  await safeGoto(page, `${BASE}/teacher/assistants`);
  await page.waitForTimeout(800);
  const editLink = await page.$('a[href*="/teacher/assistants/"][href*="/edit"]');
  if (editLink) {
    const href = await editLink.getAttribute('href');
    await testPageLoad(page, 'Assistants Edit', `${BASE}${href}`);
  } else {
    addResult('Assistants Edit', 'WARN', 'No assistant edit link found');
  }
}

async function testTerm(page) {
  log('📅 Testing Term End...');

  await testPageLoad(page, 'Term End Form', `${BASE}/teacher/term/end`);
}

async function testProfile(page) {
  log('👤 Testing Profile...');

  await testPageLoad(page, 'Profile Edit', `${BASE}/profile`);
}

async function testNotifications(page) {
  log('🔔 Testing Notifications...');

  await testPageLoad(page, 'Notifications List', `${BASE}/notifications`);
}

async function main() {
  log('🚀 Starting Sprint 31 Teacher Runtime QA...');

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1280, height: 800 },
    locale: 'ar-EG',
  });
  const page = await context.newPage();

  try {
    // Login
    const loggedIn = await loginAsTeacher(page);
    if (!loggedIn) {
      log('❌ Cannot proceed without login');
      await browser.close();
      writeReport();
      return;
    }

    // Test all modules
    await testDashboard(page);
    await testGroupsCRUD(page);
    await testStudents(page);
    await testAttendance(page);
    await testLessons(page);
    await testResources(page);
    await testExams(page);
    await testQuestionBank(page);
    await testReservations(page);
    await testAssistants(page);
    await testTerm(page);
    await testProfile(page);
    await testNotifications(page);

    // Check for NOT IMPLEMENTED features per spec
    // Grades module - spec says missing
    addNotImplemented('Grades Module (no controller/model per spec section 18.1)');
    addNotImplemented('Reports Module (no dedicated controller per spec section 18.1)');
    addNotImplemented('Teacher Settings (no settings module per spec section 18.1)');
    addNotImplemented('Password Reset for Teacher (per spec section 18.1)');
    addNotImplemented('Email Verification (per spec section 18.1)');
    addNotImplemented('Two-Factor Authentication (per spec section 18.1)');
    addNotImplemented('Import Students CSV/Excel (per spec section 18.1)');
    addNotImplemented('Export Data Excel/PDF (per spec section 18.1)');
    addNotImplemented('Bulk Create Lessons (per spec section 18.1)');
    addNotImplemented('Teacher Calendar (per spec section 18.1)');
    addNotImplemented('Student Progress Tracking (per spec section 18.1)');
    addNotImplemented('Messaging System (per spec section 18.1)');
    addNotImplemented('Announcements Module (per spec section 18.1)');
    addNotImplemented('Parent Access (per spec section 18.1)');

  } catch (e) {
    log(`💥 Fatal error: ${e.message}`);
    addBug('Global', 'Fatal error during testing', e);
  } finally {
    await browser.close();
    writeReport();
  }
}

function writeReport() {
  const passCount = RESULTS.filter(r => r.status === 'PASS').length;
  const failCount = RESULTS.filter(r => r.status === 'FAIL').length;
  const warnCount = RESULTS.filter(r => r.status === 'WARN').length;

  const report = {
    sprint: 'Sprint 31 — Teacher Runtime QA (Phase 1)',
    timestamp: new Date().toISOString(),
    summary: {
      pages_tested: RESULTS.length,
      bugs_found: BUGS.length,
      not_implemented: NOT_IMPLEMENTED.length,
      runtime_successes: passCount,
      passes: passCount,
      fails: failCount,
      warnings: warnCount,
    },
    results: RESULTS,
    bugs: BUGS,
    not_implemented: NOT_IMPLEMENTED,
  };

  fs.writeFileSync('SPRINT_31_RUNTIME_QA_RESULTS.json', JSON.stringify(report, null, 2));
  log(`\n📊 SUMMARY:`);
  log(`   Pages tested: ${RESULTS.length}`);
  log(`   Bugs found: ${BUGS.length}`);
  log(`   NOT IMPLEMENTED: ${NOT_IMPLEMENTED.length}`);
  log(`   Runtime successes: ${passCount}`);
  log(`   Passes: ${passCount} | Fails: ${failCount} | Warnings: ${warnCount}`);
  log(`📄 Report saved to SPRINT_31_RUNTIME_QA_RESULTS.json`);
}

main().catch(e => {
  console.error('Fatal:', e);
  process.exit(1);
});