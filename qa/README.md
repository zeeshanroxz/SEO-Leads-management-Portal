# Browser QA scripts (Phase 1)

Plain Node + Playwright scripts used to check the prototype. They are **not** part of the Laravel app and need Playwright with a Chromium build:

```bash
npm i -D playwright && npx playwright install chromium      # or point PW_MODULES at an existing install
php artisan serve &                                          # http://127.0.0.1:8000  (override with BASE_URL)

node qa/crawl.mjs all "$(cat qa/allpaths.txt)" --quiet      # every screen x every role: no 5xx / console errors
node qa/flows.mjs                                            # 12 scripted flows (lead create, duplicate, reject/accept, signing, commissions …)
node qa/mobile.mjs                                           # 390px / 820px horizontal-overflow check
npm i --no-save axe-core --prefix qa && node qa/axe.mjs      # WCAG 2.1 AA scan of sample pages
node qa/shots.mjs                                            # regenerates docs/screenshots
```

Each flow starts a fresh browser context, so demo state (kept in the PHP session) never leaks between flows.
