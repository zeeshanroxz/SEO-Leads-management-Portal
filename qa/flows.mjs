import { createRequire } from 'module';
const require = createRequire(process.env.PW_MODULES ? process.env.PW_MODULES.replace(/\/?$/, '/') : import.meta.url);
const { chromium } = require('playwright');
const base = process.env.BASE_URL || 'http://127.0.0.1:8000';
const only = process.argv[2];
const browser = await chromium.launch();
const results = [];
const label = { admin: 'Admin', finance: 'Finance', auditor: 'Auditor of Project', manager: 'Manager', team_lead: 'Team Lead', agent: 'Agent' };

async function session(role) {
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  const errs = [];
  page.on('pageerror', (e) => errs.push(e.message));
  page.on('console', (m) => { if (m.type() === 'error' && !m.text().includes('Failed to load resource')) errs.push(m.text()); });
  await page.goto(base + '/login');
  await page.locator(`form button:has-text("${label[role]}")`).first().click();
  await page.waitForLoadState('networkidle');
  return { ctx, page, errs };
}
const switchRole = async (page, role) => { await Promise.all([page.waitForNavigation(), page.locator('#demo-role').selectOption(role)]); await page.waitForLoadState('networkidle'); };
const D = (p) => p.locator('[role=dialog]:visible, [role=alertdialog]:visible');
const vis = async (loc, msg) => { try { await loc.first().waitFor({ state: 'visible', timeout: 3000 }); } catch { throw new Error('ASSERT: ' + msg); } };
const ok = (c, msg) => { if (!c) throw new Error('ASSERT: ' + msg); };
async function flow(name, fn) {
  if (only && !name.toLowerCase().includes(only.toLowerCase())) return;
  let s;
  try { s = await fn(); ok(!s.errs.length, 'JS errors: ' + s.errs.join(' | ')); results.push(['PASS', name]); }
  catch (e) { results.push(['FAIL', name + ' — ' + e.message.split('\n')[0]]); }
  finally { await s?.ctx?.close().catch(() => {}); }
}
const toast = async (page, text) => { await page.locator('[aria-live="polite"]').getByText(text, { exact: false }).first().waitFor({ timeout: 4000 }); };

// ---------------------------------------------------------------- flows
await flow('F1 agent creates a lead (wizard + validation) and it lands in Pending Review', async () => {
  const s = await session('agent'); const p = s.page;
  await p.goto(base + '/leads/create');
  await p.getByRole('button', { name: /^Continue/ }).click();                       // step1 without project
  await vis(p.getByText('Choose a project.'), 'project validation message');
  await p.selectOption('#project_id', 'P-SEO');
  await p.getByRole('button', { name: /^Continue/ }).click();
  await p.getByRole('button', { name: /^Continue/ }).click();                       // step2 empty
  await vis(p.getByText('Business name is required.'), 'business name validation');
  await p.fill('#business_name', 'Zephyr Test Bakery');
  await p.fill('#business_email', 'bad-email');
  await p.getByRole('button', { name: /^Continue/ }).click();
  await vis(p.getByText('Enter a valid email address.'), 'email format validation');
  await p.fill('#business_email', 'hello@zephyrtestbakery.example'); await p.fill('#business_phone', '+1 555 0177'); await p.fill('#business_address', '5 Test Street');
  await p.getByRole('button', { name: /^Continue/ }).click();
  await p.fill('#cn0', 'Jane Roe'); await p.fill('#cd0', 'Owner');
  await p.getByRole('button', { name: /^Continue/ }).click();
  await p.getByRole('button', { name: /^Continue/ }).click();                       // required questions empty
  await vis(p.getByText('"Current website URL" is required.'), 'custom required question validation');
  await p.fill('#q_q_current_site', 'https://zephyrtestbakery.example');
  await p.selectOption('#q_q_service_area', 'Regional');
  await p.getByLabel('More phone calls').check();
  await p.getByRole('button', { name: /^Continue/ }).click();
  await p.getByRole('button', { name: /Submit for review/ }).click();
  await p.waitForURL(/\/leads\/L-\d+/);
  ok(await p.getByText('Pending Review').first().isVisible(), 'pending review chip');
  await p.goto(base + '/leads');
  ok(await p.getByText('Zephyr Test Bakery').first().isVisible(), 'new lead in list');
  return s;
});

await flow('F2 duplicate warning blocks agent; admin can override with a reason', async () => {
  const s = await session('agent'); const p = s.page;
  await p.goto(base + '/leads/create'); await p.selectOption('#project_id', 'P-SEO');
  await p.getByRole('button', { name: /^Continue/ }).click();
  await p.fill('#business_name', 'Brightside Dental Group'); await p.fill('#business_email', 'info@brightsidedental.example');
  await p.waitForSelector('text=Possible duplicate', { timeout: 5000 });
  ok(await p.getByText('Matched on:').first().isVisible(), 'matched-on shown');
  ok(await p.getByText('Admin override reason').count() === 0 || !(await p.locator('#ovr').isVisible()), 'agent has no override field');
  await switchRole(p, 'admin');
  await p.goto(base + '/leads/create'); await p.selectOption('#project_id', 'P-SEO');
  await p.getByRole('button', { name: /^Continue/ }).click();
  await p.fill('#business_name', 'Brightside Dental Group'); await p.fill('#business_email', 'info@brightsidedental.example');
  await p.waitForSelector('text=Possible duplicate'); ok(await p.locator('#ovr').isVisible(), 'admin sees override reason field');
  return s;
});

await flow('F3 manager rejects with mandatory reason, then accepts', async () => {
  const s = await session('manager'); const p = s.page;
  await p.goto(base + '/review-queue');
  await p.getByRole('button', { name: /Reject/ }).first().click();
  await D(p).locator('button:has-text("Reject lead")').click();
  await vis(D(p).getByText('A rejection note of at least 10 characters is mandatory.'), 'rejection note validation');
  await D(p).locator('#reason').selectOption('Outside service area'); await D(p).locator('#notes').fill('Region not served by our team.');
  await D(p).locator('button:has-text("Reject lead")').click();
  await p.waitForLoadState('networkidle'); await toast(p, 'rejected');
  const before = await p.locator('tbody tr[data-row]:visible').count();
  await p.getByRole('button', { name: /Accept/ }).first().click();
  await D(p).locator('button:has-text("Accept lead")').click();
  await p.waitForLoadState('networkidle'); await toast(p, 'accepted');
  ok((await p.locator('tbody tr[data-row]:visible').count()) < before, 'queue shrinks after accept');
  return s;
});

await flow('F4 agent: submitted fields read-only after acceptance, notes still allowed', async () => {
  const s = await session('agent'); const p = s.page;
  await p.goto(base + '/leads/L-1008');
  ok(await p.getByText('Submission locked').first().isVisible(), 'locked badge');
  ok(await p.getByRole('link', { name: 'Edit' }).count() === 0, 'no Edit action');
  await p.goto(base + '/leads/L-1008/edit');
  await vis(p.getByText('Submitted fields are locked after acceptance'), 'locked banner on edit page');
  ok(await p.locator('#business_name').isDisabled(), 'business name input disabled');
  await p.goto(base + '/leads/L-1008#activity');
  await p.getByRole('button', { name: /Add note \/ call/ }).first().click();
  await D(p).locator('#title').fill('Spoke to Samir'); await D(p).locator('button:has-text("Add to timeline")').click();
  await p.waitForLoadState('networkidle'); await toast(p, 'Activity added');
  return s;
});

await flow('F5 agent raises a repeat audit request (separate record)', async () => {
  const s = await session('agent'); const p = s.page;
  await p.goto(base + '/leads/L-1008');
  await p.getByRole('button', { name: /Request audit \/ quote/ }).first().click();
  await D(p).locator('#type').selectOption('quotation'); await D(p).locator('#notes').fill('Need a quote for the content package too.');
  await D(p).locator('button:has-text("Submit request")').click();
  await p.waitForURL(/\/audits\/A-/);
  await p.goto(base + '/leads/L-1008#audits'); await p.getByRole('tab', { name: /Audits/ }).click();
  ok((await p.locator('a:has-text("Open")').count()) >= 2, 'two linked requests on the lead');
  return s;
});

await flow('F6 calendar: create, reschedule and cancel an event', async () => {
  const s = await session('agent'); const p = s.page;
  await p.goto(base + '/calendar'); await p.getByRole('button', { name: 'New event' }).click();
  await p.fill('#ev-title', 'QA test call'); await p.locator('#ev-end').fill('10:00'); await p.locator('#ev-start').fill('10:30');
  await vis(D(p).getByText('The end time must be after the start time.'), 'time validation');
  await p.locator('#ev-start').fill('09:00'); await p.locator('#ev-end').fill('09:30');
  await D(p).locator('button:has-text("Create event")').click();
  await p.waitForLoadState('networkidle'); await toast(p, 'Event created');
  ok(await p.getByRole('dialog').getByText('QA test call').isVisible(), 'detail opens for the new event');
  await p.getByRole('button', { name: 'Reschedule / edit' }).click();
  await p.locator('#ev-start').fill('15:00'); await p.locator('#ev-end').fill('15:30');
  await D(p).locator('button:has-text("Save changes")').click();
  await p.waitForLoadState('networkidle'); ok(await p.getByRole('dialog').getByText('Rescheduled').isVisible(), 'status rescheduled');
  await p.getByRole('button', { name: 'Cancel event' }).click();
  await D(p).locator('textarea').fill('Customer asked to cancel.'); await D(p).locator('button:has-text("Cancel event")').last().click();
  await p.waitForLoadState('networkidle'); await toast(p, 'cancelled');
  return s;
});

await flow('F7 agreement: build -> send -> customer signs (demo) -> counter-sign', async () => {
  const s = await session('finance'); const p = s.page;
  await p.goto(base + '/agreements/create?lead=L-1004');
  await p.getByRole('button', { name: 'Create draft agreement' }).click(); await p.waitForURL(/\/agreements\/AG-\d+/);
  const id = p.url().match(/AG-\d+/)[0];
  await p.getByRole('button', { name: 'Mark ready to send' }).click(); await p.waitForLoadState('networkidle');
  await p.getByRole('button', { name: /Send for signature/ }).click(); await p.waitForLoadState('networkidle');
  ok(await p.getByText('Sent').first().isVisible(), 'status Sent');
  await p.goto(base + '/sign/' + id);
  await p.getByLabel('I have read the full agreement above.').check(); await p.getByRole('button', { name: 'Continue to verification' }).click();
  await p.fill('#code', '000000'); await p.getByRole('button', { name: 'Verify' }).click();
  await vis(p.getByText('That code is not right.'), 'wrong code rejected');
  await p.fill('#code', '123456'); await p.getByRole('button', { name: 'Verify' }).click();
  await p.getByRole('button', { name: /Sign agreement/ }).click(); await vis(p.getByText('Please tick the consent box.'), 'consent required');
  await p.getByLabel(/I agree to sign electronically/).check(); await p.getByRole('button', { name: /Sign agreement/ }).click();
  await p.waitForLoadState('networkidle'); await vis(p.getByText('demo signature recorded'), 'receipt shown');
  await p.goto(base + '/agreements/' + id); ok(await p.getByText('Partially Signed').first().isVisible(), 'partially signed');
  await p.getByRole('button', { name: 'Counter-sign' }).click(); await p.waitForLoadState('networkidle');
  await vis(p.getByRole('button', { name: /Final PDF/ }), 'signed + final PDF action');
  return s;
});

await flow('F8 Square: test connection + simulated event + reconcile', async () => {
  const s = await session('admin'); const p = s.page;
  await p.goto(base + '/integrations/square'); await p.getByRole('button', { name: /Test connection/ }).click(); await toast(p, 'SIMULATED');
  await p.goto(base + '/finance/billing#webhooks'); await p.getByRole('button', { name: 'Send simulated event' }).click(); await toast(p, 'Simulated');
  await p.goto(base + '/finance/billing'); await p.evaluate(() => { location.hash = 'reconciliation'; }); await p.getByRole('button', { name: /Reconcile/ }).first().click();
  await D(p).locator('textarea').fill('Retry succeeded manually.'); await D(p).locator('button:has-text("Mark reconciled")').click();
  await p.waitForLoadState('networkidle'); await toast(p, 'reconciled');
  return s;
});

await flow('F9 commission: recalc -> approve -> due -> paid; manager commissions need Finance Manager', async () => {
  const s = await session('finance'); const p = s.page;
  await p.goto(base + '/commissions');
  const row = p.locator('tr', { hasText: 'CM-5006' });
  await row.getByRole('button', { name: 'Approve' }).click(); await p.waitForLoadState('networkidle');
  await p.locator('tr', { hasText: 'CM-5006' }).getByRole('button', { name: 'Mark due' }).click(); await p.waitForLoadState('networkidle');
  await p.locator('tr', { hasText: 'CM-5006' }).getByRole('button', { name: 'Record payout' }).click();
  await D(p).locator('button:has-text("Mark as paid")').click(); await p.waitForLoadState('networkidle');
  ok(await p.locator('tr', { hasText: 'CM-5006' }).getByText('Paid').first().isVisible(), 'CM-5006 paid');
  await Promise.all([p.waitForNavigation(), p.getByLabel('Finance Manager permissions').uncheck()]);
  await p.goto(base + '/commissions');
  ok(await p.locator('tr', { hasText: 'CM-5007' }).getByText('Finance Manager').isVisible(), 'manager commission locked without Finance Manager');
  return s;
});

await flow('F10 role-based hiding of finance / agreement data', async () => {
  const s = await session('agent'); const p = s.page;
  ok(await p.getByRole('link', { name: 'Agreements' }).count() === 0 && await p.getByRole('link', { name: 'Billing & Square' }).count() === 0, 'agent nav has no finance links');
  await p.goto(base + '/leads/L-1001'); ok(await p.getByRole('tab', { name: /Agreement/ }).count() === 0, 'agent lead has no agreement tab');
  ok(await p.getByText('Agreement:').count() === 0, 'agent does not see agreement chip');
  await p.goto(base + '/leads/L-1004'); await vis(p.getByText('Services & upsell details are restricted'), 'upsell override hides details for original agent');
  const r = await p.goto(base + '/agreements'); ok(r.status() === 403, 'agent cannot open /agreements');
  await switchRole(p, 'manager');
  const r2 = await p.goto(base + '/revenue'); ok(r2.status() === 200, 'manager sees MRR page');
  const r3 = await p.goto(base + '/agreements/AG-3001'); ok(r3.status() === 403, 'manager cannot open agreement content');
  await p.goto(base + '/leads/L-1001'); ok(await p.getByRole('tab', { name: /Agreement/ }).count() === 0, 'manager lead has no agreement tab');
  ok(await p.getByText(/Recurring revenue visible because the agreement is fully signed/).isVisible(), 'manager sees MRR only for fully signed');
  await switchRole(p, 'finance'); await p.goto(base + '/leads/L-1001'); ok(await p.getByRole('tab', { name: 'Activity' }).count() === 0, 'finance has no call-note tab by default');
  await p.goto(base + '/leads/L-1022'); ok(await p.getByRole('tab', { name: 'Activity' }).count() === 1, 'finance sees notes where Admin granted access');
  await switchRole(p, 'auditor'); const r4 = await p.goto(base + '/finance/dashboard'); ok(r4.status() === 403, 'auditor has no finance dashboard');
  return s;
});

await flow('F11 admin: form builder publish creates a new version used by the lead wizard', async () => {
  const s = await session('admin'); const p = s.page;
  await p.goto(base + '/admin/forms/FORM-3');
  await p.getByRole('button', { name: /Add question/ }).first().click();
  await p.fill('#fb-label', 'QA question about budget'); await p.selectOption('#fb-type', 'percentage');
  await p.getByRole('button', { name: /Publish new version/ }).click();
  await D(p).locator('#note').fill('QA added question'); await D(p).locator('button:has-text("Publish")').last().click();
  await p.waitForLoadState('networkidle'); await toast(p, 'New form version published');
  await p.goto(base + '/leads/create'); await p.selectOption('#project_id', 'P-ADS');
  await p.getByRole('button', { name: /^Continue/ }).click();
  await p.fill('#business_name', 'QA Ads Co'); await p.fill('#business_email', 'qa@qaads.example'); await p.fill('#business_phone', '+1 555 0111'); await p.fill('#business_address', '1 QA Way');
  await p.getByRole('button', { name: /^Continue/ }).click();
  await p.fill('#cn0', 'Q A'); await p.fill('#cd0', 'Owner'); await p.getByRole('button', { name: /^Continue/ }).click();
  await vis(p.getByText('QA question about budget'), 'newly published question appears in the lead wizard');
  return s;
});

await flow('F12 admin: override, status config, disable user, learning publish', async () => {
  const s = await session('admin'); const p = s.page;
  await p.goto(base + '/leads/L-1005'); await p.getByRole('button', { name: 'Add override' }).click();
  await D(p).locator('#reason').fill('short'); await D(p).locator('button:has-text("Save override")').click();
  await vis(D(p).getByText('Reason must be at least 10 characters.'), 'override reason validation');
  await D(p).locator('#reason').fill('Testing record-level override reason.'); await D(p).locator('button:has-text("Save override")').click();
  await p.waitForLoadState('networkidle'); await toast(p, 'Visibility override saved');
  await p.goto(base + '/admin/projects#statuses'); await p.getByRole('button', { name: 'Add status' }).first().click();
  await p.locator('form:visible input[name=label]').first().fill('Waiting on legal'); await p.locator('form:visible button:has-text("Add")').first().click();
  await p.waitForLoadState('networkidle'); await toast(p, 'added to');
  await p.goto(base + '/admin/users'); await p.locator('tr', { hasText: 'Oliver Grant' }).getByRole('button', { name: 'Disable' }).click();
  await D(p).locator('textarea').fill('Testing disable'); await D(p).locator('button:has-text("Disable user")').click();
  await p.waitForLoadState('networkidle'); await toast(p, 'disabled');
  await p.goto(base + '/learning'); await p.getByRole('button', { name: 'Publish document' }).click();
  await D(p).locator('#title').fill('QA doc'); await D(p).locator('#description').fill('A QA description for the document.');
  await D(p).locator('button:has-text("Publish")').last().click(); await p.waitForLoadState('networkidle'); await toast(p, 'published');
  return s;
});

await browser.close();
for (const [r, n] of results) console.log(r, n);
console.log(results.filter((r) => r[0] === 'FAIL').length ? '\nSOME FLOWS FAILED' : '\nall flows passed');
