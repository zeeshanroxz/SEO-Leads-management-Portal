# Project notes for Claude Code

## NON-NEGOTIABLE PHASE GATE
- This repo is in **Phase 1 — frontend prototype with mock data only.**
- Do **not** add a database, migrations, real auth, payment processing, real email sending, real OAuth/Square/Google/Zoom calls, or secrets/API keys.
- Do **not** start Phase 2 (backend) until the owner explicitly approves, e.g. "YES — BUILD THE BACKEND". If the owner asks for UI changes instead, stay in Phase 1.
- Label every simulated external operation (email, signature, payment, sync) as **"Demo simulation"** in the UI. Never claim a real signature/payment/email happened.

## Stack & conventions
- Laravel 13, Blade components, Tailwind 4 (`resources/css/app.css`), Alpine 3, Chart.js. Bundled by Vite — no CDN scripts.
- UI talks only to the contracts in `app/Contracts`; the mock adapters live in `app/Mock/Repositories` and read `app/Mock/Data/*` plus the session overlay `App\Mock\DemoStore`.
- Records are `App\Mock\Record` (Fluent) so Blade reads `$lead->business_name` exactly as it will read an Eloquent model.
- Permissions for the demo are in `config/portal.php`; use `@perm('x')` / `Demo::can('x')`. Route guards are `perm:` middleware (UI simulation only).
- Role-scoped data (what a role may see) is applied in the repositories (`scoped()` methods). Restricted fields must be **omitted** for unauthorised roles, not just hidden with CSS.
- New status values go in `config/statuses.php`; chips are rendered with `<x-status dim="…" :value="…" />`.
- Icons: `<x-ico name="…" />` (Heroicons). Do not name a component `x-icon` (clashes with Blade Icons).
- Dates in mock data are relative to today (`App\Mock\MockData::d()/dt()`).

## Commands
- `composer test` (or `php artisan test`) — PHPUnit.
- `npm run build` — production assets. `php artisan serve` — dev server.
- `qa/` — Playwright crawler/flows (see `qa/README.md`).

## Rules from the product spec worth remembering
- Independent status dimensions: lead review, sales opportunity, audit/quote, agreement, billing, commission. Never collapse them.
- Rejected needs a note; Not Interested needs a reason; Closed Lost needs a reason + note. Closed Won ≠ signed ≠ paid.
- Accepted lead fields are read-only for the Agent; activity notes are separate. Admin can reopen field groups with a logged reason.
- Audit/quote requests are separate linked records with versions; never overwrite an older report.
- Commission eligibility = Closed Won + 15 days (a *prototype assumption*, configurable calendar/business days). Never invent real rates — samples are marked synthetic.
- Never delete signed agreements or paid commissions; use linked adjustments/reversals.
