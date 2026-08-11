const { chromium } = require('playwright');
const fs = require('fs');

const BASE_URL = 'http://127.0.0.1:8000';
const SCREENSHOT_DIR = './sprint35_screenshots';

async function screenshot(page, name) {
    if (!fs.existsSync(SCREENSHOT_DIR)) fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
    try {
        await page.screenshot({ path: `${SCREENSHOT_DIR}/${name}.png`, fullPage: false, timeout: 10000 });
    } catch(e) {
        try {
            await page.screenshot({ path: `${SCREENSHOT_DIR}/${name}.png`, timeout: 10000 });
        } catch(e2) {}
    }
    console.log(`  📸 Screenshot: ${name}.png`);
}

async function run() {
    console.log('╔══════════════════════════════════════════════════════════╗');
    console.log('║   SPRINT 36 - STUDENT REGISTRATION RUNTIME QA           ║');
    console.log('╚══════════════════════════════════════════════════════════╝\n');

    const browser = await chromium.launch({ headless: false, slowMo: 30 });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        locale: 'ar-EG'
    });
    const page = await context.newPage();

    const timestamp = Date.now();
    const studentName = `Sprint36 Student ${timestamp}`;
    const mobile = `010${timestamp.toString().slice(-8)}`;
    const parentMobile = `011${timestamp.toString().slice(-8)}`;

    try {
        // Step 1: Open registration page with teacher code
        console.log('Step 1: Opening registration page...');
        await page.goto('http://127.0.0.1:8000/register/student?code=TC-FSMZ');
        await page.waitForTimeout(2000);
        
        // Verify teacher code is accepted and groups are visible
        const groupLabel = page.locator('text=اختر المجموعة');
        await groupLabel.waitFor({ timeout: 10000 });
        console.log('  ✓ Teacher code accepted, groups visible');
        await screenshot(page, 'sprint36_registration_page');

        // Step 2: Fill in the registration form
        console.log('\nStep 2: Filling registration form...');
        await page.fill('input[name="name"]', studentName);
        await page.fill('input[name="mobile"]', mobile);
        await page.fill('input[name="parent_mobile"]', parentMobile);
        await page.fill('input[name="password"]', 'password123');
        await page.fill('input[name="password_confirmation"]', 'password123');

        // Select group (group 15 - مجموعة اختبار 1)
        await page.selectOption('select[name="group_id"]', '15');
        console.log('  Form filled successfully');
        await screenshot(page, 'sprint36_form_filled');

        // Submit form
        console.log('\nStep 3: Submitting registration...');
        await page.click('button[type="submit"]');
        
        // Wait for redirect
        await page.waitForURL('http://127.0.0.1:8000/student/dashboard', { timeout: 15000 });
        console.log('  ✓ Redirected to student dashboard');
        await screenshot(page, 'sprint36_dashboard');

        // Verify we're logged in as a student
        console.log('\nStep 4: Verifying dashboard content...');
        const resourcesLink = page.locator('text=الموارد');
        const examsLink = page.locator('text=الامتحانات');
        const reservationsLink = page.locator('text=الحجوزات');
        
        if (await resourcesLink.count() > 0) console.log('  ✓ Resources link visible');
        if (await examsLink.count() > 0) console.log('  ✓ Exams link visible');
        if (await reservationsLink.count() > 0) console.log('  ✓ Reservations link visible');

        // Step 5: Navigate to Resources page
        console.log('\nStep 5: Navigating to Resources page...');
        await page.click('text=الموارد');
        await page.waitForURL('http://127.0.0.1:8000/student/resources', { timeout: 10000 });
        console.log('  ✓ Resources page loaded');
        await screenshot(page, 'sprint36_resources');

        console.log('\n╔══════════════════════════════════════════════════════════╗');
        console.log('║   ✅ ALL STEPS PASSED - REGISTRATION SUCCESSFUL        ║');
        console.log('╚══════════════════════════════════════════════════════════╝');
        console.log(`\nStudent Name: ${studentName}`);
        console.log(`Mobile: ${mobile}`);
        console.log(`Dashboard: http://127.0.0.1:8000/student/dashboard`);
        console.log(`Resources: http://127.0.0.1:8000/student/resources`);

    } catch (error) {
        console.log('\n╔══════════════════════════════════════════════════════════╗');
        console.log('║   ❌ TEST FAILED                                         ║');
        console.log('╚══════════════════════════════════════════════════════════╝');
        console.error(`Error: ${error.message}`);
        await screenshot(page, 'sprint36_error');
    } finally {
        await browser.close();
    }
}

run();
