import { chromium } from 'playwright';

async function main() {
  const browser = await chromium.launch({
    executablePath: process.env.HOME + '/.cache/ms-playwright/chromium-1243/chrome-linux64/chrome',
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  
  page.on('console', msg => {
    console.log(`[PAGE LOG ${msg.type()}]:`, msg.text());
  });
  page.on('requestfailed', req => {
    console.log(`[REQ FAILED]: ${req.method()} ${req.url()} - ${req.failure()?.errorText}`);
  });
  page.on('response', res => {
    if (res.status() >= 400) {
      console.log(`[HTTP ERR ${res.status()}]: ${res.url()}`);
    }
  });

  await page.goto('http://localhost:3000', { waitUntil: 'networkidle' });
  await page.evaluate(async () => {
    const res = await fetch('/api/v1/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({
        username: 'admin',
        password: 'Admin@123456',
        challengeId: 'local-challenge',
        captchaAnswer: '74KB9',
      }),
    });
    const data = await res.json();
    if (data.token) localStorage.setItem('crm_session_token', data.token);
  });

  await page.goto('http://localhost:3000/technicians', { waitUntil: 'networkidle' });
  const mapButton = await page.$('button:has-text("Live Field Map")');
  if (mapButton) {
    await mapButton.click();
  }
  await page.waitForTimeout(3000);

  const data = await page.evaluate(() => {
    const gmStyle = document.querySelector('.gm-style');
    const images = Array.from(document.querySelectorAll('.gm-style img')).map(img => img.src);
    const canvasList = Array.from(document.querySelectorAll('.gm-style canvas'));
    
    // Check all divs inside gm-style
    const divInfos = Array.from(document.querySelectorAll('.gm-style div')).slice(0, 15).map(el => ({
      className: el.className,
      style: el.getAttribute('style'),
      w: el.offsetWidth,
      h: el.offsetHeight,
      bg: window.getComputedStyle(el).backgroundColor
    }));

    return {
      gmStyleRect: gmStyle?.getBoundingClientRect(),
      offsetWidth: gmStyle?.offsetWidth,
      offsetHeight: gmStyle?.offsetHeight,
      canvasCount: canvasList.length,
      imageCount: images.length,
      imageSources: images,
      divInfos
    };
  });

  await page.screenshot({ path: '/home/siddharth/.gemini/antigravity-ide/brain/da40d1cd-6695-4857-8d36-5643acd6c15d/scratch/live_map_debug.png' });
  console.log('Screenshot saved to live_map_debug.png');

  console.log('RESULTS:', JSON.stringify(data, null, 2));
  await browser.close();
}

main().catch(console.error);
