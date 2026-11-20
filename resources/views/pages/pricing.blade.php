<x-layouts.public title="Pricing" description="Accountant by AppAmora is free during the pilot. A free personal plan will stay available.">
    <section class="mx-auto max-w-5xl px-4 py-16 sm:px-6">
        <h1 class="text-center text-4xl font-bold tracking-tight text-ink">Simple pricing</h1>
        <p class="mx-auto mt-4 max-w-2xl text-center text-lg text-slate-600">Everything is free while we run the pilot. Paid plans will only arrive once they offer clear extra value.</p>

        <div class="mt-12 grid gap-6 md:grid-cols-3">
            <div class="flex flex-col rounded-2xl border-2 border-brand-500 p-6">
                <p class="text-sm font-semibold text-brand-700">Available now</p>
                <h2 class="mt-1 text-xl font-semibold text-ink">Pilot</h2>
                <p class="mt-4 text-4xl font-bold text-ink">Free</p>
                <ul class="mt-6 flex flex-1 flex-col gap-2 text-sm text-slate-600">
                    <li>Personal and business workspaces</li>
                    <li>Unlimited transactions</li>
                    <li>Budgets and alerts</li>
                    <li>Full dashboard</li>
                </ul>
                <a href="{{ route('register') }}" class="mt-6 rounded-lg bg-brand-600 px-4 py-2.5 text-center font-semibold text-white hover:bg-brand-700">Start free</a>
            </div>
            <div class="flex flex-col rounded-2xl border border-slate-200 p-6">
                <p class="text-sm font-semibold text-slate-400">Planned</p>
                <h2 class="mt-1 text-xl font-semibold text-ink">Personal Plus</h2>
                <p class="mt-4 text-sm text-slate-500">For engaged individuals and freelancers.</p>
                <ul class="mt-6 flex flex-col gap-2 text-sm text-slate-600">
                    <li>Recurring transactions</li>
                    <li>Advanced reports and exports</li>
                    <li>AI monthly summaries</li>
                </ul>
            </div>
            <div class="flex flex-col rounded-2xl border border-slate-200 p-6">
                <p class="text-sm font-semibold text-slate-400">Planned</p>
                <h2 class="mt-1 text-xl font-semibold text-ink">Business</h2>
                <p class="mt-4 text-sm text-slate-500">For small teams.</p>
                <ul class="mt-6 flex flex-col gap-2 text-sm text-slate-600">
                    <li>Team members and roles</li>
                    <li>Audit history and exports</li>
                    <li>Forecasts and cost alerts</li>
                </ul>
            </div>
        </div>
    </section>
</x-layouts.public>
