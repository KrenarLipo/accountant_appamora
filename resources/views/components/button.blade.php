<button {{ $attributes->merge(['type' => 'submit', 'class' => 'w-full rounded-lg bg-ink px-4 py-2.5 font-semibold text-white shadow-sm hover:bg-ink/90 focus:outline-none focus:ring-2 focus:ring-brand-500/50']) }}>
    {{ $slot }}
</button>
