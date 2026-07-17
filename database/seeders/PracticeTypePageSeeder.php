<?php

namespace Database\Seeders;

use App\Models\LandingPageCustomPage;
use Illuminate\Database\Seeder;

/**
 * Landing pages for the "Practice Type" header dropdown.
 *
 * Each entry defines its marketing blocks once; the prose body stored in
 * `content` is generated from the same data so both the marketing template
 * and the plain-prose fallback stay in step.
 */
class PracticeTypePageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $index => $page) {
            LandingPageCustomPage::updateOrCreate(
                ['slug' => $page['slug']],
                array_merge($this->build($page, $index), [
                    'nav_group' => 'practice-type',
                    'is_active' => true,
                    'sort_order' => 100 + $index,
                ])
            );
        }
    }

    private function pages(): array
    {
        return [
            [
                'title' => 'Family Law',
                'slug' => 'family-law',
                'icon' => 'Heart',
                'headline' => 'Family law software with built-in peace of mind',
                'summary' => 'Custody, support and divorce matters',
                'lede' => 'Software that lets you prioritise clients and their needs.',
                'context' => 'Family matters move between mediation, negotiation and hearings, often over months or years. Clients call constantly, financial disclosure is exhaustive, and one missed filing date can undo months of progress. AdvocateGo keeps every custody, support and divorce matter organised so you can spend your attention on people, not paperwork.',
                'hero_image' => '/screenshots/a-advocate-saas-pic.png',
                'features' => [
                    ['Workflow', 'Family law workflows', 'Ready-made workflows for adoption, divorce, custody and child-support matters carry each case through its stages with the right tasks and priorities at each step.'],
                    ['ListPlus', 'Custom fields', 'Store the details family law actually turns on — separation dates, children\'s birthdays, key hearing dates — as structured fields on every matter, not buried in notes.'],
                    ['FileSignature', 'Documents and eSignatures', 'Draft, share and collect signatures on agreements and disclosure through a secure portal, without printing, scanning or chasing email attachments.'],
                    ['MessageSquare', 'Text and portal messaging', 'Reach clients by SMS and secure message — far more likely to be read and answered than email — with every exchange logged against the matter.'],
                    ['LayoutDashboard', 'A client portal that works', 'One hub where clients exchange documents, view invoices and follow matter updates, so the calls asking "what is happening?" simply stop coming.'],
                    ['SplitSquareHorizontal', 'Split billing', 'Bill multiple parties on a single matter and track who owes what, so shared-cost family arrangements reconcile without a spreadsheet.'],
                ],
                'testimonials' => [
                    'title' => 'Family law firms that made the switch',
                    'subtitle' => 'Practitioners who traded scattered tools for one system built around the client.',
                    'items' => [
                        ['quote' => 'Custody and support matters used to live across three tools. Now the timeline, the disclosure and the billing are on one screen, and I actually leave the office on time.', 'author' => 'Managing Partner', 'role' => 'Family & Matrimonial Firm'],
                        ['quote' => 'Clients text us and it lands right on the matter. We stopped losing an afternoon a week to voicemail tag over the same three questions.', 'author' => 'Associate Attorney', 'role' => 'Divorce & Custody Practice'],
                        ['quote' => 'The portal cut our document collection time in half. Financial disclosure comes in complete instead of trickling in the week before a hearing.', 'author' => 'Legal Assistant', 'role' => 'Family Law Group'],
                        ['quote' => 'Split billing across both parties finally reconciles on its own. That alone paid for the software in the first month.', 'author' => 'Firm Owner', 'role' => 'Solo Family Practice'],
                        ['quote' => 'Onboarding a new matter takes minutes and nothing gets missed. For emotional cases, that reliability is the difference for our clients.', 'author' => 'Paralegal', 'role' => 'Family & Adoption Firm'],
                    ],
                ],
                'faq' => [
                    'title' => 'Family law software questions',
                    'subtitle' => 'What practitioners ask before they move their family law practice onto one platform.',
                    'items' => [
                        ['question' => 'What is family law practice management software?', 'answer' => 'It is a single system that runs the day-to-day of a family law practice — matters, deadlines, documents, client communication, time tracking, billing and trust accounting — so custody, support and divorce work lives in one place instead of across email, spreadsheets and paper files.'],
                        ['question' => 'How does it reduce administrative work?', 'answer' => 'Ready-made workflows create the right tasks at each stage, custom fields capture family-specific data once, and the client portal collects documents and answers status questions for you. The time you used to spend on coordination goes back into the matter.'],
                        ['question' => 'Can it handle split billing between parties?', 'answer' => 'Yes. You can bill more than one party on the same matter and track what each owes, which is how shared-cost family arrangements are settled without a separate spreadsheet.'],
                        ['question' => 'How does it keep sensitive family matters confidential?', 'answer' => 'Role-based access keeps each matter visible only to the people working it, secure messaging and the portal replace unencrypted email, and every document and communication is logged against the matter with a clear audit trail.'],
                        ['question' => 'What should I look for when choosing family law software?', 'answer' => 'Look for family-specific workflows and custom fields, secure client communication and eSignatures, flexible billing including split and flat fees, trust accounting, and a client portal — all on one record so nothing has to be rekeyed between systems.'],
                    ],
                ],
            ],
            [
                'title' => 'Personal Injury Law',
                'slug' => 'personal-injury-law',
                'icon' => 'Stethoscope',
                'summary' => 'Contingency matters from intake to settlement',
                'lede' => 'Contingency work only pays when the case resolves, so every hour of overhead counts.',
                'context' => 'Personal injury firms carry cases for months before seeing revenue. Medical records pile up, liens have to be tracked, and settlement disbursement has to be exact to the penny.',
                'hero_image' => '/screenshots/c-advocate-saas-pic.png',
                'features' => [
                    ['UserPlus', 'Fast case intake', 'Screening forms capture the facts you need to evaluate a case before you commit to it.'],
                    ['FileHeart', 'Medical records', 'Track records and bills against every treating provider, with nothing lost in an inbox.'],
                    ['Calculator', 'Settlement breakdowns', 'Model disbursement across liens, advanced costs and fee splits before you sign off.'],
                    ['Landmark', 'Trust disbursement', 'Settlement funds handled in trust with a clean audit trail from receipt to payout.'],
                    ['ReceiptText', 'Case expenses', 'Advanced costs recorded against the matter so they are recovered, not written off.'],
                    ['TrendingUp', 'Case value tracking', 'See what is in the pipeline and what it is worth across the whole contingency docket.'],
                ],
            ],
            [
                'title' => 'Criminal Law',
                'slug' => 'criminal-law',
                'icon' => 'Scale',
                'summary' => 'Defense practice built around the court calendar',
                'lede' => 'In criminal defense the calendar is the case, and the calendar never waits.',
                'context' => 'Defense practices juggle arraignments, motions, plea negotiations and trial dates across several courts, often with clients who are hard to reach and matters that arrive with no notice at all.',
                'hero_image' => '/screenshots/b-advocate-saas-pic.png',
                'features' => [
                    ['CalendarDays', 'Court calendaring', 'Every appearance, motion date and filing deadline on one calendar, per court.'],
                    ['Smartphone', 'Intake from anywhere', 'Open a matter from your phone at the courthouse and fill in the detail later.'],
                    ['FolderSearch', 'Discovery management', 'Evidence and disclosure organised per matter, with version history you can rely on.'],
                    ['Wallet', 'Flat fees and retainers', 'Bill the way defense work is actually sold, with balances clients can see.'],
                    ['Lock', 'Privileged notes', 'Sensitive notes and communications locked to the people entitled to read them.'],
                    ['ListChecks', 'Trial preparation', 'Task lists that carry a matter from arraignment through to trial without gaps.'],
                ],
            ],
            [
                'title' => 'Estate Planning Law',
                'slug' => 'estate-planning-law',
                'icon' => 'ScrollText',
                'summary' => 'Wills, trusts and probate administration',
                'lede' => 'Estate work is document-heavy, highly repeatable, and unforgiving of small errors.',
                'context' => 'Most estate matters are variations on a template, but each one carries names, assets and beneficiaries that must be exactly right, and the client relationship may run for decades.',
                'hero_image' => '/screenshots/d-advocate-saas-pic.png',
                'features' => [
                    ['FileStack', 'Document assembly', 'Generate wills and trusts from templates populated with the matter record.'],
                    ['Users', 'Beneficiary records', 'Client, family and beneficiary details that stay accurate over many years.'],
                    ['ClipboardCheck', 'Probate checklists', 'Administration steps and statutory deadlines tracked from grant to distribution.'],
                    ['Tag', 'Flat-fee packages', 'Sell defined packages with clear scope and track them to completion.'],
                    ['Archive', 'Long-term client files', 'Pick a file up a decade later and find everything exactly where it was left.'],
                    ['GitBranch', 'Version history', 'Every draft and revision retained, so you can always show what changed and when.'],
                ],
            ],
            [
                'title' => 'Real Estate Law',
                'slug' => 'real-estate-law',
                'icon' => 'Home',
                'summary' => 'Closings, title work and transaction management',
                'lede' => 'A closing is a deadline with a dozen moving parts attached to it.',
                'context' => 'Real estate practice is coordination: lenders, agents, title and clients all need documents at the right moment, and funds must be handled and reconciled precisely.',
                'hero_image' => '/screenshots/e-advocate-saas-pic.png',
                'features' => [
                    ['ListOrdered', 'Transaction checklists', 'A repeatable path from contract to closing, so nothing is discovered too late.'],
                    ['Banknote', 'Escrow ledgers', 'Trust and escrow balances reconciled to the penny on every transaction.'],
                    ['Share2', 'Secure document exchange', 'Share with lenders, agents and clients without email attachments.'],
                    ['CalendarCheck', 'Closing-date tasks', 'Tasks that schedule themselves backwards from the closing date.'],
                    ['FileCheck2', 'Disbursement records', 'A record of every payment out that will stand up to an audit.'],
                    ['Building2', 'Property records', 'Title, survey and property detail kept on the matter, not in someone\'s drawer.'],
                ],
            ],
            [
                'title' => 'Immigration Law',
                'slug' => 'immigration-law',
                'icon' => 'Plane',
                'summary' => 'Petitions, applications and status tracking',
                'lede' => 'Immigration work is a long sequence of forms, evidence and waiting.',
                'context' => 'A single client may have a case running for years across several filings. Evidence has to be assembled, receipts tracked, and clients kept informed in a language they are comfortable with.',
                'hero_image' => '/screenshots/f-advocate-saas-pic.png',
                'features' => [
                    ['Files', 'Multi-filing matters', 'Track several petitions for the same client or family under one relationship.'],
                    ['Upload', 'Evidence collection', 'Checklists that tell you, and the client, exactly what is still outstanding.'],
                    ['MailCheck', 'Receipt and notice tracking', 'Log receipt notices and response deadlines against the filing they belong to.'],
                    ['Globe', 'Multilingual client portal', 'Clients upload documents and read updates in the language they prefer.'],
                    ['CreditCard', 'Flat fees and payment plans', 'Price by filing type and let clients pay in instalments without losing track of balances.'],
                    ['Clock', 'Long-running status', 'See where every case stands across a docket measured in years, not weeks.'],
                ],
            ],
            [
                'title' => 'Civil Litigation',
                'slug' => 'civil-litigation',
                'icon' => 'Gavel',
                'summary' => 'Disputes from pleadings through trial',
                'lede' => 'Litigation is won on preparation, and preparation is a document management problem.',
                'context' => 'Contested matters generate pleadings, discovery, exhibits and correspondence at volume, all of it under deadlines set by rule rather than convenience.',
                'hero_image' => '/screenshots/g-advocate-saas-pic.png',
                'features' => [
                    ['CalendarClock', 'Rules-based deadlines', 'Calculate dates from case events so the diary reflects the rules, not memory.'],
                    ['FolderTree', 'Discovery and exhibits', 'Organise volume with version history and search that actually finds the document.'],
                    ['Timer', 'Point-of-work time capture', 'Hourly time recorded as it is worked, which is the only time that survives to the bill.'],
                    ['PiggyBank', 'Matter budgets', 'Show clients what the case is costing while it is running, not after.'],
                    ['UsersRound', 'Team workspaces', 'Everyone on the file works from the same record, with the same view of it.'],
                    ['FileSearch', 'Full-text search', 'Find the paragraph you half-remember across everything filed on the matter.'],
                ],
            ],
            [
                'title' => 'Bankruptcy Law',
                'slug' => 'bankruptcy-law',
                'icon' => 'FileWarning',
                'summary' => 'Consumer and business filings',
                'lede' => 'Bankruptcy filings live or die on complete, accurate schedules.',
                'context' => 'The work is procedural and volume-driven: gather financials, assemble schedules, file on time, then manage the creditors meeting and any objections that follow.',
                'hero_image' => '/screenshots/h-advocate-saas-pic.png',
                'features' => [
                    ['ClipboardList', 'Structured intake', 'Capture assets, debts, income and expenses in a form that maps to the schedules.'],
                    ['FolderUp', 'Client document collection', 'Clients upload statements and paystubs through the portal, not by email.'],
                    ['CalendarClock', 'Filing deadlines', 'Filings, hearings and creditor meetings tracked across the whole docket.'],
                    ['CreditCard', 'Instalment billing', 'Flat fees paid over time, with balances both sides can see.'],
                    ['LayoutDashboard', 'High-volume visibility', 'See the status of every case at once, without opening every case.'],
                    ['FileCheck2', 'Schedule review', 'Check completeness before filing rather than after an objection.'],
                ],
            ],
            [
                'title' => 'Business Law',
                'slug' => 'business-law',
                'icon' => 'Briefcase',
                'summary' => 'Corporate, commercial and transactional work',
                'lede' => 'Business clients expect their lawyer to move at the speed of the deal.',
                'context' => 'Corporate work spans formation, contracts, governance and transactions, often for one client across many small matters that all have to roll up into a single relationship.',
                'hero_image' => '/screenshots/i-advocate-saas-pic.png',
                'features' => [
                    ['Network', 'Client hierarchies', 'Many matters grouped under one organisation, with a view across all of them.'],
                    ['FileSignature', 'Contract drafting', 'Draft from your own clause library instead of the last deal you can find.'],
                    ['Building2', 'Entity and governance records', 'Corporate records kept current, searchable and ready when diligence starts.'],
                    ['Coins', 'Mixed billing', 'Hourly, flat-fee and retainer arrangements on the same client without workarounds.'],
                    ['BarChart3', 'Matter budgets and reporting', 'Reporting a business client will actually read and act on.'],
                    ['Zap', 'Deal-speed turnaround', 'Templates and workflows that let routine work go out the same day.'],
                ],
            ],
            [
                'title' => 'Intellectual Property Law',
                'slug' => 'intellectual-property-law',
                'icon' => 'Lightbulb',
                'summary' => 'Trademarks, patents and portfolio management',
                'lede' => 'IP practice is portfolio management with hard statutory deadlines.',
                'context' => 'Registrations must be prosecuted, renewed and defended, sometimes across jurisdictions, and a missed renewal is a loss the client can never recover.',
                'hero_image' => '/screenshots/j-advocate-saas-pic.png',
                'features' => [
                    ['LibraryBig', 'Portfolio tracking', 'Every mark, application and registration in one place, per client.'],
                    ['AlarmClock', 'Renewal deadlines', 'Escalating reminders on renewals and office-action responses.'],
                    ['History', 'Prosecution history', 'The full history lives on the matter, not in one attorney\'s inbox.'],
                    ['UserCheck', 'Docketing that survives turnover', 'The docket belongs to the firm, so staff changes do not lose dates.'],
                    ['Globe2', 'Multi-jurisdiction matters', 'Filings across jurisdictions tracked under a single portfolio.'],
                    ['Receipt', 'Flexible billing', 'Bill by matter, by portfolio or by client as the engagement requires.'],
                ],
            ],
            [
                'title' => 'General Practice',
                'slug' => 'general-practice',
                'icon' => 'LayoutGrid',
                'summary' => 'Mixed caseloads under one system',
                'lede' => 'A general practice does not get to specialise its software.',
                'context' => 'One week is a divorce, a closing and a small contract dispute. The system has to handle hourly and flat fees, trust and operating accounts, and every kind of deadline without being configured three separate ways.',
                'hero_image' => '/screenshots/k-advocate-saas-pic.png',
                'features' => [
                    ['LayoutGrid', 'Matter types per practice area', 'Configure each area the way it works, in one shared workspace.'],
                    ['Coins', 'Every billing model', 'Hourly, flat-fee and contingency running side by side without compromise.'],
                    ['Landmark', 'Trust compliance throughout', 'Trust accounting that stays compliant no matter what kind of matter it is.'],
                    ['CalendarRange', 'One firm calendar', 'Every court, every client commitment, every deadline, in one view.'],
                    ['BarChart3', 'Profitability reporting', 'See which kinds of work actually make money and which quietly do not.'],
                    ['Users', 'Small-team friendly', 'Built to be run by a firm that does not have an IT department.'],
                ],
            ],
        ];
    }

    /** Every screenshot available to spread across the pages. */
    private const IMAGE_POOL = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'client',
    ];

    /**
     * The layout presets rotated across pages so no two adjacent practice pages
     * arrange themselves the same way. Each drives hero, feature and showcase
     * rendering plus the accent colour in the marketing template.
     */
    private function layoutPreset(int $index): array
    {
        $presets = [
            ['hero' => 'split',    'features' => 'grid',   'showcase' => 'alternating', 'accent' => 'primary'],
            ['hero' => 'reverse',  'features' => 'list',   'showcase' => 'cards',       'accent' => 'secondary'],
            ['hero' => 'centered', 'features' => 'zigzag', 'showcase' => 'alternating', 'accent' => 'accent'],
            ['hero' => 'split',    'features' => 'zigzag', 'showcase' => 'cards',       'accent' => 'secondary'],
            ['hero' => 'reverse',  'features' => 'grid',   'showcase' => 'alternating', 'accent' => 'accent'],
            ['hero' => 'centered', 'features' => 'list',   'showcase' => 'cards',       'accent' => 'primary'],
        ];

        return $presets[$index % count($presets)];
    }

    /** Path for a pooled screenshot key. */
    private function image(string $key): string
    {
        return "/screenshots/{$key}-advocate-saas-pic.png";
    }

    /**
     * Three distinct screenshots for a page's showcase, offset by index so each
     * page shows a different trio instead of everyone sharing the same three.
     */
    private function showcaseImages(int $index): array
    {
        $pool = self::IMAGE_POOL;
        $n = count($pool);
        $start = ($index * 5) % $n;

        return [
            $this->image($pool[$start]),
            $this->image($pool[($start + 4) % $n]),
            $this->image($pool[($start + 8) % $n]),
        ];
    }

    /**
     * Standard showcase for practice-type pages: the three product areas every
     * practice touches, described in the language of that practice.
     */
    private function showcase(string $title, array $images): array
    {
        return [
            'title' => 'See it on a real matter',
            'subtitle' => 'The same record carries the work from open to close.',
            'items' => [
                [
                    'image' => $images[0],
                    'title' => 'Every matter at a glance',
                    'description' => "Open a {$title} matter and see the deadlines, documents, tasks, notes and unbilled time on one screen. No hunting through folders and no second system to check.",
                ],
                [
                    'image' => $images[1],
                    'title' => 'Clients who stop chasing you',
                    'description' => 'The client portal gives clients their documents, invoices and case status on demand. Most of the calls asking what is happening simply stop coming.',
                ],
                [
                    'image' => $images[2],
                    'title' => 'Billing that writes itself',
                    'description' => 'Time and expenses recorded on the matter become the invoice, the ledger entry and the report without anyone rekeying a number.',
                ],
            ],
        ];
    }

    private function stats(): array
    {
        return [
            ['value' => '1 record', 'label' => 'For matters, documents, billing and trust'],
            ['value' => 'Minutes', 'label' => 'To open a matter and start working'],
            ['value' => '3-way', 'label' => 'Trust reconciliation, audit-ready'],
            ['value' => 'Free trial', 'label' => 'No credit card required'],
        ];
    }

    /**
     * Expands a page definition into the stored record, deriving the prose
     * fallback body from the same feature list the cards are built from.
     */
    private function build(array $page, int $index = 0): array
    {
        $layout = $page['layout'] ?? $this->layoutPreset($index);
        $showcaseImages = $page['showcase_images'] ?? $this->showcaseImages($index);

        $features = collect($page['features'])->map(fn ($f) => [
            'icon' => $f[0],
            'title' => $f[1],
            'description' => $f[2],
        ])->all();

        $items = collect($features)
            ->map(fn ($f) => "<li><strong>{$f['title']}</strong> — {$f['description']}</li>")
            ->implode('');

        $content = <<<HTML
        <p>{$page['context']}</p>
        <h2>How {$page['title']} firms use the platform</h2>
        <ul>{$items}</ul>
        <h2>Everything on one record</h2>
        <p>Case management, documents, time tracking, billing and trust accounting all run on the same matter, so the work you do becomes the invoice, the ledger entry and the report without anyone rekeying it.</p>
        HTML;

        return [
            'title' => $page['title'],
            'slug' => $page['slug'],
            'icon' => $page['icon'],
            'summary' => $page['summary'],
            'content' => $content,
            'meta_title' => "{$page['title']} Practice Management Software",
            'meta_description' => "{$page['lede']} Case management, billing and trust accounting built for {$page['title']} practices.",
            'page_data' => array_filter([
                'eyebrow' => 'Practice Type',
                'layout' => $layout,
                'hero' => [
                    'headline' => $page['headline'] ?? "{$page['title']} software that keeps up with the caseload",
                    'subheadline' => $page['lede'] . ' ' . $page['context'],
                    'image' => $page['hero_image'],
                    'bullets' => collect($features)->take(3)->pluck('title')->all(),
                    'primary_cta' => ['label' => 'Start Free Trial'],
                    'secondary_cta' => ['label' => 'Book a Demo'],
                ],
                'stats' => $this->stats(),
                'features' => [
                    'title' => "Built for {$page['title']}",
                    'subtitle' => 'The capabilities this practice area actually depends on, in one platform.',
                    'items' => $features,
                ],
                'showcase' => $this->showcase($page['title'], $showcaseImages),
                'testimonials' => $page['testimonials'] ?? null,
                'faq' => $page['faq'] ?? null,
                'cta' => [
                    'title' => "Run your {$page['title']} practice on one platform",
                    'subtitle' => 'Start a free trial and set up a live matter in minutes, or book a walkthrough and we will show you the workflow end to end.',
                    'primary_cta' => ['label' => 'Start Free Trial'],
                    'secondary_cta' => ['label' => 'Book a Demo'],
                ],
            ]),
        ];
    }
}
