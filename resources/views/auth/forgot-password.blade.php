<x-layouts.auth title="Reset your password" subtitle="Enter your email and we will send you a reset link.">
    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4">
        @csrf
        <x-input name="email" type="email" label="Email" autocomplete="email" required autofocus />
        <x-button>Send reset link</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        <a href="{{ route('login') }}" class="font-medium text-brand-700 hover:underline">Back to log in</a>
    </p>
</x-layouts.auth>
