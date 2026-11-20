<x-layouts.auth title="Choose a new password">
    <form method="POST" action="{{ route('password.store') }}" class="flex flex-col gap-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-input name="email" type="email" label="Email" :value="$email" autocomplete="email" required />
        <x-input name="password" type="password" label="New password" autocomplete="new-password" required autofocus />
        <x-input name="password_confirmation" type="password" label="Confirm new password" autocomplete="new-password" required />
        <x-button>Save new password</x-button>
    </form>
</x-layouts.auth>
