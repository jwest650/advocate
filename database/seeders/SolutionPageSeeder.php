<?php

namespace Database\Seeders;

use App\Models\LandingPageCustomPage;
use Illuminate\Database\Seeder;

/**
 * Landing pages for the "Solutions" header dropdown — one per product module.
 * Same shape as the practice-type pages, but each module brings its own
 * screenshot showcase rather than a shared one.
 */
class SolutionPageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $index => $page) {
            LandingPageCustomPage::updateOrCreate(
                ['slug' => $page['slug']],
                array_merge($this->build($page), [
                    'nav_group' => 'solution',
                    'is_active' => true,
                    'sort_order' => 200 + $index,
                ])
            );
        }
    }

    private function pages(): array
    {
        return [
            [
                'title' => 'Client Engagement',
                'slug' => 'client-engagement',
                'icon' => 'HeartHandshake',
                'summary' => 'Intake, CRM, client portal and e-signature',
                'lede' => 'Win the client, then keep them informed without picking up the phone.',
                'context' => 'Most firms lose work in the gap between the first call and the signed engagement letter. Client engagement covers that gap and everything after it, so prospects become clients and clients stop calling to ask what is happening.',
                'hero_image' => '/screenshots/client-advocate-saas-pic.png',
                'features' => [
                    ['UserPlus', 'Lead capture and intake', 'Web forms and intake questionnaires that feed straight into a matter, with no retyping.'],
                    ['Contact', 'Legal CRM', 'See exactly where every prospect stands and what the next step is.'],
                    ['FileSignature', 'E-signature', 'Send engagement letters and retainers for signature and get them back the same day.'],
                    ['LockKeyhole', 'Secure client portal', 'Documents, messages and invoices in one place clients can reach at any hour.'],
                    ['BellRing', 'Automatic status updates', 'Clients are told when something moves, so they never have to ask.'],
                    ['MessagesSquare', 'Threaded messaging', 'Client conversations kept on the matter instead of scattered across inboxes.'],
                ],
                'showcase' => [
                    ['/screenshots/client-advocate-saas-pic.png', 'A portal clients actually use', 'Clients sign in to see their documents, invoices and case status. The routine "any update?" call stops arriving, and the update is always current because it comes from the matter itself.'],
                    ['/screenshots/b-advocate-saas-pic.png', 'From enquiry to engagement', 'Track a prospect from the first enquiry through to a signed retainer, so you can see how much work you are winning and how much is quietly going cold.'],
                ],
            ],
            [
                'title' => 'Firm Management',
                'slug' => 'law-practice-management',
                'icon' => 'LayoutDashboard',
                'summary' => 'Matters, documents, calendar and tasks',
                'lede' => 'One record for every matter, with everything the matter touches attached to it.',
                'context' => 'Firm management is the core of the platform: the case file that documents, deadlines, tasks, notes and time all hang from, shared by everyone who works the file.',
                'hero_image' => '/screenshots/a-advocate-saas-pic.png',
                'features' => [
                    ['FolderKanban', 'Matter records', 'Parties, deadlines, notes and history in one place, for the whole life of the file.'],
                    ['FileStack', 'Document management', 'Version history and full-text search, so the right draft is never in doubt.'],
                    ['CalendarRange', 'Firm calendar', 'Court dates, deadlines and appointments across the whole firm in one view.'],
                    ['ListChecks', 'Task assignment', 'Assign work and see what is outstanding without a status meeting.'],
                    ['ShieldCheck', 'Role-based access', 'Staff see the matters they should and nothing else.'],
                    ['Search', 'Search that works', 'Find a matter, a document or a paragraph in seconds.'],
                ],
                'showcase' => [
                    ['/screenshots/a-advocate-saas-pic.png', 'The matter dashboard', 'Deadlines, documents, tasks, notes and unbilled time on a single screen. This is the view most of the firm lives in all day.'],
                    ['/screenshots/d-advocate-saas-pic.png', 'Documents with a history', 'Every version retained and searchable, so you can always show what the document said and when it changed.'],
                ],
            ],
            [
                'title' => 'Legal Automation',
                'slug' => 'legal-workflow-automation-software',
                'icon' => 'Workflow',
                'summary' => 'Document assembly and workflow automation',
                'lede' => 'The repeatable parts of legal work should not be typed twice.',
                'context' => 'Every practice has procedures it runs the same way each time. Automation turns those procedures into workflows that fire on their own, and turns matter data into finished documents.',
                'hero_image' => '/screenshots/f-advocate-saas-pic.png',
                'features' => [
                    ['FileCode2', 'Document assembly', 'Generate documents from templates populated with the matter record.'],
                    ['Workflow', 'Workflow templates', 'Opening a matter creates the tasks and deadlines that matter type always needs.'],
                    ['CalendarClock', 'Deadline calculation', 'Dates derived from case events and court rules, not from memory.'],
                    ['BellRing', 'Automatic follow-ups', 'Reminders that go out whether or not anyone remembered to send them.'],
                    ['LibraryBig', 'Clause library', 'Draft from your own precedents rather than the last deal you can find.'],
                    ['Repeat', 'Repeatable procedures', 'The way your best fee earner runs a file becomes the way the firm runs it.'],
                ],
                'showcase' => [
                    ['/screenshots/f-advocate-saas-pic.png', 'Templates that fill themselves', 'Pull names, dates, parties and figures straight from the matter into a finished draft. The retyping, and the transcription errors that come with it, disappear.'],
                    ['/screenshots/e-advocate-saas-pic.png', 'Workflows that start themselves', 'Open a matter of a given type and the tasks, deadlines and owners are created automatically, in the order the work is actually done.'],
                ],
            ],
            [
                'title' => 'Billing & Payments',
                'slug' => 'legal-billing-software',
                'icon' => 'CreditCard',
                'summary' => 'Time tracking, invoicing and online payments',
                'lede' => 'Capture the time, send the invoice, get paid — without the month-end scramble.',
                'context' => 'Revenue leaks in the gaps: time nobody recorded, invoices that go out late, and payments that take weeks to arrive. Billing closes those gaps by connecting the work directly to the bill.',
                'hero_image' => '/screenshots/i-advocate-saas-pic.png',
                'features' => [
                    ['Timer', 'Time capture', 'Record time at the point of work, on desktop or on a phone.'],
                    ['Coins', 'Every billing model', 'Hourly, flat-fee, contingency and retainer on one system.'],
                    ['ReceiptText', 'Professional invoices', 'Bills generated from recorded time and expenses, not rebuilt by hand.'],
                    ['CreditCard', 'Online payments', 'Card and bank payments, with fees handled the way the rules require.'],
                    ['BellRing', 'Payment reminders', 'Outstanding balances chased automatically instead of awkwardly.'],
                    ['TrendingUp', 'Realization tracking', 'See the gap between time worked, time billed and money collected.'],
                ],
                'showcase' => [
                    ['/screenshots/i-advocate-saas-pic.png', 'Time recorded where the work happens', 'Start a timer on the matter you are already in. Time captured this way is time that survives to the invoice, which is where most firms quietly lose revenue.'],
                    ['/screenshots/g-advocate-saas-pic.png', 'Invoices out, payments in', 'Generate the bill from recorded time, send it, and let the client pay it online. The payment lands in the right account and posts to the ledger on its own.'],
                ],
            ],
            [
                'title' => 'Accounting & Finance',
                'slug' => 'accounting-finance',
                'icon' => 'Landmark',
                'summary' => 'Trust accounting, bookkeeping and reporting',
                'lede' => 'Trust compliance and firm books in one place, reconciled and audit-ready.',
                'context' => 'Running legal accounting in a separate general-ledger product means double entry and a reconciliation you can never quite trust. Here the ledger is part of the same system as the matter.',
                'hero_image' => '/screenshots/j-advocate-saas-pic.png',
                'features' => [
                    ['Landmark', 'Trust accounting', 'Client funds kept properly separate from firm money, always.'],
                    ['FileCheck2', 'Three-way reconciliation', 'Bank, ledger and client balances reconciled and ready for a bar audit.'],
                    ['BookOpen', 'Full general ledger', 'Firm bookkeeping in the same system, with no second product to keep in step.'],
                    ['BarChart3', 'Financial reporting', 'Revenue, realization and profitability, by matter, client or fee earner.'],
                    ['ScrollText', 'Complete audit trail', 'Every transaction traceable to who entered it, when, and against what.'],
                    ['ShieldCheck', 'Compliance guardrails', 'Warnings before an entry breaks a trust rule, rather than after.'],
                ],
                'showcase' => [
                    ['/screenshots/j-advocate-saas-pic.png', 'Trust accounts that survive an audit', 'Three-way reconciliation runs against the same records the matters use, so the trust ledger and the firm books can never quietly drift apart.'],
                    ['/screenshots/h-advocate-saas-pic.png', 'Reporting you can act on', 'See which clients, matters and practice areas actually make money — the numbers most firms only discover at year end, if at all.'],
                ],
            ],
        ];
    }

    private function stats(): array
    {
        return [
            ['value' => 'One system', 'label' => 'Matters, billing and accounting together'],
            ['value' => 'No exports', 'label' => 'Nothing to sync between products'],
            ['value' => 'Audit-ready', 'label' => 'Trust reconciliation built in'],
            ['value' => 'Free trial', 'label' => 'No credit card required'],
        ];
    }

    private function build(array $page): array
    {
        $features = collect($page['features'])->map(fn ($f) => [
            'icon' => $f[0],
            'title' => $f[1],
            'description' => $f[2],
        ])->all();

        $showcase = collect($page['showcase'])->map(fn ($s) => [
            'image' => $s[0],
            'title' => $s[1],
            'description' => $s[2],
        ])->all();

        $items = collect($features)
            ->map(fn ($f) => "<li><strong>{$f['title']}</strong> — {$f['description']}</li>")
            ->implode('');

        $content = <<<HTML
        <p>{$page['context']}</p>
        <h2>What's included</h2>
        <ul>{$items}</ul>
        <h2>Part of one platform</h2>
        <p>Every module shares the same matter and client record. Work recorded here shows up in billing, in the ledger and in firm reporting automatically, with no exports and no second system to keep in step.</p>
        HTML;

        return [
            'title' => $page['title'],
            'slug' => $page['slug'],
            'icon' => $page['icon'],
            'summary' => $page['summary'],
            'content' => $content,
            'meta_title' => "{$page['title']} for Law Firms",
            'meta_description' => "{$page['lede']} {$page['summary']} in one legal practice management platform.",
            'page_data' => [
                'eyebrow' => 'Solutions',
                'hero' => [
                    'headline' => $page['lede'],
                    'subheadline' => $page['context'],
                    'image' => $page['hero_image'],
                    'bullets' => collect($features)->take(3)->pluck('title')->all(),
                    'primary_cta' => ['label' => 'Start Free Trial'],
                    'secondary_cta' => ['label' => 'Book a Demo'],
                ],
                'stats' => $this->stats(),
                'features' => [
                    'title' => "What's included in {$page['title']}",
                    'subtitle' => 'Everything this module covers, working off the same matter record as the rest of the platform.',
                    'items' => $features,
                ],
                'showcase' => [
                    'title' => 'See it in action',
                    'subtitle' => 'The screens your firm would be working in every day.',
                    'items' => $showcase,
                ],
                'cta' => [
                    'title' => "Bring {$page['title']} into your firm",
                    'subtitle' => 'Start a free trial, or book a walkthrough and we will show you how it fits the way your firm already works.',
                    'primary_cta' => ['label' => 'Start Free Trial'],
                    'secondary_cta' => ['label' => 'Talk to Sales'],
                ],
            ],
        ];
    }
}
