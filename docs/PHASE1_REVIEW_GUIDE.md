# Phase 1 review guide — frontend prototype (Laravel)

**Status:** Phase 1 complete and waiting for your review. **Phase 2 (backend) has not been started** and will not start until you reply with an explicit approval such as **“YES — BUILD THE BACKEND”**. If you want UI changes instead, tell me and I stay in Phase 1.

Everything below is fictional mock data. Nothing is sent, charged, signed or synced for real.

---

## 1. Run it

```bash
composer install
cp .env.example .env && php artisan key:generate
npm install && npm run build
php artisan serve            # http://127.0.0.1:8000/login
```

No database is needed. Demo state (leads you create, statuses you change …) lives in the PHP session only; **Reset demo data** (pink bar, or *Settings & assumptions*) restores the seed records.

**Demo bar (pink, top of every screen).** *Preview as* switches between the six roles. It is a frontend preview feature and is labelled as such; it is not authentication. When previewing **Finance**, a *Finance Manager permissions* tick-box toggles the extra permission set.

| Role | Demo identity | Reports to |
|---|---|---|
| Admin | Jordan Hale | — |
| Finance (+ Finance Manager set) | Fiona Reyes | — |
| Auditor of Project | Priya Menon (SEO/Ads) · Tomas Rivera (Web/Automation) | — |
| Manager | Marcus Webb (Alpha Team) · Lena Fischer (Bravo Team) | Admin |
| Team Lead | Sofia Alvarez · Daniel Park | Manager |
| Agent | Amir Khan, Chloe Bennett, Ravi Shah · Nina Petrova, Oliver Grant, Zara Idris (disabled) | Team Lead |

---

## 2. What each role sees (default access intent)

| Capability | Admin | Finance | Auditor | Manager | Team Lead | Agent |
|---|---|---|---|---|---|---|
| Dashboard | global | finance | assigned requests | team | own/team | own |
| Leads list / detail | all | Closed Won only (no call notes unless Admin grants) | leads with their assigned requests | team | own + team | own only |
| Create lead | ✓ | – | – | ✓ | ✓ | ✓ |
| Accept / reject | ✓ | – | – | ✓ team | ✓ **own** leads (team review is an Admin toggle, off) | – |
| Edit accepted lead fields | ✓ (+ reopen groups with reason) | – | – | sales status/notes | sales status/notes | **no** — read-only; can add notes |
| Request audit / quote | ✓ | – | – | ✓ | ✓ | ✓ |
| Author reports | ✓ | – | assigned | – | – | – |
| Send report to client (simulated) | ✓ | – | ✓ | ✓ | ✓ | ✓ if Admin allows |
| Calendar | ✓ | – | – | ✓ | ✓ | ✓ |
| Agreements, signing, offline copies | ✓ | ✓ | – | – | – | – |
| Square settings / billing | settings: Admin · billing: ✓ | ✓ | – | – | – | – |
| Commission records | all | all (manager commissions need Finance Manager) | – | own + team (can add) | own | own |
| Approve / pay commission | ✓ | ✓ | – | – | – | – |
| MRR/MRC after full signature | ✓ | ✓ | – | **all** signed customers (no other finance data) | – | – |
| Users, forms, projects, statuses, overrides, settings | ✓ | – | – | – | – | – |
| Activity / audit trail | all | finance-relevant | audit-related | team | team | own |

Restricted data is **omitted from the rendered page** for roles that may not see it (for example an Agent's lead page has no agreement/billing tab or chip, and the Manager's lead page shows only the signed MRR figure). In Phase 2 the server will omit it from API responses — hiding in the UI is not security.

---

## 3. Screens and routes (all 26 requested screens)

| # | Screen | Route(s) | Notes |
|---|---|---|---|
| 1 | Login mockup | `/login` | Not validated; role cards start the demo |
| 2 | Role dashboards | `/dashboard` | Six variants; charts have labelled axes/units and a “view data table” |
| 3 | Leads list | `/leads` | Search, filters, sort, pagination, role-dependent columns; `?state=loading\|empty\|error` previews states |
| 4 | Create / edit lead | `/leads/create`, `/leads/{id}/edit` | 5-step wizard, dynamic questions, live duplicate check, field-group locks |
| 5 | Lead workspace | `/leads/{id}` | Overview, contacts, activity, audits & quotes, appointments, files, agreement & billing*, commission*, history; six independent status chips |
| 6 | Review queue | `/review-queue` | Accept confirm; reject needs reason **and** note |
| 7 | Customer profile | `/customers`, `/customers/{id}` | Many contacts/opportunities; agreements tab for authorised roles only |
| 8 | Form builder | `/admin/forms`, `/admin/forms/{id}` | Draft → preview → publish version; drag-reorder, conditional questions, versions tab, fixed-field required/optional |
| 9 | Projects & statuses | `/admin/projects` | Audit policy (required/optional/n-a), auditor assignment, form assignment, services, status configuration |
| 10 | Audit & quote queue | `/audits` | Filter by type/status/priority; Admin can assign |
| 11 | Auditor workspace | `/audits/{id}` | Rich-text report editor, internal-notes panel, attachments, version history |
| 12 | Client preview & send | `/audits/{id}/preview` | Branded template, version selector, To/CC/subject/message, **simulated** send recorded on the timeline |
| 13 | Calendar | `/calendar` | Day/Week/Month; create, edit/reschedule, cancel, invitees, double-booking warning |
| 14 | Calendar connections | `/settings/calendar-integrations` | Simulated Google/Zoom connect, sync, error + reconnect |
| 15 | Learning library | `/learning` | Search/filter, version history, Admin publish with audience targeting |
| 16 | Closed Won queue | `/finance/handoff` | “Take it”, build agreement |
| 17 | Agreements | `/agreements`, `/agreements/create`, `/agreements/{id}` | Builder with reusable clauses; document preview; versions, events, evidence; clause library tab |
| 18 | Customer signing mockup | `/sign/{id}` | Review → one-time code (**123456**) → typed/drawn signature + consent → receipt; offline upload |
| 19 | Square & billing | `/integrations/square` (Admin), `/finance/billing` | Masked settings, sandbox/live toggle, test-connection simulation, customer mapping, subscriptions & one-time charges, payments, webhooks (retry/duplicate/invalid signature), sync log, reconciliation |
| 20 | Commissions | `/commissions`, `/commissions/{id}`, `/commissions-rules` | One-time/residual, eligibility, approve → due → paid, hold, reverse, adjustments, rule versions |
| 21 | Finance dashboard | `/finance/dashboard` | Cash in/out vs contracted MRR vs receipts, liability donut, payments table, CSV export of mock figures |
| 22 | Team commissions | `/commissions` (Manager) | Own + team tabs, add team commission |
| 23 | Notifications | `/notifications`, `/notifications/settings` | In-app list, read state, simulated email preferences |
| 24 | Users, hierarchy, permissions, overrides | `/admin/users`, `/admin/visibility` | Role change/disable with reason; record-level override with reason + revoke |
| 25 | Activity & errors | `/admin/activity` | Audit trail (role-scoped) + simulated integration errors |
| 26 | Settings & assumptions | `/admin/settings` | Assumption register + configurable business rules (eligibility days / calendar vs business, reversal policy, currency …) |
| — | Recurring revenue (signed) | `/revenue` | Manager/Finance/Admin MRR/MRC view |

\* only for roles allowed to see it.

---

## 4. Interactions you can try (suggested 15-minute walkthrough)

1. **Create a lead + duplicate warning** — *Agent* → *Create lead*. Choose *Local SEO Growth*; on step 2 type `info@brightsidedental.example`: a duplicate banner appears and submit is blocked. Switch to *Admin*: an override reason field appears.
2. **Review** — *Manager* → *Review queue*: *Reject* without a note (blocked) then with reason + note; *Accept* another. Re-open as the *Agent*: the rejection reason is visible.
3. **Locked after acceptance** — *Agent* → open an accepted lead (e.g. L-1008): no *Edit*, the edit page is disabled; *Add note / call* still works. *Admin* → *Reopen submitted fields…* (reason required) unlocks chosen groups.
4. **Repeat audit requests** — *Agent* on L-1008 → *Request audit / quote* twice: two separate linked records; *Auditor* opens one, edits the report (internal block + notes panel), saves a **new version**, submits; reviewer approves; *Client preview* strips internal content; *Send (demo)* is logged.
5. **Appointments** — *Agent* → *Calendar* → *New event* (invite a Team Lead, see a double-booking warning), reschedule, cancel with reason. *Calendar connections* shows the simulated Google error (“portal event is safe”).
6. **Closed Won → agreement → signing** — *Finance* → *Closed Won queue* → *Build agreement* for L-1004 → *Mark ready* → *Send (demo)* → open the customer signing page, use code `123456`, sign → *Partially Signed* → *Counter-sign* → *Signed* (final PDF action appears). Try the offline upload + review on AG-3003.
7. **Square (all simulated)** — *Admin* → *Square settings*: masked fields, *Test connection*. *Finance* → *Billing & Square*: map customers, simulate a webhook, retry the failed one, reconcile a mismatch.
8. **Commissions** — *Finance* → *Commissions*: *Recalculate eligibility*, approve → mark due → record payout (a paid record can only be adjusted/reversed). Untick *Finance Manager permissions* → manager commissions lock. *Manager* → *Team commissions*; *Agent* → *My commission* (due/paid only).
9. **Admin controls** — *Admin* → *Lead forms* → add a question, publish a version, then create a lead: the new question appears. *Visibility overrides*: L-1004 hides upsell details from its original Agent/Team Lead/Manager. *Projects & statuses*: add a status, deactivate one.
10. **Role hiding** — Agent/Manager/Finance on the same lead (L-1001) to compare what each sees.

---

## 5. What is simulated (and labelled “Demo simulation”)

Email sending (audit/report, client summaries, reminders, learning notifications), Google Calendar / Zoom connection and sync, Square API / webhooks / subscriptions / payments, customer signature capture and the one-time code, file uploads (nothing is stored; editor images are embedded ≤ 300 KB), PDF generation (browser print only), user invitation emails.

---

## 6. Assumptions (also visible in *Settings & assumptions*)

Prototype defaults, **not** business decisions:

- Commission eligibility = Closed Won + **15 calendar days** (days and calendar/business counting are configurable); prerequisite: agreement fully signed. Rates shown (5 % / 2 % / 1 % one-time, 2 % / 1 % residual) are **synthetic samples**.
- Reversal after a signed deal is cancelled: unpaid commissions → *On Hold* (configurable: auto-reverse / none); paid ones are never deleted and get a review note.
- Duplicate match: exact on normalised email, phone, website domain, business name; blocked by default; only Admin may override (reason required).
- Team Leads accept their **own** leads; reviewing their agents' leads is an Admin toggle (off).
- Agents may send approved audits (Admin toggle on) — “if allowed by policy”.
- Finance Manager = extra permission set inside Finance.
- Currency USD (display only), timezone label UTC with no conversion, clause wording is placeholder text (not legal advice), MRR vs MRC treated as the same recurring figure.
- Agreement/clause prices and service prices are sample values.

## 7. Known limitations / honest gaps

- No database: data is session-scoped; opening a second browser gives you fresh seed data. Many requests per page rebuild the data from arrays (fast enough, not scalable — by design).
- Role scoping is simulated in repositories and `perm:` middleware; there is no real authentication or policy layer.
- The report editor is a `contenteditable` mockup (browser `execCommand`); sample chart/image insertion only. Output is allow-list sanitised, but Phase 2 should adopt a maintained editor + sanitiser.
- Calendar: no drag-to-move, no recurring events, timezone is a label only, mobile defaults to the day view.
- Form builder: one “equals” condition per question; no section-level conditions; duplicating a whole form is not implemented.
- No PDF files, no real file storage, no email, no notification delivery.
- Residual commission ledger rows are seeded sample periods (not auto-generated).
- Accessibility was checked with axe-core (WCAG 2.1 A/AA rules) on sample pages; no manual screen-reader or full keyboard audit yet.
- Tested in Chromium only. Dates are relative to “today”, so numbers and screenshots shift day to day.
- Laravel Boost / MCP tooling from the starter skeleton was not installed.

## 8. Tests and checks actually run

| Check | Result |
|---|---|
| `php artisan test` (PHPUnit — 27 tests, 157 assertions: role access, duplicate matching, review rules, agent lock, status reasons, validation, commission lifecycle, signing flow, offline review, reversal policy, sanitiser, status config) | **27 passed** |
| `qa/crawl.mjs` — 57 URLs × 6 roles in headless Chromium | no 5xx, no console/page errors; restricted pages return the intended 403 screen |
| `qa/flows.mjs` — 12 scripted browser flows (lead wizard validation, duplicate block/override, reject/accept, agent lock, repeat audit, calendar create/reschedule/cancel, agreement + signing + counter-sign, Square test/event/reconcile, commission lifecycle, role hiding, form-builder publish, admin controls) | **12 passed** |
| `qa/mobile.mjs` — 390 px and 820 px widths on key pages | no horizontal page overflow |
| `qa/axe.mjs` — axe-core WCAG 2.1 A/AA on sample pages | no violations (after contrast fixes) |
| `npm run build` | succeeds |

Not run: cross-browser testing, load/performance testing, manual assistive-technology testing.

## 9. Proposed Phase 2 (only after your explicit approval)

1. Replace mock repositories with Eloquent implementations of `app/Contracts/*`; migrations + seeders; MySQL.
2. Real authentication (MFA option), roles/permissions + record grants as **server-side policies**; omit restricted fields from responses.
3. Validated form requests (including race-safe duplicate detection), safe file storage, queued email with delivery logging, audit log writes, notification delivery.
4. Square: encrypted server-side credentials, verified idempotent webhooks, customer/subscription/payment sync, reconciliation.
5. Google/Zoom OAuth (after confirming provider capabilities), sync with provider-ID de-duplication.
6. Native signing evidence capture and immutable signed versions (after legal/compliance review), PDF generation.
7. Commission ledger with versioned rules and reversal policy; finance reporting.
8. Tests, deployment documentation, and the third-party account tasks you need to do (Square app + webhook, email provider/domain, Google/Zoom apps).

## 10. Decisions I need from you before/while building Phase 2

Production stack/hosting & database (Laravel + MySQL proposed) · email provider and sender domain · Google/Zoom scope & sync direction · e-signature jurisdictions, identity proof level and consent wording · Square account/currency/subscription setup · commission trigger/prerequisites, calendar vs business days, rates/tiers/residual caps/split credit and reversal policy · currency & tax handling · duplicate matching thresholds and merge process · whether Agents may ever edit locked fields · which statuses lock a deal · notification/reminder policies.
