@php
    $faqs = [
        ['Is Accountant free?', 'Yes. During the pilot every feature is free. Later there will still be a free plan for personal use, with paid plans for businesses that need more people and deeper reports.'],
        ['Do I need to connect my bank?', 'No. You add transactions yourself in a few seconds. Bank connections may come later, only with a regulated provider and your explicit consent.'],
        ['Can I use it for my business and my personal money?', 'Yes. Create a Personal workspace and a Business workspace and switch between them. Each keeps its own accounts, categories and budgets.'],
        ['Is my financial data safe?', 'Every request is checked against your workspace membership on the server, passwords are hashed, and every change to a record is logged with who made it.'],
        ['Is this tax or accounting advice?', 'No. Accountant organises your money and explains it clearly. For tax filing or certified accounts, talk to a qualified professional.'],
    ];
@endphp

<x-layouts.public>
    <x-slot:head>
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'SoftwareApplication',
                        'name' => 'Accountant by AppAmora',
                        'applicationCategory' => 'FinanceApplication',
                        'operatingSystem' => 'Web',
                        'url' => route('home'),
                        'description' => 'Track income and expenses, set budgets and get alerts. For individuals and small businesses.',
                        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EUR'],
                        'publisher' => ['@type' => 'Organization', 'name' => 'AppAmora', 'url' => 'https://appamora.com'],
                    ],
                    [
                        '@type' => 'FAQPage',
                        'mainEntity' => collect($faqs)->map(fn ($faq) => [
                            '@type' => 'Question',
                            'name' => $faq[0],
                            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq[1]],
                        ])->all(),
                    ],
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
        </script>
    </x-slot:head>

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-brand-50/60 to-white">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:py-24">
            <div>
                <p class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-brand-700 ring-1 ring-brand-100">For individuals and small businesses</p>
                <h1 class="mt-5 text-4xl font-bold tracking-tight text-ink sm:text-5xl">Know where your money goes.</h1>
                <p class="mt-5 max-w-xl text-lg text-slate-600">Record income and expenses in seconds, set monthly budgets and get an alert before you overspend. One clear dashboard for your household or your business.</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('register') }}" class="rounded-lg bg-brand-600 px-6 py-3 text-center font-semibold text-white shadow-sm hover:bg-brand-700">Start free</a>
                    <a href="{{ route('features') }}" class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-center font-semibold text-ink hover:bg-slate-50">See what it does</a>
                </div>
                <p class="mt-4 text-sm text-slate-500">No bank connection needed. No card required.</p>
            </div>

            {{-- Illustrative dashboard preview --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/60" aria-label="Example dashboard" role="img">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-ink">This month</p>
                    <p class="text-xs text-slate-400">Example data</p>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-3">
                    <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Income</p><p class="mt-1 font-semibold text-ink">€2,450</p></div>
                    <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Expenses</p><p class="mt-1 font-semibold text-ink">€1,830</p></div>
                    <div class="rounded-xl bg-brand-50 p-3"><p class="text-xs text-brand-700">Net</p><p class="mt-1 font-semibold text-brand-700">+€620</p></div>
                </div>
                <div class="mt-5 flex h-32 items-end gap-2">
                    @foreach ([45, 60, 52, 70, 58, 80] as $i => $height)
                        <div class="flex flex-1 flex-col justify-end gap-1">
                            <div class="rounded-t bg-brand-500/80" style="height: {{ $height }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-5 flex flex-col gap-3">
                    @foreach ([['Groceries', 72, 'bg-amber-500'], ['Dining out', 91, 'bg-red-500'], ['Transport', 40, 'bg-brand-500']] as [$name, $pct, $color])
                        <div>
                            <div class="flex justify-between text-xs"><span class="font-medium text-slate-600">{{ $name }}</span><span class="text-slate-400">{{ $pct }}% of budget</span></div>
                            <div class="mt-1 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full {{ $color }}" style="width: {{ $pct }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <h2 class="text-3xl font-bold tracking-tight text-ink">Everything you need to stay in control</h2>
        <p class="mt-3 max-w-2xl text-slate-600">Simple enough for a household, structured enough for a small business.</p>
        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['Fast expense tracking', 'Add income, expenses and transfers between accounts. Search and filter everything later.'],
                ['Budgets with alerts', 'Set a monthly limit overall or per category. Get one clear alert at 70%, 90% and 100%.'],
                ['A dashboard that explains', 'Income, expenses, net result, account balances and a six-month trend — on one screen.'],
                ['Personal and business', 'Keep household and business money apart in separate workspaces, and switch in one click.'],
                ['Team roles', 'Business owners can give a bookkeeper or partner exactly the access they need. (Invitations coming soon.)'],
                ['Full history', 'Every change is recorded with who made it and when, so your numbers stay trustworthy.'],
            ] as [$title, $text])
                <div class="rounded-2xl border border-slate-200 p-6">
                    <h3 class="font-semibold text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Audiences --}}
    <section class="bg-slate-50">
        <div class="mx-auto grid max-w-6xl gap-6 px-4 py-16 sm:px-6 md:grid-cols-2">
            <div class="rounded-2xl bg-white p-8 ring-1 ring-slate-200">
                <p class="text-sm font-semibold text-brand-700">For you and your family</p>
                <h2 class="mt-2 text-2xl font-bold text-ink">Fewer surprises at the end of the month</h2>
                <ul class="mt-4 flex flex-col gap-2 text-slate-600">
                    <li>See what you spend on food, bills and fun</li>
                    <li>Get warned before a budget runs out</li>
                    <li>Compare this month with last month</li>
                </ul>
            </div>
            <div class="rounded-2xl bg-white p-8 ring-1 ring-slate-200">
                <p class="text-sm font-semibold text-brand-700">For small businesses</p>
                <h2 class="mt-2 text-2xl font-bold text-ink">Spot rising costs early</h2>
                <ul class="mt-4 flex flex-col gap-2 text-slate-600">
                    <li>Track revenue, suppliers, rent and salaries</li>
                    <li>See your monthly result at a glance</li>
                    <li>Know your cash in every account</li>
                </ul>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <h2 class="text-3xl font-bold tracking-tight text-ink">Up and running in three steps</h2>
        <ol class="mt-10 grid gap-6 md:grid-cols-3">
            @foreach ([
                ['Create a workspace', 'Choose personal or business, your currency and starting balance. Ready-made categories are added for you.'],
                ['Add your transactions', 'Record what comes in and goes out. It takes a few seconds each.'],
                ['Set budgets and relax', 'Pick monthly limits. Accountant watches them and tells you when to look.'],
            ] as $i => [$title, $text])
                <li class="rounded-2xl border border-slate-200 p-6">
                    <span class="grid size-9 place-items-center rounded-full bg-ink font-semibold text-white">{{ $i + 1 }}</span>
                    <h3 class="mt-4 font-semibold text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ $text }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- FAQ --}}
    <section class="bg-slate-50">
        <div class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
            <h2 class="text-3xl font-bold tracking-tight text-ink">Questions</h2>
            <div class="mt-8 flex flex-col gap-3">
                @foreach ($faqs as [$question, $answer])
                    <details class="group rounded-xl bg-white p-5 ring-1 ring-slate-200">
                        <summary class="cursor-pointer list-none font-semibold text-ink">{{ $question }}</summary>
                        <p class="mt-3 text-slate-600">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="bg-ink">
        <div class="mx-auto flex max-w-6xl flex-col items-center gap-6 px-4 py-16 text-center sm:px-6">
            <h2 class="text-3xl font-bold text-white">Spot problems earlier. Decide better.</h2>
            <a href="{{ route('register') }}" class="rounded-lg bg-brand-500 px-6 py-3 font-semibold text-white hover:bg-brand-600">Create your free account</a>
        </div>
    </section>
</x-layouts.public>
