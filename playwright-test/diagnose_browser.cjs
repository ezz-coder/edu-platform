const { chromium } = require('playwright');

async function diagnose() {
    console.log('=== BROWSER ENVIRONMENT DIAGNOSTIC ===\n');
    
    const browser = await chromium.launch({ 
        headless: false,
        args: [
            '--start-maximized',
            '--force-device-scale-factor=1',
            '--disable-web-security',
            '--disable-features=IsolateOrigins,site-per-process'
        ]
    });
    
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        deviceScaleFactor: 1,
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    });
    
    const page = await context.newPage();
    
    console.log('Browser launched with:');
    console.log('  - Viewport: 1920x1080');
    console.log('  - Device Scale Factor: 1');
    console.log('  - Headless: false');
    console.log('  - Args: --start-maximized, --force-device-scale-factor=1');
    console.log('  - User Agent: Chrome/120 on Windows 10\n');
    
    await page.goto('http://127.0.0.1:8000/', { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(5000);
    
    console.log('Page loaded. Taking screenshot...');
    await page.screenshot({ path: 'Browser_Problem.png', fullPage: true });
    
    // Check for potential zoom issues
    const zoomInfo = await page.evaluate(() => {
        return {
            devicePixelRatio: window.devicePixelRatio,
            innerWidth: window.innerWidth,
            innerHeight: window.innerHeight,
            outerWidth: window.outerWidth,
            outerHeight: window.outerHeight,
            screenWidth: window.screen.width,
            screenHeight: window.screen.height,
            visualViewportScale: window.visualViewport ? window.visualViewport.scale : 'N/A'
        };
    });
    
    console.log('\nWindow dimensions:');
    console.log('  - devicePixelRatio:', zoomInfo.devicePixelRatio);
    console.log('  - innerWidth:', zoomInfo.innerWidth);
    console.log('  - innerHeight:', zoomInfo.innerHeight);
    console.log('  - visualViewport scale:', zoomInfo.visualViewportScale);
    
    // Check for injected styles or large icons
    const iconInfo = await page.evaluate(() => {
        const icons = document.querySelectorAll('i[class*="bi-"], .bi');
        const sizes = Array.from(icons).slice(0, 10).map(icon => {
            const style = window.getComputedStyle(icon);
            return {
                class: icon.className,
                fontSize: style.fontSize,
                width: style.width,
                height: style.height
            };
        });
        return { iconCount: icons.length, sampleSizes: sizes };
    });
    
    console.log('\nIcon information:');
    console.log('  - Total icons found:', iconInfo.iconCount);
    console.log('  - Sample sizes:', JSON.stringify(iconInfo.sampleSizes, null, 2));
    
    console.log('\n✓ Diagnostic complete. Check Browser_Problem.png');
    console.log('  Close the browser manually to exit.');
    
    await page.waitForTimeout(60000);
    await browser.close();
}

diagnose().catch(err => {
    console.error('Error:', err);
    process.exit(1);
});