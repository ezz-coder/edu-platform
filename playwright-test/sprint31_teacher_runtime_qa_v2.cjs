/**
 * Sprint 31 — Teacher Runtime QA (Phase 1) — v2
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
let pageRef = null;

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

function normalizeUrl(href) {
  if (!href) return null;
  if (href.startsWith("http://") || href.startsWith("https://")) return href;
  return BASE + (href.startsWith("/") ? "" : "/") + href;
}

async function safeGoto(page, url) {
  try {
    const resp = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 20000 });
    return resp;
  } catch (e) {
    return null;
  }
}

async function getPageText(page) {
  try {
    return await page.evaluate(() => document.body ? document.body.innerText : '');
  } catch (e) {
    return '';
  }
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

async function isLoggedIn(page) {
  const url = page.url();
  if (url.includes('/login')) return false;
  const text = await getPageText(page);
  if (text.includes('تسجيل الدخول') && text.includes('رقم الموبايل') && text.length < 2000) return false;
  return true;
}

async function ensureLoggedIn(page) {
  if (await isLoggedIn(page)) return true;
  log('🔄 Session lost — re-logging in...');
  return await loginAsTeacher(page);
}

async function loginAsTeacher(page) {
  log('🔐 Logging in as teacher...');
  await safeGoto(page, `${BASE}/login`);
  await page.waitForLoadState('domcontentloaded');
  await page.waitForTimeout(1000);

  const mobileInput = await page.$('input[name="mobile"]');
  const passwordInput = await page.$('input[name="password"]');

  if (!mobileInput || !passwordInput) {
    addBug('Login', 'Login form fields not found (mobile/password)');
    return false;
  }

  await mobileInput.fill('01000000001');
  await passwordInput.fill('Teacher@123');

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

  if (url.includes('/teacher/dashboard') || url.includes('/teacher')) {
    log('✅ Login successful — redirected to teacher area');
    return true;
  }

  addBug('Login', `Login failed — URL: ${url}`, (await getPageText(page)).substring(0, 300));
  return false;
}

async function testPageLoad(page, name, url) {
  log(`📄 Testing: ${name} (${url})`);

  // Ensure logged in before each page
  await ensureLoggedIn(page);

  const resp = await safeGoto(page, url);
  await page.waitForTimeout(500);

  // Check if redirected to login
  if (page.url().includes('/login')) {
    log('🔄 Redirected to login — re-logging in...');
    const relogged = await loginAsTeacher(page);
    if (relogged) {
      const resp2 = await safeGoto(page, url);
      await page.waitForTimeout(500);
      if (!resp2) {
        addBug(name, `Page failed to load after re-login: ${url}`);
        addResult(name, 'FAIL', 'Page did not load after re-login');
        return false;
      }
      const status2 = resp2.status();
      if (status2 >= 500) {
        addBug(name, `Server error ${status2} on ${url}`);
        addResult(name, 'FAIL', `HTTP ${status2}`);
        return false;
      }
      const errPattern = await hasErrorOnPage(page);
      if (errPattern) {
        addBug(name, `PHP/View error: ${errPattern}`, (await getPageText(page)).substring(0, 500));
        addResult(name, 'FAIL', `Error: ${errPattern}`);
        return false;
      }
      addResult(name, 'PASS', `HTTP ${status2} (after re-login)`);
      return true;
    }
    addBug(name, `Could not re-login to access: ${url}`);
    addResult(name, 'FAIL', 'Re-login failed');
    return false;
  }

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
    addResult(name, 'WARN', `HTTP 403 — access denied`);
    return false;
  }

  if (status === 404) {
    addBug(name, `404 Not Found: ${url}`);
    addResult(name, 'FAIL', 'HTTP 404');
    return false;
  }

  const errPattern = await hasErrorOnPage(page);
  if (errPattern) {
    addBug(name, `PHP/View error: ${errPattern}`, (await getPageText(page)).substring(0, 500));
    addResult(name, 'FAIL', `Error: ${errPattern}`);
    return false;
  }

  addResult(name, 'PASS', `HTTP ${status}`);
  return true;
}

async function testDashboard(page) {
  const ok = await testPageLoad(page, 'Teacher Dashboard', `${BASE}/teacher/dashboard`);
  if (!ok) return;

  const text = await getPageText(page);
  const expectedItems = ['المجموعات', 'الطلاب', 'الامتحانات', 'الحضور'];
  let found = 0;
  for (const item of expectedItems) {
    if (text.includes(item)) found++;
  }
  addResult('Dashboard Stats', found >= 2 ? 'PASS' : 'WARN', `Found ${found}/${expectedItems.length} expected items`);

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

  await testPageLoad(page, 'Groups List', `${BASE}/teacher/groups`);
  await testPageLoad(page, 'Groups Create', `${BASE}/teacher/groups/create`);

  // Show first group
  await safeGoto(page, `${BASE}/teacher/groups`);
  await page.waitForTimeout(500);
  await ensureLoggedIn(page);
  const showLink = await page.$('a[href*="/teacher/groups/"]:not([href*="create"]):not([href*="edit"]):not([href*="cards"])');
  if (showLink) {
    const href = await showLink.getAttribute('href');
    await testPageLoad(page, 'Groups Show', `normalizeUrl(href)`);
  } else {
    addResult('Groups Show', 'WARN', 'No group show link found');
  }

  // Edit
  await safeGoto(page, `${BASE}/teacher/groups`);
  await page.waitForTimeout(500);
  await ensureLoggedIn(page);
  const editLink = await page.$('a[href*="/teacher/groups/"][href*="/edit"]');
  if (editLink) {
    const href = await editLink.getAttribute('href');
    await testPageLoad(page, 'Groups Edit', `normalizeUrl(href)`);
  } else {
    addResult('Groups Edit', 'WARN', 'No group edit link found');
  }
}

async function testStudents(page) {
  log('👥 Testing Students...');

  await testPageLoad(page, 'Students List', `${BASE}/teacher/students`);

  // Search
  await safeGoto(page, `${BASE}/teacher/students`);
  await page.waitForTimeout(500);
  await ensureLoggedIn(page);
  const searchInput = await page.$('input[name="search"], input[type="search"], input[placeholder*="بحث"], input[placeholder*="search"]');
  if (searchInput) {
    await searchInput.fill('test');
    await searchInput.press('Enter');
    await page.waitForTimeout(1500);
    addResult('Students Search', 'PASS', 'Search submitted');
  } else {
    addResult('Students Search', 'WARN', 'Search input not found');
  }

  // Filter
  await safeGoto(page, `${BASE}/teacher/students`);
  await page.waitForTimeout(500);
  await ensureLoggedIn(page);
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
  await page.waitForTimeout(500);
  await ensureLoggedIn(page);
  const showLink = await page.$('a[href*="/teacher/students/"]:not([href*="move"]):not([href*="remove"]):not([href*="restore"]):not([href*="transfer"]):not([href*="card"]):not([href*="history"]):not([href*="bulk"])');
  if (showLink) {
    const href = await showLink.getAttribute('href');
    await testPageLoad(page, 'Students Show', `normalizeUrl(href)`);
  } else {
    addResult('Students Show', 'WARN', 'No student show link found');
  }
}

async function testAttendance(page) {
  log('📋 Testing Attendance...');

  await testPageLoad(page, 'Attendance List', `${BASE}/teacher/attendance`);
  await testPageLoad(page, 'Attendance Create', `${BASE}/teacher/attendance/create`);

  await safeGoto(page, `${BASE}/teacher/attendance`);
  await page.waitForTimeout(500);
  await ensureLoggedIn(page);
  const showLink = await page.$('a[href*="/teacher/attendance/"]:not([href*="create"])');
  if (showLink) {
    const href = await showLink.getAttribute('href');
    await testPageLoad(page, 'Attendance Show', `normalizeUrl(href)`);
  } else {
    addResult('Attendance Show', 'WARN', 'No attendance session link found');
  }
}

async function testLessons(page) {
  log('📖 Testing Lessons...');

  await testPageLoad(page, 'Lessons List', `${BASE}/teacher/lessons`);
  await testPageLoad(page, 'Lessons Create', `${BASE}/teacher/lessons/create`);
  await testPageLoad(page, 'Lessons Recycle Bin', `${BASE}/teacher/lessons/recycle-bin`);

  await safeGoto(page, `${BASE}/teacher/lessons`);
  await page.waitForTimeout(500);
  await ensureLoggedIn(page);
  const editLink = await page.$('a[href*="/teacher/lessons/"][href*="/edit"]');
  if (editLink) {
    const href = await editLink.getAttribute('href');
    await testPageLoad(page, 'Lessons Edit', `normalizeUrl(href)`);
  } else {
    addResult('Lessons Edit', 'WARN', 'No lesson edit link found');
  }
}

async function testResources(page) {
  log('📎 Testing Resources...');

  await testPageLoad(page, 'Resources List', `${BASE}/teacher/resources`);
  await testPageLoad(page, 'Resources Create', `${BASE}/teacher/resources/create`);
  await testPageLoad(page, 'Resources Recycle Bin', `${BASE}/teacher/resources/recycle-bin`);

  await safeGoto(page, `${BASE}/teacher/resources`);
  await page.waitForTimeout(500);
  await ensureLoggedIn(page);
  const editLink = await page.$('a[href*="/teacher/resources/"][href*="/edit"]');
  if (editLink) {
    const href = await editLink.getAttribute('href');
    await testPageLoad(page, 'Resources Edit', `normalizeUrl(href)`);
  } else {
    addResult('Resources Edit', 'WARN', 'No resource edit link found');
  }

  await safeGoto(page, `${BASE}/teacher/resources`);
  await page.waitForTimeout(500);
  await ensureLoggedIn(page);
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

  await safeGoto(page, `${BASE}/teacher/exams`);
  await page.waitForTimeout(500);
  await ensureLoggedIn(page);
  const examLink = await page.$('a[href*="/teacher/exams/"]:not([href*="create"]):not([href*="recycle"]):not([href*="edit"]):not([href*="attempt"]):not([href*="publish"]):not([href*="close"]):not([href*="archive"]):not([href*="duplicate"]):not([href*="restore"]):not([href*="force"])');
  if (examLink) {
    const href = await examLink.getAttribute('href');
    await testPageLoad(page, 'Exams Questions', `normalizeUrl(href)`);
    await testPageLoad(page, 'Exams Attempts', `normalizeUrl(href)/attempts`);
    await testPageLoad(page, 'Exams Edit', `normalizeUrl(href)/edit`);
    await testPageLoad(page, 'Exams Time Edit', `normalizeUrl(href)/time`);
  } else {
    addResult('Exams Questions', 'WARN', 'No exam link found');
  }
}

async function testQuestionBank(page) {
  log('🏦 Testing Question Bank...');

  await testPageLoad(page, 'Question Bank List', `${BASE}/teacher/question-bank`);
  await testPageLoad(page, 'Question Bank Create', `${BASE}/teacher/question-bank/create`);
  await testPageLoad(page, 'Question Bank Recycle Bin', `${BASE}/teacher/question-bank/recycle-bin`);

  await safeGoto(page, `${BASE}/teacher/question-bank`);
  await page.waitForTimeout(500);
  await ensureLoggedIn(page);
  const editLink = await page.$('a[href*="/teacher/question-bank/"][href*="/edit"]');
  if (editLink) {
    const href = await editLink.getAttribute('href');
    await testPageLoad(page, 'Question Bank Edit', `normalizeUrl(href)`);
  } else {
    addResult('Question Bank Edit', 'WARN', 'No question bank edit link found');
  }
}

async function testReservations(page) {
  log('📦 Testing Reservations...');

  await testPageLoad(page, 'Reservations List', `${BASE}/teacher/reservations`);
  await testPageLoad(page, 'Reservations Create', `${BASE}/teacher/reservations/create`);
  await testPageLoad(page, 'Reservations Recycle Bin', `${BASE}/teacher/reservations/recycle-bin`);

  await safeGoto(page, `${BASE}/teacher/reservations`);
  await page.waitForTimeout(500);
  await ensureLoggedIn(page);
  const showLink = await page.$('a[href*="/teacher/reservations/"]:not([href*="create"]):not([href*="recycle"]):not([href*="edit"]):not([href*="restore"]):not([href*="force"]):not([href*="archive"]):not([href*="reopen"]):not([href*="deliver"])');
  if (showLink) {
    const href = await showLink.getAttribute('href');
    await testPageLoad(page, 'Reservations Show', `normalizeUrl(href)`);
    await testPageLoad(page, 'Reservations Edit', `normalizeUrl(href)/edit`);
  } else {
    addResult('Reservations Show', 'WARN', 'No reservation show link found');
  }
}

async function testAssistants(page) {
  log('🤝 Testing Assistants...');

  await testPageLoad(page, 'Assistants List', `${BASE}/teacher/assistants`);
  await testPageLoad(page, 'Assistants Create', `${BASE}/teacher/assistants/create`);

  await safeGoto(page, `${BASE}/teacher/assistants`);
  await page.waitForTimeout(500);
  await ensureLoggedIn(page);
  const editLink = await page.$('a[href*="/teacher/assistants/"][href*="/edit"]');
  if (editLink) {
    const href = await editLink.getAttribute('href');
    await testPageLoad(page, 'Assistants Edit', `normalizeUrl(href)`);
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
  log('🚀 Starting Sprint 31 Teacher Runtime QA v2...');

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1280, height: 800 },
    locale: 'ar-EG',
  });
  const page = await context.newPage();
  pageRef = page;

  try {
    const loggedIn = await loginAsTeacher(page);
    if (!loggedIn) {
      log('❌ Cannot proceed without login');
      await browser.close();
      writeReport();
      return;
    }

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