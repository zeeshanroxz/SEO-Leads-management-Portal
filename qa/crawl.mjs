// Usage: node crawl.mjs <role|all> <comma-separated paths> [--shots] [--width=1440]
import { createRequire } from 'module';
const require = createRequire(process.env.PW_MODULES ? process.env.PW_MODULES.replace(/\/?$/, '/') : import.meta.url);
const { chromium } = require('playwright');

const [roleArg, pathsArg, ...flags] = process.argv.slice(2);
const shots = flags.includes('--shots');
const quiet = flags.includes('--quiet');
const tally = {};
const width = parseInt((flags.find((f) => f.startsWith('--width=')) || '--width=1440').split('=')[1]);
const roles = roleArg === 'all' ? ['admin', 'finance', 'auditor', 'manager', 'team_lead', 'agent'] : roleArg.split(',');
const paths = pathsArg.split(',');
const base = process.env.BASE_URL || 'http://127.0.0.1:8000';

const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium-1243/chrome-linux/chrome' }).catch(() => chromium.launch());
let problems = 0;
for (const role of roles) {
  const ctx = await browser.newContext({ viewport: { width, height: 900 } });
  const page = await ctx.newPage();
  const errs = [];
  page.on('console', (m) => { if (m.type() === 'error') errs.push('console: ' + m.text().slice(0, 200)); });
  page.on('pageerror', (e) => errs.push('pageerror: ' + e.message.slice(0, 200)));
  page.on('requestfailed', (r) => errs.push('reqfail: ' + r.url().slice(0, 100)));
  await page.goto(base + '/login');
  await page.locator(`form button:has-text("${{ admin: 'Admin', finance: 'Finance', auditor: 'Auditor of Project', manager: 'Manager', team_lead: 'Team Lead', agent: 'Agent' }[role]}")`).first().click();
  await page.waitForLoadState('networkidle');
  for (const p of paths) {
    errs.length = 0;
    const resp = await page.goto(base + p, { waitUntil: 'networkidle' });
    const status = resp.status();
    const title = await page.title();
    const h1 = (await page.locator('h1').first().textContent().catch(() => ''))?.trim().slice(0, 60);
    const real = errs.filter((e) => !((status === 403 || status === 404) && e.includes('Failed to load resource')));
    const bad = status >= 500 || real.length;
    if (bad) problems++;
    if (!quiet || bad) console.log(`${bad ? 'FAIL' : (status === 403 ? '403 ' : 'ok  ')} [${role}] ${status} ${p}  — ${h1 || title}${real.length ? '\n      ' + [...new Set(real)].join('\n      ') : ''}`);
    tally[role] = tally[role] || {}; tally[role][status] = (tally[role][status] || 0) + 1;
    if (shots) await page.screenshot({ path: `shots/${role}${p.replace(/[^a-z0-9]+/gi, '_')}.png`, fullPage: true });
  }
  await ctx.close();
}
await browser.close();
console.log('status tally per role:', JSON.stringify(tally));
console.log(problems ? `\n${problems} problem page(s)` : '\nall clean');
process.exit(problems ? 1 : 0);
