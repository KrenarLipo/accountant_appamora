<x-layouts.auth title="Check your email" subtitle="We sent a verification link to {{ auth()->user()->email }}. Click it to open your dashboard.">
    <div class="flex flex-col gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-button>Send the link again</x-button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full rounded-lg px-4 py-2 text-sm font-medium text-slate-500 hover:text-ink">Log out</button>
        </form>
    </div>
</x-layouts.auth>
