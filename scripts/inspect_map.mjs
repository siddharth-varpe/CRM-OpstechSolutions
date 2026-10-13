import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';

async function run() {
  console.log('Launching browser...');
  const browser = await chromium.launch({
    executablePath: process.env.HOME + '/.cache/ms-playwright/chromium-1243/chrome-linux64/chrome',
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 }
  });
  const page = await context.newPage();

  const consoleLogs = [];
  page.on('console', msg => {
    consoleLogs.push(`[${msg.type()}] ${msg.text()}`);
    console.log(`[BROWSER CONSOLE ${msg.type()}] ${msg.text()}`);
  });

  const pageErrors = [];
  page.on('pageerror', err => {
    pageErrors.push(err.toString());
    console.error(`[PAGE ERROR]`, err);
  });

  const failedRequests = [];
  page.on('requestfailed', req => {
    failedRequests.push(`${req.method()} ${req.url()} - ${req.failure()?.errorText}`);
    console.log(`[REQUEST FAILED] ${req.method()} ${req.url()} - ${req.failure()?.errorText}`);
  });

  page.on('response', res => {
    const url = res.url();
    if (url.includes('maps.googleapis.com') || url.includes('google') || url.includes('gstatic') || url.includes('/api/v1/maps')) {
      console.log(`[MAP RESP] ${res.status()} ${url}`);
    }
  });

  try {
    console.log('Authenticating directly via API...');
    await page.goto('http://localhost:3000', { waitUntil: 'networkidle' });

    const loginResult = await page.evaluate(async () => {
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
      if (data.token) {
        localStorage.setItem('crm_session_token', data.token);
      }
      return data;
    });

    console.log('Login result:', loginResult.success ? 'SUCCESS' : JSON.stringify(loginResult));

    console.log('Current URL after login attempt:', page.url());

    console.log('Navigating to /technicians...');
    await page.goto('http://localhost:3000/technicians', { waitUntil: 'networkidle' });
    await page.waitForTimeout(2000);

    // Check if "Live Field Map" button exists and click it
    const mapButton = await page.$('button:has-text("Live Field Map")');
    if (mapButton) {
      console.log('Clicking "Live Field Map" button...');
      await mapButton.click();
      await page.waitForTimeout(3000);
    } else {
      console.log('"Live Field Map" button not found or already on map view');
    }

    // Track all network requests
    page.on('response', res => {
      const url = res.url();
      if (url.includes('maps.googleapis.com') || url.includes('google') || url.includes('gstatic')) {
        console.log(`[MAP RESP] ${res.status()} ${url.split('?')[0]}`);
      }
    });

    // Inspect Google Maps state and DOM
    const mapDiagnostics = await page.evaluate(() => {
      const google = window.google;
      const gmStyle = document.querySelector('.gm-style');
      const gmErr = document.querySelector('.gm-err-container, .gm-err-content, .gm-style-moc');
      const mapContainer = document.querySelector('div[class*="h-\\[750px\\]"]');
      
      return {
        hasGoogle: !!google,
        hasGoogleMaps: !!(google && google.maps),
        hasMapInstance: !!(google && google.maps && google.maps.Map),
        gmStyleExists: !!gmStyle,
        gmStyleChildrenCount: gmStyle ? gmStyle.children.length : 0,
        gmStyleHTMLSnippet: gmStyle ? gmStyle.innerHTML.slice(0, 1000) : null,
        gmErrExists: !!gmErr,
        gmErrText: gmErr ? gmErr.innerText : null,
        containerRect: mapContainer ? mapContainer.getBoundingClientRect() : null,
      };
    });

    console.log('=== MAP DIAGNOSTICS ===');
    console.log(JSON.stringify(mapDiagnostics, null, 2));
  } catch (err) {
    console.error('Test execution failed:', err);
  } finally {
    await browser.close();
  }
}

run();
