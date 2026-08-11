const { chromium } = require('playwright');
const fs = require('fs');

async function runStudentQA() {
    console.log('=== SPRINT 35 - STUDENT MODULE REAL BROWSER QA ===\n');
    
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();
    
    const screenshots = [];
    const bugs = [];
    let testResults = {
        home: false,
        login: false,
        dashboard: false,
        profile: false,
        groups: false,
        lessons: false,
        resources: false,
        exams: false,
        grades: false,
        attendance: false,
        notifications: false,
        downloads: false,
        logout: false
    };

    try {
        // ============================================
        // STEP 1: HOME PAGE
        // ============================================
        console.log('[1] Testing Home Page...');
        try {
            await page.goto('http://127.0.0.1:8000/', { waitUntil: 'domcontentloaded', timeout: 120000 });
            await page.waitForTimeout(3000);
            
            const content = await page.content();
            const hasError = content.includes('Whoops') || content.includes('Exception') || content.includes('ErrorException');
            
            if (hasError) {
                bugs.push({ step: 'Home Page', error: 'Laravel Exception found', severity: 'HIGH' });
            } else {
                testResults.home = true;
                await page.screenshot({ path: 'Home.png', fullPage: true, timeout: 120000 });
                screenshots.push('Home.png');
                console.log('✓ Home page loaded successfully');
            }
        } catch (error) {
            bugs.push({ step: 'Home Page', error: error.message, severity: 'HIGH' });
            console.log('✗ Home page failed:', error.message);
        }

        // ============================================
        // STEP 2: LOGIN
        // ============================================
        console.log('\n[2] Testing Login...');
        try {
            await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'domcontentloaded', timeout: 120000 });
            await page.waitForSelector('input[name="mobile"]', { timeout: 10000 });
            await page.waitForSelector('input[name="password"]', { timeout: 5000 });
            
            await page.fill('input[name="mobile"]', '01000000000');
            await page.fill('input[name="password"]', 'Student@123');
            await page.click('button[type="submit"]');
            
            await page.waitForURL('**/student/**', { timeout: 120000 });
            await page.waitForLoadState('domcontentloaded', { timeout: 120000 });
            await page.waitForTimeout(2000);
            
            let currentUrl = page.url();
            
            if (currentUrl.includes('/student/accounts')) {
                console.log('  → Account selection page detected, selecting account...');
                await page.waitForSelector('input[name="account_id"]', { timeout: 10000 });
                await page.click('input[name="account_id"]');
                await page.click('button:has-text("دخول")');
                await page.waitForURL('**/student/resources', { timeout: 30000 });
                await page.waitForLoadState('domcontentloaded', { timeout: 120000 });
                await page.waitForTimeout(2000);
            }
            
            currentUrl = page.url();
            const pageContent = await page.content();
            
            console.log('  URL after login:', currentUrl);
            console.log('  Page title:', await page.title());
            
            const hasError = pageContent.includes('Whoops') || pageContent.includes('Exception') || 
                           pageContent.includes('ErrorException') || pageContent.includes('These credentials');
            
            if (hasError) {
                const errorMatch = pageContent.match(/<pre>.*?Exception.*?<\/pre>/s) || 
                                  pageContent.match(/<div class=".*?alert.*?">.*?<\/div>/s);
                const errorText = errorMatch ? errorMatch[0].substring(0, 300) : 'Login failed or error occurred';
                bugs.push({ 
                    step: 'Login', 
                    error: errorText,
                    severity: 'HIGH' 
                });
                console.log('✗ Login failed - error detected');
                await page.screenshot({ path: 'Student_Login_Error.png', fullPage: true, timeout: 120000 });
                await fs.promises.writeFile('Student_Login_Error.html', pageContent);
            } else if (currentUrl.includes('/student/')) {
                testResults.login = true;
                await page.screenshot({ path: 'Student_Login.png', fullPage: true, timeout: 120000 });
                screenshots.push('Student_Login.png');
                console.log('✓ Login successful - redirected to:', currentUrl);
            } else {
                bugs.push({ step: 'Login', error: 'Unexpected redirect: ' + currentUrl, severity: 'HIGH' });
                console.log('✗ Login failed - unexpected URL');
                await page.screenshot({ path: 'Student_Login_Error.png', fullPage: true, timeout: 120000 });
                await fs.promises.writeFile('Student_Login_Error.html', pageContent);
            }
        } catch (error) {
            bugs.push({ step: 'Login', error: error.message, severity: 'HIGH' });
            console.log('✗ Login failed:', error.message);
        }

        if (!testResults.login) {
            console.log('\n⚠ Cannot continue without login. Stopping test.');
            await browser.close();
            return generateReport(screenshots, bugs, testResults);
        }

        // ============================================
        // STEP 3: DASHBOARD
        // ============================================
        console.log('\n[3] Testing Dashboard...');
        try {
            await page.goto('http://127.0.0.1:8000/student/dashboard', { waitUntil: 'domcontentloaded', timeout: 120000 });
            await page.waitForTimeout(3000);
            
            const content = await page.content();
            const hasError = content.includes('Whoops') || content.includes('Exception') || content.includes('ErrorException');
            
            if (hasError) {
                const errorMatch = content.match(/<pre>.*?Exception.*?<\/pre>/s);
                bugs.push({ 
                    step: 'Dashboard', 
                    error: errorMatch ? errorMatch[0].substring(0, 200) : 'Exception found',
                    severity: 'HIGH' 
                });
            } else {
                testResults.dashboard = true;
                await page.screenshot({ path: 'Student_Dashboard.png', fullPage: true, timeout: 120000 });
                screenshots.push('Student_Dashboard.png');
                console.log('✓ Dashboard loaded successfully');
            }
        } catch (error) {
            bugs.push({ step: 'Dashboard', error: error.message, severity: 'HIGH' });
            console.log('✗ Dashboard failed:', error.message);
        }

        // ============================================
        // STEP 4: PROFILE
        // ============================================
        console.log('\n[4] Testing Profile...');
        try {
            await page.goto('http://127.0.0.1:8000/profile', { waitUntil: 'domcontentloaded', timeout: 120000 });
            await page.waitForTimeout(2000);
            
            const content = await page.content();
            const hasError = content.includes('Whoops') || content.includes('Exception') || content.includes('ErrorException');
            
            if (hasError) {
                bugs.push({ step: 'Profile', error: 'Exception found on profile page', severity: 'HIGH' });
            } else {
                testResults.profile = true;
                await page.screenshot({ path: 'Student_Profile.png', fullPage: true, timeout: 120000 });
                screenshots.push('Student_Profile.png');
                console.log('✓ Profile loaded successfully');
            }
        } catch (error) {
            bugs.push({ step: 'Profile', error: error.message, severity: 'HIGH' });
            console.log('✗ Profile failed:', error.message);
        }

        // ============================================
        // STEP 5: MY GROUP
        // ============================================
        console.log('\n[5] Testing My Group...');
        try {
            await page.goto('http://127.0.0.1:8000/student/groups', { waitUntil: 'domcontentloaded', timeout: 120000 });
            await page.waitForTimeout(2000);
            
            const content = await page.content();
            const hasError = content.includes('Whoops') || content.includes('Exception') || content.includes('ErrorException');
            
            if (hasError) {
                bugs.push({ step: 'My Group', error: 'Exception found', severity: 'HIGH' });
            } else {
                testResults.groups = true;
                await page.screenshot({ path: 'Student_Group.png', fullPage: true, timeout: 120000 });
                screenshots.push('Student_Group.png');
                console.log('✓ My Group loaded successfully');
            }
        } catch (error) {
            bugs.push({ step: 'My Group', error: error.message, severity: 'HIGH' });
            console.log('✗ My Group failed:', error.message);
        }

        // ============================================
        // STEP 6: LESSONS
        // ============================================
        console.log('\n[6] Testing Lessons...');
        try {
            console.log('⚠ Student lessons route does not exist (by design, integrated into resources)');
        } catch (error) {
            bugs.push({ step: 'Lessons', error: error.message, severity: 'HIGH' });
            console.log('✗ Lessons failed:', error.message);
        }

        // ============================================
        // STEP 7: RESOURCES
        // ============================================
        console.log('\n[7] Testing Resources...');
        try {
            await page.goto('http://127.0.0.1:8000/student/resources', { waitUntil: 'domcontentloaded', timeout: 120000 });
            await page.waitForTimeout(2000);
            
            const content = await page.content();
            const hasError = content.includes('Whoops') || content.includes('Exception') || content.includes('ErrorException');
            
            if (hasError) {
                const errorMatch = content.match(/<pre>.*?Exception.*?<\/pre>/s);
                bugs.push({ 
                    step: 'Resources', 
                    error: errorMatch ? errorMatch[0].substring(0, 200) : 'Exception found',
                    severity: 'HIGH' 
                });
            } else {
                testResults.resources = true;
                await page.screenshot({ path: 'Student_Resources.png', fullPage: true, timeout: 120000 });
                screenshots.push('Student_Resources.png');
                console.log('✓ Resources loaded successfully');
            }
        } catch (error) {
            bugs.push({ step: 'Resources', error: error.message, severity: 'HIGH' });
            console.log('✗ Resources failed:', error.message);
        }

        // ============================================
        // STEP 8: EXAMS
        // ============================================
        console.log('\n[8] Testing Exams...');
        try {
            await page.goto('http://127.0.0.1:8000/student/exams', { waitUntil: 'domcontentloaded', timeout: 120000 });
            await page.waitForTimeout(2000);
            
            const content = await page.content();
            const hasError = content.includes('Whoops') || content.includes('Exception') || content.includes('ErrorException');
            
            if (hasError) {
                bugs.push({ step: 'Exams', error: 'Exception found', severity: 'HIGH' });
            } else {
                testResults.exams = true;
                await page.screenshot({ path: 'Student_Exams.png', fullPage: true, timeout: 120000 });
                screenshots.push('Student_Exams.png');
                console.log('✓ Exams loaded successfully');
            }
        } catch (error) {
            bugs.push({ step: 'Exams', error: error.message, severity: 'HIGH' });
            console.log('✗ Exams failed:', error.message);
        }

        // ============================================
        // STEP 9: GRADES
        // ============================================
        console.log('\n[9] Testing Grades...');
        try {
            await page.goto('http://127.0.0.1:8000/student/account', { waitUntil: 'domcontentloaded', timeout: 120000 });
            await page.waitForTimeout(2000);
            
            const content = await page.content();
            const hasError = content.includes('Whoops') || content.includes('Exception') || content.includes('ErrorException');
            
            if (hasError) {
                bugs.push({ step: 'Grades', error: 'Exception found on My Account', severity: 'HIGH' });
            } else {
                const hasExamAttempts = content.includes('امتحان') || content.includes('exam') || content.includes('result');
                if (hasExamAttempts) {
                    testResults.grades = true;
                    console.log('✓ Grades/Exam attempts found on My Account');
                } else {
                    console.log('⚠ No exam attempts/grades found on My Account (may be normal if no exams taken)');
                }
            }
        } catch (error) {
            bugs.push({ step: 'Grades', error: error.message, severity: 'HIGH' });
            console.log('✗ Grades failed:', error.message);
        }

        // ============================================
        // STEP 10: ATTENDANCE
        // ============================================
        console.log('\n[10] Testing Attendance...');
        try {
            await page.goto('http://127.0.0.1:8000/student/account', { waitUntil: 'domcontentloaded', timeout: 120000 });
            await page.waitForTimeout(2000);
            
            const content = await page.content();
            const hasError = content.includes('Whoops') || content.includes('Exception') || content.includes('ErrorException');
            
            if (hasError) {
                bugs.push({ step: 'Attendance', error: 'Exception found on My Account', severity: 'HIGH' });
            } else {
                const hasAttendance = content.includes('حضور') || content.includes('attendance') || content.includes('غياب');
                if (hasAttendance) {
                    testResults.attendance = true;
                    await page.screenshot({ path: 'Student_Attendance.png', fullPage: true, timeout: 120000 });
                    screenshots.push('Student_Attendance.png');
                    console.log('✓ Attendance info found on My Account');
                } else {
                    console.log('⚠ No attendance info found on My Account (may be normal if no attendance records)');
                }
            }
        } catch (error) {
            bugs.push({ step: 'Attendance', error: error.message, severity: 'HIGH' });
            console.log('✗ Attendance failed:', error.message);
        }

        // ============================================
        // STEP 11: NOTIFICATIONS
        // ============================================
        console.log('\n[11] Testing Notifications...');
        try {
            await page.goto('http://127.0.0.1:8000/notifications', { waitUntil: 'domcontentloaded', timeout: 120000 });
            await page.waitForTimeout(2000);
            
            const content = await page.content();
            const hasError = content.includes('Whoops') || content.includes('Exception') || content.includes('ErrorException');
            
            if (hasError) {
                bugs.push({ step: 'Notifications', error: 'Exception found', severity: 'HIGH' });
            } else {
                testResults.notifications = true;
                await page.screenshot({ path: 'Student_Notifications.png', fullPage: true, timeout: 120000 });
                screenshots.push('Student_Notifications.png');
                console.log('✓ Notifications loaded successfully');
            }
        } catch (error) {
            bugs.push({ step: 'Notifications', error: error.message, severity: 'HIGH' });
            console.log('✗ Notifications failed:', error.message);
        }

        // ============================================
        // STEP 12: FILE DOWNLOADS
        // ============================================
        console.log('\n[12] Testing File Downloads...');
        try {
            await page.goto('http://127.0.0.1:8000/student/resources', { waitUntil: 'domcontentloaded', timeout: 120000 });
            await page.waitForTimeout(2000);
            
            const downloadLink = await page.locator('a[href*="download"], a[download], .download-link').first();
            
            if (await downloadLink.count() > 0 && await downloadLink.isVisible()) {
                const [download] = await Promise.all([
                    page.waitForEvent('download', { timeout: 10000 }),
                    downloadLink.click()
                ]);
                
                const downloadPath = await download.path();
                testResults.downloads = true;
                console.log('✓ File downloaded successfully:', downloadPath);
            } else {
                console.log('⚠ No download links found on resources page');
            }
        } catch (error) {
            bugs.push({ step: 'File Downloads', error: error.message, severity: 'MEDIUM' });
            console.log('⚠ File download test skipped:', error.message);
        }

        // ============================================
        // STEP 13: LOGOUT
        // ============================================
        console.log('\n[13] Testing Logout...');
        try {
            const logoutButton = await page.locator('form[action*="logout"] button, a[href*="logout"]').first();
            
            if (await logoutButton.count() > 0) {
                await logoutButton.click();
                await page.waitForTimeout(3000);
                
                const currentUrl = page.url();
                if (currentUrl.includes('login') || !currentUrl.includes('/student/')) {
                    testResults.logout = true;
                    await page.screenshot({ path: 'Student_Logout.png', fullPage: true, timeout: 120000 });
                    screenshots.push('Student_Logout.png');
                    console.log('✓ Logout successful');
                    
                    await page.goto('http://127.0.0.1:8000/student/resources', { waitUntil: 'domcontentloaded', timeout: 60000 });
                    await page.waitForTimeout(2000);
                    
                    const currentUrl2 = page.url();
                    if (currentUrl2.includes('login')) {
                        console.log('✓ Dashboard properly redirects to login');
                    } else {
                        bugs.push({ step: 'Logout', error: 'Dashboard accessible after logout', severity: 'HIGH' });
                    }
                } else {
                    bugs.push({ step: 'Logout', error: 'Still on student page after logout', severity: 'HIGH' });
                }
            } else {
                bugs.push({ step: 'Logout', error: 'Logout button not found', severity: 'HIGH' });
            }
        } catch (error) {
            bugs.push({ step: 'Logout', error: error.message, severity: 'HIGH' });
            console.log('✗ Logout failed:', error.message);
        }

    } catch (error) {
        console.error('\n=== FATAL ERROR ===');
        console.error(error.message);
        await page.screenshot({ path: 'Student_Fatal_Error.png', fullPage: true, timeout: 120000 });
        bugs.push({ step: 'Fatal', error: error.message, severity: 'CRITICAL' });
    } finally {
        await browser.close();
    }

    return generateReport(screenshots, bugs, testResults);
}

function generateReport(screenshots, bugs, testResults) {
    console.log('\n\n========================================');
    console.log('=== SPRINT 35 - STUDENT QA RESULTS ===');
    console.log('========================================\n');
    
    console.log('📊 Test Results:');
    Object.entries(testResults).forEach(([feature, passed]) => {
        const status = passed ? '✓ PASS' : '✗ FAIL';
        console.log(`  ${status} - ${feature.toUpperCase()}`);
    });
    
    console.log('\n📸 Screenshots Captured:');
    screenshots.forEach(s => console.log(`  - ${s}`));
    
    console.log('\n🐛 Bugs Found:', bugs.length);
    bugs.forEach((bug, i) => {
        console.log(`\n  Bug #${i + 1}:`);
        console.log(`    Step: ${bug.step}`);
        console.log(`    Error: ${bug.error}`);
        console.log(`    Severity: ${bug.severity}`);
    });
    
    const passedCount = Object.values(testResults).filter(v => v).length;
    const totalCount = Object.keys(testResults).length;
    
    console.log('\n========================================');
    console.log(`Success Rate: ${Math.round((passedCount / totalCount) * 100)}% (${passedCount}/${totalCount})`);
    console.log('========================================\n');
    
    return { testResults, bugs, screenshots };
}

runStudentQA().then(result => {
    console.log('\n✓ QA Test Complete');
    process.exit(result.bugs.some(b => b.severity === 'CRITICAL') ? 1 : 0);
}).catch(err => {
    console.error('Test execution failed:', err);
    process.exit(1);
});