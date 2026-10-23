<?php

/*
|--------------------------------------------------------------------------
| Call Centre Portal - Phase 1 (frontend prototype) configuration
|--------------------------------------------------------------------------
| Roles, the default permission baseline, navigation and prototype defaults.
| In Phase 2 the permission baseline moves into the database (role + record
| grants) and is enforced server-side by policies. Here it only drives which
| screens, columns and actions the *demo* renders for the selected demo role.
*/

return [

    'name' => 'Call Centre Portal',

    // Display-only default currency (assumption register: "Currency / taxes").
    'currency' => 'USD',
    'timezone_label' => 'UTC',

    'roles' => [
        'admin' => ['label' => 'Admin', 'user' => 'U-01', 'blurb' => 'Global access and configuration'],
        'finance' => ['label' => 'Finance', 'user' => 'U-02', 'blurb' => 'Handoff, agreements, Square, commissions'],
        'auditor' => ['label' => 'Auditor of Project', 'user' => 'U-04', 'blurb' => 'Assigned audits, guidance and quotes'],
        'manager' => ['label' => 'Manager', 'user' => 'U-06', 'blurb' => 'Supervises team pipeline and commissions'],
        'team_lead' => ['label' => 'Team Lead', 'user' => 'U-08', 'blurb' => 'Own/team pipeline and closer workflow'],
        'agent' => ['label' => 'Agent', 'user' => 'U-10', 'blurb' => 'Creates and tracks own leads'],
    ],

    /*
    | Default permission baseline (docs section 2.2). "*" = everything.
    | Scope (own / team / assigned / finance) is applied by the repositories.
    */
    'permissions' => [
        'admin' => ['*'],
        'finance' => [
            'leads.view', 'customers.view', 'finance.handoff', 'finance.dashboard', 'finance.square',
            'agreements.view', 'agreements.manage', 'commission.view', 'commission.create',
            'commission.approve', 'commission.manage_rules', 'revenue.mrr', 'learning.view', 'activity.view',
        ],
        'auditor' => [
            'leads.view', 'audit.view', 'audit.author', 'audit.send', 'learning.view', 'activity.view',
        ],
        'manager' => [
            'leads.view', 'leads.create', 'leads.review', 'leads.sales_edit', 'customers.view',
            'audit.view', 'audit.request', 'audit.send', 'calendar.use', 'commission.view',
            'commission.create', 'commission.team', 'revenue.mrr', 'learning.view', 'activity.view',
        ],
        'team_lead' => [
            'leads.view', 'leads.create', 'leads.review_own', 'leads.sales_edit', 'customers.view',
            'audit.view', 'audit.request', 'audit.send', 'calendar.use', 'commission.view',
            'learning.view', 'activity.view',
        ],
        'agent' => [
            'leads.view', 'leads.create', 'customers.view', 'audit.view', 'audit.request', 'audit.send',
            'calendar.use', 'commission.view', 'learning.view', 'activity.view',
        ],
    ],

    /*
    | Sidebar. Each item is shown only when the demo role holds the permission.
    */
    'nav' => [
        ['label' => 'Workspace', 'items' => [
            ['Dashboard', 'dashboard', 'squares-2x2', null],
            ['Leads', 'leads.index', 'user-group', 'leads.view'],
            ['Review queue', 'review.index', 'clipboard-document-check', ['leads.review', 'leads.review_own'], 'review'],
            ['Customers', 'customers.index', 'building-office-2', 'customers.view'],
            ['Calendar', 'calendar.index', 'calendar-days', 'calendar.use'],
            ['Audits & quotes', 'audits.index', 'document-magnifying-glass', 'audit.view', 'audits'],
            ['Learning library', 'learning.index', 'academic-cap', 'learning.view'],
        ]],
        ['label' => 'Finance', 'items' => [
            ['Closed Won queue', 'finance.handoff', 'inbox-arrow-down', 'finance.handoff', 'handoff'],
            ['Agreements', 'agreements.index', 'document-text', 'agreements.view'],
            ['Billing & Square', 'billing.index', 'credit-card', 'finance.square'],
            ['Commissions', 'commissions.index', 'banknotes', 'commission.view'],
            ['Recurring revenue', 'revenue.index', 'arrow-path', 'revenue.mrr'],
            ['Finance dashboard', 'finance.dashboard', 'chart-bar', 'finance.dashboard'],
        ]],
        ['label' => 'Administration', 'items' => [
            ['Users & teams', 'admin.users', 'users', 'admin.users'],
            ['Lead forms', 'forms.index', 'adjustments-horizontal', 'admin.forms'],
            ['Projects & statuses', 'projects.index', 'rectangle-stack', 'admin.projects'],
            ['Visibility overrides', 'admin.visibility', 'eye-slash', 'admin.visibility'],
            ['Square settings', 'integrations.square', 'link', 'admin.integrations'],
            ['Activity & errors', 'admin.activity', 'queue-list', ['admin.audit', 'activity.view']],
            ['Settings & assumptions', 'admin.settings', 'cog-6-tooth', 'admin.settings'],
        ]],
    ],

    /*
    | Prototype defaults (assumption register). Never real policy.
    */
    'defaults' => [
        'commission_eligibility_days' => 15,
        'commission_day_mode' => 'calendar',   // calendar | business
        'commission_trigger' => 'closed_won',
        'residual_period' => 'monthly',
        'reversal_policy' => 'manual_review',
        'duplicate_block' => true,
        'agent_edit_after_accept' => false,
        'reminder_lead_minutes' => 30,
    ],

    'assumptions' => [
        ['Technology / hosting', 'Laravel 13 + Blade + Tailwind CSS + Alpine.js; mock repository adapters behind interfaces; no database in Phase 1.', 'Confirm production stack, hosting, domain, build/deployment path and database.'],
        ['Backend architecture', 'None built. Phase 2 (after your explicit approval) swaps the mock repositories for Eloquent-backed ones in the same Laravel app.', 'Confirm backend framework (Laravel + MySQL proposed) and single-app deployment.'],
        ['Email provider', 'All email is simulated and labelled "Demo simulation". An email service abstraction is represented by the mock send dialogs.', 'SMTP/provider, sender domain authentication, reply-to, bounce handling.'],
        ['Calendar synchronization', 'Internal calendar works alone. Google/Zoom connect, sync and disconnect are simulated.', 'Confirm provider API scopes, one-way vs two-way sync, Zoom scope.'],
        ['Electronic signatures', 'Native signing UI only (typed/drawn, consent, one-time code). Nothing is stored or legally binding.', 'Jurisdictions, identity proof level, consent language, evidence and retention.'],
        ['Square credentials', 'Masked sandbox-style settings UI only. No real keys are ever entered or stored in the frontend.', 'Square app/account, sandbox/live credentials, location, webhook URL, currency.'],
        ['Commission eligibility', '15 calendar days after Closed Won (prototype assumption only; configurable).', 'Calendar vs business days, trigger event, signed-contract prerequisite, exclusions.'],
        ['Commission formulas', 'Fixed amount, percentage and residual fields supported. All rates shown are synthetic sample data, not policy.', 'Rates, tiers, residual period/cap, split-credit rules, payout schedule, reversal policy.'],
        ['Currency / taxes', 'Neutral sample data in USD (display only).', 'Primary currency, tax handling, invoices and accounting treatment.'],
        ['Lead duplicate matching', 'Exact match on normalised email, phone, website domain and business name. Duplicates are blocked; Admin override needs a reason.', 'Exact vs fuzzy thresholds, authorised overrides, merge process.'],
        ['Agent edit after acceptance', 'Submitted lead fields read-only for the Agent; new activity notes still allowed.', 'Whether limited fields can ever be reopened, and by which role.'],
        ['Statuses', 'Documented initial values, Admin-configurable in the prototype UI.', 'Which terminal statuses lock a deal and the rules for reopening.'],
        ['Notification policies', 'In-app plus simulated email, per-user preferences.', 'Reminder lead times, quiet hours, acknowledgements, escalation.'],
        ['Finance Manager', 'Treated as an additional permission set inside the Finance role (toggle in the demo role menu).', 'Whether it becomes a separate role.'],
    ],
];
