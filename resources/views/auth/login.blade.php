<x-layouts.auth title="Log in" subtitle="Welcome back. Log in to see your money at a glance.">
    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
        @csrf
        <x-input name="email" type="email" label="Email" autocomplete="email" required autofocus />
        <x-input name="password" type="password" label="Password" autocomplete="current-password" required />

        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600"> Remember me
            </label>
            <a href="{{ route('password.request') }}" class="font-medium text-brand-700 hover:underline">Forgot password?</a>
        </div>

        <x-button>Log in</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        New here? <a href="{{ route('register') }}" class="font-medium text-brand-700 hover:underline">Create a free account</a>
    </p>
</x-layouts.auth>
