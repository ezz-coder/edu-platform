const { chromium } = require('playwright');
const fs = require('fs');

const BASE_URL = 'http://127.0.0.1:8000';
const STUDENT_MOBILE = '01000000000';
const STUDENT_PASSWORD = 'Student@123';
const TEACHER_CODE = 'TC-FSMZ';

const results = {
  testedPages: 0,
  successfulTests: 0,
  failedTests: 0,
  bugsFound: [],
  bugsFixed: [],
  featuresNotImplemented: [],
  details: []
};

function logResult(pageName, status, detail) {
  results.details.push({ page: pageName, status, detail });
  if (status === 'PASS') results.successfulTests++;
  else if (status === 'FAIL') results.failedTests++;
  console.log(`[${status}] ${pageName}: ${detail}`);
}

function logBug(bug) {
  results.bugsFound.push(bug);
  console.log(`[BUG] ${bug}`);
}

async function safeGoto(page, url, timeout = 20000) {
  try {
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout });
    await page.waitForTimeout(2000);
    return true;
  } catch (e) {
    console.log(`  Warning: Timeout navigating to ${url}: ${e.message}`);
    return false;
  }
}

async function loginAsStudent(page) {
  await safeGoto(page, `${BASE_URL}/login`);
  
  const hasMobile = await page.locator('input[name="mobile"]').count() > 0;
  const hasEmail = await page.locator('input[name="email"]').count() > 0;
  
  if (hasMobile) {
    await page.fill('input[name="mobile"]', STUDENT_MOBILE);
  } else if (hasEmail) {
    await page.fill('input[name="email"]', '01000000000@bayan.local');
  } else {
    const emailInputs = await page.locator('input[type="email"]').all();
    if (emailInputs.length > 0) {
      await emailInputs[0].fill('01000000000@bayan.local');
    }
  }
  
  await page.fill('input[type="password"]', STUDENT_PASSWORD);
  
  const submitBtn = await page.locator('button[type="submit"]');
  if (await submitBtn.count() > 0) {
    await submitBtn.click();
  }
  
  await page.waitForTimeout(3000);
  return page.url();
}

async function runTest() {
  console.log('=== SPRINT 34 - Student Runtime QA (Phase 1) ===\n');
  
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 },
    locale: 'ar-EG'
  });
  const page = await context.newPage();

  try {
    // ============================================================
    // TEST 1: Login Page
    // ============================================================
    console.log('\n--- Test 1: Login Page ---');
    const loginLoaded = await safeGoto(page, `${BASE_URL}/login`);
    
    if (loginLoaded) {
      const hasLoginForm = await page.locator('input[name="email"], input[name="mobile"], input[type="email"]').count() > 0;
      const hasPasswordField = await page.locator('input[type="password"]').count() > 0;
      
      if (hasLoginForm && hasPasswordField) {
        logResult('Login Page', 'PASS', 'Login form displayed with email/mobile and password fields');
      } else {
        logResult('Login Page', 'FAIL', `Login form missing. Has form: ${hasLoginForm}, Has password: ${hasPasswordField}`);
      }
    } else {
      logResult('Login Page', 'FAIL', 'Login page failed to load');
    }
    results.testedPages++;

    // ============================================================
    // TEST 2: Student Login
    // ============================================================
    console.log('\n--- Test 2: Student Login ---');
    const afterLoginUrl = await loginAsStudent(page);
    const isOnStudentDashboard = afterLoginUrl.includes('/student/dashboard') || afterLoginUrl.includes('/student/resources');
    
    if (isOnStudentDashboard) {
      logResult('Student Login', 'PASS', `Successfully logged in, redirected to: ${afterLoginUrl}`);
    } else {
      logResult('Student Login', 'FAIL', `Login failed. URL: ${afterLoginUrl}`);
    }
    results.testedPages++;

    // ============================================================
    // TEST 3: Dashboard / Resources Home
    // ============================================================
    console.log('\n--- Test 3: Dashboard / Resources Home ---');
    await safeGoto(page, `${BASE_URL}/student/dashboard`);
    
    const dashboardContent = await page.evaluate(() => document.body.innerText);
    const hasResources = dashboardContent.includes('\u0645\u0635\u0627\u062f\u0631') || dashboardContent.toLowerCase().includes('resources');
    const hasExams = dashboardContent.includes('\u0627\u0645\u062a\u062d\u0627\u0646\u0627\u062a') || dashboardContent.toLowerCase().includes('exams');
    const hasLessons = dashboardContent.includes('\u062f\u0631\u0648\u0633') || dashboardContent.toLowerCase().includes('lessons');
    
    if (hasResources && hasExams && hasLessons) {
      logResult('Dashboard/Resources Home', 'PASS', 'Shows resources, exams, and lessons sections');
    } else {
      const missing = [];
      if (!hasResources) missing.push('Resources');
      if (!hasExams) missing.push('Exams');
      if (!hasLessons) missing.push('Lessons');
      logResult('Dashboard/Resources Home', 'FAIL', `Missing: ${missing.join(', ')}`);
    }
    results.testedPages++;

    // ============================================================
    // TEST 4: Resources List
    // ============================================================
    console.log('\n--- Test 4: Resources List ---');
    await safeGoto(page, `${BASE_URL}/student/resources`);
    
    const resourcesContent = await page.evaluate(() => document.body.innerText);
    if (resourcesContent.length > 100) {
      logResult('Resources List', 'PASS', 'Resources page loaded with content');
    } else {
      logResult('Resources List', 'FAIL', 'Resources page appears empty');
    }
    results.testedPages++;

    // ============================================================
    // TEST 5: Groups Page
    // ============================================================
    console.log('\n--- Test 5: Groups Page ---');
    await safeGoto(page, `${BASE_URL}/student/groups`);
    
    const groupsContent = await page.evaluate(() => document.body.innerText);
    const hasGroups = groupsContent.includes('\u0645\u062c\u0645\u0648\u0639\u0629') || groupsContent.toLowerCase().includes('group');
    
    if (hasGroups) {
      logResult('Groups Page', 'PASS', 'Groups page loaded with group listings');
    } else {
      logResult('Groups Page', 'FAIL', 'Groups page has no group content');
    }
    results.testedPages++;

    // ============================================================
    // TEST 6: Reservations Page
    // ============================================================
    console.log('\n--- Test 6: Reservations Page ---');
    await safeGoto(page, `${BASE_URL}/student/reservations`);
    
    const reservationsContent = await page.evaluate(() => document.body.innerText);
    if (reservationsContent.length > 50) {
      logResult('Reservations Page', 'PASS', 'Reservations page loaded');
    } else {
      logResult('Reservations Page', 'FAIL', 'Reservations page has no content');
    }
    results.testedPages++;

    // ============================================================
    // TEST 7: Reservation History
    // ============================================================
    console.log('\n--- Test 7: Reservation History ---');
    await safeGoto(page, `${BASE_URL}/student/reservations/history`);
    
    const historyContent = await page.evaluate(() => document.body.innerText);
    if (historyContent.length > 50) {
      logResult('Reservation History', 'PASS', 'Reservation history page loaded');
    } else {
      logResult('Reservation History', 'FAIL', 'Reservation history page has no content');
    }
    results.testedPages++;

    // ============================================================
    // TEST 8: Exams Page
    // ============================================================
    console.log('\n--- Test 8: Exams Page ---');
    await safeGoto(page, `${BASE_URL}/student/exams`);
    
    const examsContent = await page.evaluate(() => document.body.innerText);
    if (examsContent.length > 50) {
      logResult('Exams Page', 'PASS', 'Exams page loaded');
    } else {
      logResult('Exams Page', 'FAIL', 'Exams page has no content');
    }
    results.testedPages++;

    // ============================================================
    // TEST 9: Account Page (Profile)
    // ============================================================
    console.log('\n--- Test 9: Account Page ---');
    await safeGoto(page, `${BASE_URL}/student/account`);
    
    const accountContent = await page.evaluate(() => document.body.innerText);
    const hasStudentInfo = accountContent.includes('\u0643\u0648\u062f') || accountContent.includes('code') || accountContent.includes('\u0637\u0627\u0644\u0628');
    
    if (hasStudentInfo) {
      logResult('Account Page', 'PASS', 'Account page shows student info');
    } else {
      logResult('Account Page', 'FAIL', 'Account page missing student information');
    }
    results.testedPages++;

    // ============================================================
    // TEST 10: Student Card
    // ============================================================
    console.log('\n--- Test 10: Student Card ---');
    await safeGoto(page, `${BASE_URL}/student/card`);
    
    const cardContent = await page.evaluate(() => document.body.innerText);
    const hasCard = cardContent.includes('\u0628\u0637\u0627\u0642\u0629') || cardContent.toLowerCase().includes('card');
    
    if (hasCard) {
      logResult('Student Card', 'PASS', 'Student card page loaded');
    } else {
      logResult('Student Card', 'FAIL', 'Student card page has no card content');
    }
    results.testedPages++;

    // ============================================================
    // TEST 11: Student Report
    // ============================================================
    console.log('\n--- Test 11: Student Report ---');
    await safeGoto(page, `${BASE_URL}/student/report`);
    
    const reportContent = await page.evaluate(() => document.body.innerText);
    if (reportContent.length > 100) {
      logResult('Student Report', 'PASS', 'Student report page loaded');
    } else {
      logResult('Student Report', 'FAIL', 'Student report page has no content');
    }
    results.testedPages++;

    // ============================================================
    // TEST 12: Notifications Page
    // ============================================================
    console.log('\n--- Test 12: Notifications Page ---');
    await safeGoto(page, `${BASE_URL}/notifications`);
    
    const notifContent = await page.evaluate(() => document.body.innerText);
    if (notifContent.length > 50) {
      logResult('Notifications Page', 'PASS', 'Notifications page loaded');
    } else {
      logResult('Notifications Page', 'FAIL', 'Notifications page has no content');
    }
    results.testedPages++;

    // ============================================================
    // TEST 13: Profile Edit Page
    // ============================================================
    console.log('\n--- Test 13: Profile Edit Page ---');
    await safeGoto(page, `${BASE_URL}/profile`);
    
    const profileForm = await page.locator('input[name="name"], input[type="text"]').count() > 0;
    if (profileForm) {
      logResult('Profile Edit Page', 'PASS', 'Profile edit page loaded with form');
    } else {
      logResult('Profile Edit Page', 'FAIL', 'Profile edit page has no form');
    }
    results.testedPages++;

    // ============================================================
    // TEST 14: Account Switching Page
    // ============================================================
    console.log('\n--- Test 14: Account Switching ---');
    await safeGoto(page, `${BASE_URL}/student/accounts`);
    
    const accountsContent = await page.evaluate(() => document.body.innerText);
    if (accountsContent.length > 50) {
      logResult('Account Switching', 'PASS', 'Account switching page loaded');
    } else {
      logResult('Account Switching', 'FAIL', 'Account switching page has no content');
    }
    results.testedPages++;

    // ============================================================
    // TEST 15: Registration Page (Guest)
    // ============================================================
    console.log('\n--- Test 15: Registration Page ---');
    await safeGoto(page, `${BASE_URL}/login`);
    await safeGoto(page, `${BASE_URL}/register/student`);
    
    const registerContent = await page.evaluate(() => document.body.innerText);
    if (registerContent.length > 100) {
      logResult('Registration Page', 'PASS', 'Registration page loaded');
    } else {
      logResult('Registration Page', 'FAIL', 'Registration page has no content');
    }
    results.testedPages++;

    // ============================================================
    // TEST 16: Registration with Teacher Code
    // ============================================================
    console.log('\n--- Test 16: Registration with Teacher Code ---');
    await safeGoto(page, `${BASE_URL}/register/student?code=${TEACHER_CODE}`);
    
    const regWithCode = await page.evaluate(() => document.body.innerText);
    const hasGroupsList = regWithCode.includes('\u0645\u062c\u0645\u0648\u0639\u0629') || regWithCode.toLowerCase().includes('group');
    
    if (hasGroupsList) {
      logResult('Registration with Teacher Code', 'PASS', 'Teacher code accepted, groups displayed');
    } else {
      logResult('Registration with Teacher Code', 'FAIL', 'Teacher code did not show groups');
    }
    results.testedPages++;

    // ============================================================
    // TEST 17: Logout
    // ============================================================
    console.log('\n--- Test 17: Logout ---');
    await loginAsStudent(page);
    await safeGoto(page, `${BASE_URL}/logout`);
    
    const loggedOutUrl = page.url();
    if (loggedOutUrl.includes('/login') || loggedOutUrl === `${BASE_URL}/`) {
      logResult('Logout', 'PASS', 'Successfully logged out');
    } else {
      logResult('Logout', 'FAIL', `Logout did not redirect to login. URL: ${loggedOutUrl}`);
    }
    results.testedPages++;

    // ============================================================
    // TEST 18: Sidebar Navigation Links
    // ============================================================
    console.log('\n--- Test 18: Sidebar Navigation ---');
    await loginAsStudent(page);
    await safeGoto(page, `${BASE_URL}/student/dashboard`);
    
    const navTexts = await page.evaluate(() => {
      const sidebar = document.querySelector('nav, .sidebar, aside, [class*="sidebar"], [class*="nav"]');
      if (!sidebar) return 'no-sidebar-found';
      return Array.from(sidebar.querySelectorAll('a, button')).map(l => l.textContent.trim()).filter(t => t.length > 0);
    });
    
    const navString = Array.isArray(navTexts) ? navTexts.join(' ') : navTexts;
    const hasNavResources = navString.includes('\u0645\u0635\u0627\u062f\u0631');
    const hasNavExams = navString.includes('\u0627\u0645\u062a\u062d\u0627\u0646\u0627\u062a');
    const hasNavReservations = navString.includes('\u062d\u062c\u0648\u0632\u0627\u062a');
    const hasNavNotifications = navString.includes('\u0625\u0634\u0639\u0627\u0631\u0627\u062a');
    const hasNavAccount = navString.includes('\u062d\u0633\u0627\u0628\u064a');
    
    const navItems = [];
    if (hasNavResources) navItems.push('Resources');
    if (hasNavExams) navItems.push('Exams');
    if (hasNavReservations) navItems.push('Reservations');
    if (hasNavNotifications) navItems.push('Notifications');
    if (hasNavAccount) navItems.push('Account');
    
    if (navItems.length >= 4) {
      logResult('Sidebar Navigation', 'PASS', `Found navigation items: ${navItems.join(', ')}`);
    } else {
      logResult('Sidebar Navigation', 'FAIL', `Only found ${navItems.length}/5 navigation items: ${navItems.join(', ')}`);
    }
    results.testedPages++;

    // ============================================================
    // TEST 19: Resource Detail Page
    // ============================================================
    console.log('\n--- Test 19: Resource Detail Page ---');
    await safeGoto(page, `${BASE_URL}/student/resources`);
    
    const resourceLinks = await page.locator('a[href*="/student/resources/"]').all();
    if (resourceLinks.length > 0) {
      const href = await resourceLinks[0].getAttribute('href');
      await safeGoto(page, `${BASE_URL}${href}`);
      
      const detailContent = await page.evaluate(() => document.body.innerText);
      if (detailContent.length > 50) {
        logResult('Resource Detail Page', 'PASS', 'Resource detail page loaded');
      } else {
        logResult('Resource Detail Page', 'FAIL', 'Resource detail page has no content');
      }
    } else {
      logResult('Resource Detail Page', 'SKIP', 'No resource links found');
    }
    results.testedPages++;

    // ============================================================
    // TEST 20: Error Check on All Pages
    // ============================================================
    console.log('\n--- Test 20: Error Check on All Pages ---');
    const pagesToCheck = [
      '/student/dashboard',
      '/student/resources',
      '/student/groups',
      '/student/reservations',
      '/student/reservations/history',
      '/student/exams',
      '/student/account',
      '/student/card',
      '/student/report',
      '/student/accounts',
      '/notifications',
      '/profile'
    ];
    
    let errorCount = 0;
    for (const url of pagesToCheck) {
      await safeGoto(page, `${BASE_URL}${url}`);
      
      const content = await page.content();
      const hasError = content.includes('Whoops') || 
                       content.includes('Stack Trace') ||
                       content.includes('ErrorException') ||
                       content.includes('NotFoundHttpException') ||
                       content.includes('500 ') ||
                       content.includes('403 ');
      
      if (hasError) {
        errorCount++;
        logBug(`Error on ${url}: Page shows error/exception`);
      }
    }
    
    if (errorCount === 0) {
      logResult('Error Check', 'PASS', 'No errors on any student pages');
    } else {
      logResult('Error Check', 'FAIL', `${errorCount} pages have errors`);
    }
    results.testedPages++;

    // ============================================================
    // CHECK MISSING FEATURES
    // ============================================================
    console.log('\n--- Checking Missing Features ---');
    
    // Check cancel reservation
    await safeGoto(page, `${BASE_URL}/student/reservations`);
    const resText = await page.evaluate(() => document.body.innerText);
    if (!resText.includes('\u0625\u0644\u063a\u0627\u0621') && !resText.toLowerCase().includes('cancel')) {
      results.featuresNotImplemented.push('Cancel Reservation (student_features.md Section 12)');
    }
    
    // Check search/filter on resources
    await safeGoto(page, `${BASE_URL}/student/resources`);
    const resPageText = await page.evaluate(() => document.body.innerText);
    if (!resPageText.includes('\u0628\u062d\u062b') && !resPageText.toLowerCase().includes('search')) {
      results.featuresNotImplemented.push('Search/Filter on Resources page (student_features.md Section 8)');
    }
    
    // Check exam review attempts
    await safeGoto(page, `${BASE_URL}/student/exams`);
    const examText = await page.evaluate(() => document.body.innerText);
    if (!examText.includes('\u0645\u0631\u0627\u062c\u0639\u0629') && !examText.toLowerCase().includes('review') && !examText.includes('\u0633\u0627\u0628\u0642')) {
      results.featuresNotImplemented.push('Review Previous Exam Attempts (student_features.md Section 9)');
    }
    
    // ============================================================
    // SUMMARY
    // ============================================================
    console.log('\n=== TEST SUMMARY ===');
    console.log(`Pages Tested: ${results.testedPages}`);
    console.log(`Successful: ${results.successfulTests}`);
    console.log(`Failed: ${results.failedTests}`);
    console.log(`Bugs Found: ${results.bugsFound.length}`);
    console.log(`Features Not Implemented: ${results.featuresNotImplemented.length}`);
    
    if (results.featuresNotImplemented.length > 0) {
      console.log('\nFeatures Not Implemented:');
      results.featuresNotImplemented.forEach(f => console.log(`  - ${f}`));
    }
    
    generateReport();
    
  } catch (error) {
    console.error('Fatal Error:', error.message);
    try { await page.screenshot({ path: 'sprint34_fatal_error.png', fullPage: true }); } catch(e) {}
  } finally {
    await browser.close();
  }
}

function generateReport() {
  const report = `# SPRINT 34 — Student Runtime QA Report

## 📊 Summary

| Metric | Value |
|--------|-------|
| Pages Tested | ${results.testedPages} |
| Successful Tests | ${results.successfulTests} |
| Failed Tests | ${results.failedTests} |
| Bugs Found | ${results.bugsFound.length} |
| Bugs Fixed | ${results.bugsFixed.length} |
| Features Not Implemented | ${results.featuresNotImplemented.length} |

## ✅ Pages Tested

${results.details.map(d => `| ${d.page} | ${d.status} | ${d.detail} |`).join('\n')}

## 🐛 Bugs Found

${results.bugsFound.length > 0 ? results.bugsFound.map(b => `- ${b}`).join('\n') : 'No bugs found during runtime testing.'}

## 🔧 Bugs Fixed

${results.bugsFixed.length > 0 ? results.bugsFixed.map(b => `- ${b}`).join('\n') : 'No bugs were fixed during this session.'}

## ❌ Features Not Implemented (from student_features.md)

${results.featuresNotImplemented.length > 0 ? results.featuresNotImplemented.map(f => `- ${f}`).join('\n') : 'All required features are implemented.'}

## 📁 Files Modified

- None (runtime testing only)

## 🏁 Final Verdict

${results.failedTests === 0 && results.bugsFound.length === 0 ? '✅ **Student module is fully compliant with specifications.**' : '⚠️ **Student module has issues that need to be addressed.**'}
`;

  fs.writeFileSync('SPRINT_34_RUNTIME_QA_REPORT.md', report, 'utf8');
  console.log('\nReport saved: SPRINT_34_RUNTIME_QA_REPORT.md');
}

runTest().catch(console.error);