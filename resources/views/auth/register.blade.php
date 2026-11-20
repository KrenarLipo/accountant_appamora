<x-layouts.auth title="Create your account" subtitle="Free to start. Set up your first workspace in about two minutes.">
    <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-4">
        @csrf
        <x-input name="name" label="Your name" autocomplete="name" required autofocus />
        <x-input name="email" type="email" label="Email" autocomplete="email" required />
        <x-input name="password" type="password" label="Password" autocomplete="new-password" required />
        <x-input name="password_confirmation" type="password" label="Confirm password" autocomplete="new-password" required />

        <div class="flex flex-col gap-1">
            <label class="flex items-start gap-2 text-sm">
                <input type="checkbox" name="terms" value="1" class="mt-0.5 rounded border-slate-300 text-brand-600" @checked(old('terms'))>
                <span>I understand Accountant is a money-organisation tool, not tax, accounting or investment advice.</span>
            </label>
            @error('terms')
                <p class="text-sm text-red-600">Please confirm to continue.</p>
            @enderror
        </div>

        <x-button>Create account</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        Already have an account? <a href="{{ route('login') }}" class="font-medium text-brand-700 hover:underline">Log in</a>
    </p>
</x-layouts.auth>
