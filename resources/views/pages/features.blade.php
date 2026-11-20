<x-layouts.public title="Features" description="Expense tracking, monthly budgets with alerts, a clear dashboard, personal and business workspaces, and a full change history.">
    <section class="mx-auto max-w-4xl px-4 py-16 sm:px-6">
        <h1 class="text-4xl font-bold tracking-tight text-ink">What Accountant does</h1>
        <p class="mt-4 text-lg text-slate-600">A trustworthy record of your money first. Clear insight on top.</p>

        <div class="mt-12 flex flex-col gap-10">
            @foreach ([
                ['Accounts and transactions', [
                    'Cash, bank, card, wallet and savings accounts with opening balances',
                    'Income, expenses and transfers — transfers never inflate your spending',
                    'Merchant, category, date and notes on every transaction',
                    'Search, filter by date, account, category or type',
                ]],
                ['Categories', [
                    'Starter categories for personal or business use',
                    'Add, rename and colour your own',
                    'Safe deletion: move existing transactions to another category first',
                ]],
                ['Budgets and alerts', [
                    'One overall monthly budget and one per category',
                    'Alerts at the thresholds you choose (for example 70%, 90%, 100%)',
                    'Each alert is sent only once per month — no spam',
                    'In-app notifications and email',
                ]],
                ['Dashboard', [
                    'Income, expenses and net result for any month',
                    'Comparison with the previous month',
                    'Six-month trend chart and spending by category',
                    'Live balance of every account and top merchants',
                ]],
                ['Workspaces and roles', [
                    'Separate Personal and Business workspaces',
                    'Roles: Owner, Admin, Finance Manager, Contributor, Viewer',
                    'Every change logged with who made it',
                ]],
            ] as [$title, $items])
                <div>
                    <h2 class="text-2xl font-semibold text-ink">{{ $title }}</h2>
                    <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                        @foreach ($items as $item)
                            <li class="flex gap-2 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                                <span class="mt-0.5 text-brand-600" aria-hidden="true">✓</span>{{ $item }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="mt-14 rounded-2xl bg-brand-50 p-8">
            <h2 class="text-xl font-semibold text-ink">Coming next</h2>
            <p class="mt-2 text-slate-600">CSV import and export, team invitations, recurring transactions, receipt uploads, and AI-written monthly summaries that explain your numbers in plain language.</p>
        </div>
    </section>
</x-layouts.public>
