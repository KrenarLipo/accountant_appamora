<button {{ $attributes->merge(['type' => 'submit', 'class' => 'w-full rounded-lg bg-brand-600 px-4 py-2.5 font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/50']) }}>
    {{ $slot }}
</button>
