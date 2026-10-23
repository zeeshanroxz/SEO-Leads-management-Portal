import { createRequire } from 'module';
import fs from 'fs';
const require = createRequire(process.env.PW_MODULES ? process.env.PW_MODULES.replace(/\/?$/, '/') : import.meta.url);
const { chromium } = require('playwright');
const axeSrc = fs.readFileSync(new URL('./node_modules/axe-core/axe.min.js', import.meta.url), 'utf8');
const base = process.env.BASE_URL || 'http://127.0.0.1:8000';
const browser = await chromium.launch();
const label = { admin: 'Admin', finance: 'Finance', agent: 'Agent' };
const pages = { admin: ['/dashboard', '/leads', '/leads/L-1011', '/leads/create', '/calendar', '/admin/forms/FORM-1', '/audits/A-2001', '/admin/users', '/login'], finance: ['/agreements/AG-3001', '/commissions', '/finance/billing', '/sign/AG-3003'], agent: ['/dashboard', '/leads/L-1008'] };
const summary = {};
for (const role of Object.keys(pages)) {
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const p = await ctx.newPage();
  await p.goto(base + '/login'); await p.locator(`form button:has-text("${label[role]}")`).first().click(); await p.waitForLoadState('networkidle');
  for (const u of pages[role]) {
    await p.goto(base + u, { waitUntil: 'networkidle' });
    await p.evaluate(axeSrc);
    const r = await p.evaluate(async () => (await axe.run(document, { runOnly: ['wcag2a', 'wcag2aa', 'wcag21aa'] })).violations.map((v) => ({ id: v.id, impact: v.impact, n: v.nodes.length, sample: v.nodes[0].target.join(' ').slice(0, 90), help: v.help })));
    for (const v of r) { const k = v.id + ' (' + v.impact + ')'; summary[k] = summary[k] || { help: v.help, pages: [], sample: v.sample }; summary[k].pages.push(u + '·' + v.n); }
  }
  await ctx.close();
}
await browser.close();
for (const [k, v] of Object.entries(summary)) console.log(k, '-', v.help, '\n   pages:', v.pages.slice(0, 6).join(', '), '\n   e.g.', v.sample);
if (!Object.keys(summary).length) console.log('no axe violations');
