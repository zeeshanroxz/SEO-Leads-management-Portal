# Call Centre Portal — Phase 1 frontend prototype (Laravel)

A role-based **CRM · audit & quotation · calendar · agreements · Square billing · commission · finance** portal for a call centre selling SEO, digital-marketing, web-development and automation services.

> ## ⚠️ Phase gate
> **This repository currently contains Phase 1 only — a navigable, interactive frontend prototype on mock data.**
> There is **no database, no real authentication, no real email, no real payments, no Square/Google/Zoom calls and no e-signature storage.**
> The backend (Phase 2) will be built **only after the owner replies with an explicit approval** such as *“YES — BUILD THE BACKEND”*.

| | |
|---|---|
| Framework | Laravel 13 (PHP 8.3+) |
| Views | Blade components + Tailwind CSS 4 + Alpine.js 3 + Chart.js 4 (bundled with Vite — no CDNs) |
| Data | Fictional mock data behind repository contracts (`app/Contracts`), served by session-backed mock adapters (`app/Mock`) — **no DB** |
| Tests | PHPUnit feature/unit tests (`composer test`), Playwright route crawler + scripted flows (`qa/`) |

## Run it

```bash
composer install
cp .env.example .env && php artisan key:generate
npm install
npm run build          # or `npm run dev` while editing views/JS
php artisan serve      # http://127.0.0.1:8000
```

Or in one go: `composer run setup && php artisan serve`.

Open <http://127.0.0.1:8000/login> and pick a role. Use **Preview as** in the pink demo bar to switch roles at any time and **Reset demo data** to restore the seed records.

> The role switcher is a **frontend preview feature only**. It must never become a security mechanism — Phase 2 enforces authorisation on the server and never sends restricted fields to unauthorised users.

Requires: PHP ≥ 8.3 (ext: mbstring, dom, xml, ctype, tokenizer), Composer 2, Node ≥ 20. No database server is needed.

## Where to look

| Path | Purpose |
|---|---|
| `docs/PHASE1_REVIEW_GUIDE.md` | **Start here** — screens/routes, role demo views, interactions, limitations, assumptions, tests |
| `docs/screenshots/` | Screenshots of key screens and states |
| `config/portal.php` | Roles, default permission baseline, navigation, prototype defaults, assumption register |
| `config/statuses.php` | The six independent status dimensions |
| `app/Contracts/*` | Data-access contracts the UI depends on (Phase 2 swaps the implementations) |
| `app/Mock/Data/*` | Fictional seed data (dates are relative to “today”) |
| `app/Mock/Repositories/*` | Session-backed mock adapters (`DemoStore` = in-session overlay, not a DB) |
| `app/Support/Demo.php` | Demo-role helper (`Demo::can()`), **not** authentication |
| `resources/views` | Blade pages (`pages/`), layout and components (`components/`) |
| `resources/js` | Alpine components: data table, calendar, form builder, lead wizard, rich-text editor, signing flow … |
| `routes/web_*.php` | Routes grouped by area (CRM, admin, work, finance) |
| `qa/` | Playwright scripts used for the Phase 1 browser checks |

## Phase 2 (not started)

Phase 2 replaces `app/Mock/Repositories/*` with Eloquent-backed implementations of the same contracts, adds migrations/seeders, real authentication and server-side policies, validated APIs, safe file storage, email queues, Square (server-side secrets + verified webhooks), Google/Zoom OAuth, native-signing evidence, a commission ledger, audit logging, tests and deployment docs. See the review guide for the proposed plan and the open decisions that need an owner answer first.
