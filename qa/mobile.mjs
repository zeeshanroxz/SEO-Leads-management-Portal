import { createRequire } from 'module';
const require = createRequire(process.env.PW_MODULES ? process.env.PW_MODULES.replace(/\/?$/, '/') : import.meta.url);
const { chromium } = require('playwright');
const base = process.env.BASE_URL || 'http://127.0.0.1:8000';
const browser = await chromium.launch();
const label = { admin: 'Admin', finance: 'Finance', agent: 'Agent', manager: 'Manager' };
const pages = { admin: ['/dashboard', '/leads', '/leads/L-1011', '/leads/create', '/calendar', '/admin/forms/FORM-1', '/audits/A-2001', '/agreements/AG-3001', '/commissions', '/finance/billing', '/admin/users', '/customers/C-001', '/learning'], agent: ['/dashboard', '/leads', '/leads/L-1008', '/calendar'], finance: ['/finance/dashboard', '/agreements/AG-3001', '/finance/handoff'] };
let bad = 0;
for (const w of [390, 820]) {
  for (const role of Object.keys(pages)) {
    const ctx = await browser.newContext({ viewport: { width: w, height: 800 }, isMobile: w < 500 });
    const p = await ctx.newPage();
    await p.goto(base + '/login'); await p.locator(`form button:has-text("${label[role]}")`).first().click(); await p.waitForLoadState('networkidle');
    for (const u of pages[role]) {
      await p.goto(base + u, { waitUntil: 'networkidle' });
      const over = await p.evaluate(() => ({ sw: document.documentElement.scrollWidth, iw: window.innerWidth, culprit: [...document.querySelectorAll('body *')].filter((e) => e.getBoundingClientRect().right > window.innerWidth + 1 && !e.closest('.overflow-x-auto, [class*="overflow-"], aside, header nav, .fixed')).slice(0, 3).map((e) => e.tagName + '.' + (e.className.toString().slice(0, 50))) }));
      const flag = over.sw > over.iw + 1;
      if (flag) { bad++; console.log(`OVERFLOW ${w}px [${role}] ${u} scrollWidth=${over.sw}`, over.culprit); }
      if (u === '/dashboard' || u === '/leads/L-1011') await p.screenshot({ path: `shots/m${w}_${role}${u.replace(/\W+/g, '_')}.png`, fullPage: false });
    }
    await ctx.close();
  }
}
await browser.close();
console.log(bad ? bad + ' overflow(s)' : 'no horizontal page overflow');
