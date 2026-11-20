@props(['title', 'subtitle' => null])

<x-layouts.public :title="$title" :noindex="true">
    <div class="flex justify-center bg-slate-50 px-4 py-12 sm:py-20">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h1 class="text-2xl font-semibold text-ink">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
            @endif

            @if (session('status'))
                <div class="mt-4 rounded-lg bg-brand-50 px-3 py-2 text-sm text-brand-700" role="status">{{ session('status') }}</div>
            @endif

            <div class="mt-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.public>
