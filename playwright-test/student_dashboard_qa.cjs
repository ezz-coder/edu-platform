const { chromium } = require('playwright');
const fs = require('fs');

async function runTest() {
  console.log('=== Student Dashboard Analysis ===\n');
  
  const browser = await chromium.launch({ headless: false });
  const page = await browser.newPage();
  await page.setViewportSize({ width: 1920, height: 1080 });

  try {
    // Login as student
    await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForSelector('input[name="mobile"]', { timeout: 10000 });
    
    await page.fill('input[name="mobile"]', '01000000000');
    await page.fill('input[name="password"]', 'Student@123');
    await page.click('button:has-text("تسجيل الدخول")');
    
    // Wait for redirect to student dashboard
    await page.waitForURL('**/student/dashboard', { timeout: 60000 });
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(3000);
    
    // Take screenshot
    await page.screenshot({ path: 'Student_Dashboard.png', fullPage: true });
    console.log('✓ Student_Dashboard.png taken');
    
    // Analyze page content
    const bodyText = await page.evaluate(() => document.body.innerText);
    const pageContent = await page.content();
    
    console.log('\n=== PAGE ANALYSIS ===\n');
    console.log('URL:', page.url());
    console.log('Title:', await page.title());
    console.log('\n--- Visible Text Content ---');
    console.log(bodyText.substring(0, 2000));
    
    console.log('\n--- Page Structure ---');
    
    // Check for common elements
    const checks = {
      'Dashboard Title': await page.locator('h1, h2, .dashboard-title').first().textContent(),
      'Statistics Cards': await page.locator('.stat-card, .stat-value, [class*="stat"]').count(),
      'Navigation Menu': await page.locator('nav, .sidebar, .navbar').count(),
      'Student Name': await page.locator('.student-name, .user-name, .nav-user').first().textContent(),
      'Enrolled Groups': await page.locator('text=مجموعات, text=groups, [class*="group"]').count(),
      'Upcoming Lessons': await page.locator('text=حصص, text=lessons, [class*="lesson"]').count(),
      'Assignments': await page.locator('text=واجبات, text=assignments, [class*="assignment"]').count(),
      'Attendance': await page.locator('text=حضور, text=attendance, [class*="attendance"]').count(),
      'Notifications': await page.locator('.notification, [class*="notification"]').count(),
      'Error Messages': await page.locator('.alert-error, .error, [class*="error"]').count(),
      'Success Messages': await page.locator('.alert-success, .success, [class*="success"]').count()
    };
    
    console.log('\n--- Element Analysis ---');
    for (const [name, value] of Object.entries(checks)) {
      console.log(`${name}: ${value}`);
    }
    
    // Check for Laravel errors
    const hasLaravelError = pageContent.includes('Whoops') || 
                           pageContent.includes('Exception') ||
                           pageContent.includes('Stack Trace') ||
                           pageContent.includes('ErrorException');
    
    console.log('\n--- Error Check ---');
    console.log('Laravel Errors:', hasLaravelError ? 'YES - ERROR FOUND' : 'NO - Clean');
    
    // Check page length
    console.log('\n--- Page Content ---');
    console.log('Total Content Length:', bodyText.length, 'characters');
    console.log('Has Sufficient Content:', bodyText.length > 100 ? 'YES' : 'NO - Empty Page');
    
    // Take additional screenshots of different sections
    console.log('\n--- Taking Additional Screenshots ---');
    
    // Try to find and click on different menu items
    const menuItems = ['المجموعات', 'الحصص', 'الواجبات', 'الحضور', 'الملاحظات'];
    for (const item of menuItems) {
      const link = await page.locator(`a:has-text("${item}")`).first();
      if (await link.count() > 0 && await link.isVisible()) {
        await link.click();
        await page.waitForTimeout(2000);
        const screenshotName = `Student_${item}.png`;
        await page.screenshot({ path: screenshotName, fullPage: true });
        console.log(`✓ ${screenshotName}`);
      }
    }
    
    console.log('\n=== ANALYSIS COMPLETE ===');
    console.log('Student Dashboard screenshots saved');
    
  } catch (error) {
    console.error('Error:', error.message);
    await page.screenshot({ path: 'Student_Error.png', fullPage: true });
  } finally {
    await browser.close();
  }
}

runTest().catch(console.error);