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
    <section class="bg-white">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 py-14 sm:px-6 lg:grid-cols-2 lg:py-20">
            <div class="flex flex-col gap-5">
                <p class="text-base font-medium text-slate-500 sm:text-lg">Money clarity for households and small businesses</p>
                <h1 class="flex flex-col text-ink">
                    <span class="text-4xl font-bold leading-tight tracking-tight sm:text-5xl">Know where it goes.</span>
                    <span class="text-8xl font-extrabold leading-none tracking-tighter sm:text-9xl">Grow<span class="text-brand-500">.</span></span>
                </h1>
                <p class="max-w-xl text-lg leading-relaxed text-slate-600">Track every payment by day, see profit or loss at a glance, and get an alert explained in plain words when a cost drifts from normal.</p>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <a href="{{ route('register') }}" class="rounded-xl bg-ink px-7 py-4 text-center text-base font-bold text-white shadow-sm hover:bg-ink/90">Start free</a>
                    <a href="{{ route('features') }}" class="inline-flex items-center justify-center gap-1.5 text-base font-semibold text-ink hover:underline">
                        See how it works
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-brand-600" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                    </a>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full bg-brand-50 px-3 py-1.5 text-sm font-semibold text-brand-700">Accountant · live</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-600">Wealth · soon</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-600">Invest · soon</span>
                </div>
                <p class="text-sm text-slate-500">No bank connection needed · Import your Excel in one step · No card required</p>
            </div>

            {{-- Illustrative dashboard preview --}}
            <div class="flex flex-col gap-4 rounded-3xl bg-slate-50 p-5 ring-1 ring-slate-200/70 sm:p-6" aria-label="Example dashboard" role="img">
                <div class="flex items-center justify-between">
                    <p class="text-lg font-bold text-ink">November</p>
                    <p class="text-xs text-slate-500">Example data</p>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-2xl bg-white p-3 sm:p-4"><p class="text-xs text-slate-500">Income</p><p class="mt-1 text-lg font-bold text-ink sm:text-xl">€3,150</p></div>
                    <div class="rounded-2xl bg-white p-3 sm:p-4"><p class="text-xs text-slate-500">Paid out</p><p class="mt-1 text-lg font-bold text-ink sm:text-xl">€4,390</p></div>
                    <div class="rounded-2xl bg-ink p-3 text-white sm:p-4"><p class="text-xs text-brand-200">Profit</p><p class="mt-1 text-lg font-bold sm:text-xl">+€1,510</p></div>
                </div>
                <div class="flex flex-col gap-3 rounded-2xl bg-white p-4">
                    <div class="flex justify-between text-sm"><span class="font-semibold text-ink">Groceries</span><span class="text-slate-500">72% of budget</span></div>
                    <div class="h-2 rounded-full bg-slate-100"><div class="h-2 w-[72%] rounded-full bg-brand-500"></div></div>
                    <div class="flex justify-between text-sm"><span class="font-semibold text-ink">Marketing</span><span class="font-semibold text-amber-700">above normal</span></div>
                    <div class="h-2 rounded-full bg-slate-100"><div class="h-2 w-[96%] rounded-full bg-orange-500"></div></div>
                </div>
                <div class="flex flex-col gap-1.5 rounded-2xl bg-ink p-4 text-white">
                    <span class="text-xs font-semibold uppercase tracking-wider text-brand-200">Alert · explained by AI</span>
                    <span class="text-sm leading-relaxed text-slate-200">Marketing is EUR 275 against a normal EUR 150 — mostly one ads payment on the 12th. Check whether it was planned.</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Platforms --}}
    <section id="platforms" class="border-y border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="flex items-center gap-3">
                <x-logo-mark :size="40" />
                <p class="text-xl font-extrabold tracking-tight text-ink"><span class="text-brand-600">app</span>amora</p>
            </div>
            <h2 class="mt-4 text-3xl font-bold tracking-tight text-ink">One account. Every money tool.</h2>
            <p class="mt-3 max-w-2xl text-slate-600">Accountant is the first platform from AppAmora. Sign in once and switch on more modules as they launch.</p>
            <div class="mt-10 grid gap-5 md:grid-cols-3">
                <div class="flex flex-col gap-2 rounded-2xl bg-ink p-6 text-white">
                    <span class="self-start rounded-full bg-brand-500 px-2.5 py-1 text-xs font-bold text-ink">Live</span>
                    <h3 class="text-xl font-bold">Accountant</h3>
                    <p class="text-sm text-slate-300">Income, expenses, budgets, profit and loss, alerts and Excel import.</p>
                </div>
                <div class="flex flex-col gap-2 rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                    <span class="self-start rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">Coming later</span>
                    <h3 class="text-xl font-bold text-ink">Wealth</h3>
                    <p class="text-sm text-slate-600">Savings goals, net worth and habits that build wealth over time.</p>
                </div>
                <div class="flex flex-col gap-2 rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                    <span class="self-start rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-800">Coming later</span>
                    <h3 class="text-xl font-bold text-ink">Invest</h3>
                    <p class="text-sm text-slate-600">Portfolio and market tracking — information, not investment advice.</p>
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
            <x-logo-mark :size="56" :inverted="true" />
            <h2 class="text-3xl font-bold text-white">Spot problems earlier. Decide better.</h2>
            <a href="{{ route('register') }}" class="rounded-xl bg-brand-500 px-7 py-4 font-bold text-ink hover:bg-brand-400">Create your free account</a>
        </div>
    </section>
</x-layouts.public>
