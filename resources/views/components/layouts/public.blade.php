@props([
    'title' => null,
    'description' => 'Accountant by AppAmora helps individuals and small businesses track income and expenses, set budgets and get alerts before money problems grow.',
    'noindex' => false,
])

@php
    $pageTitle = $title ? $title.' · Accountant by AppAmora' : 'Accountant by AppAmora — Know where your money goes';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $description }}">
    @if ($noindex)
        <meta name="robots" content="noindex, nofollow">
    @else
        <link rel="canonical" href="{{ url()->current() }}">
    @endif
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Accountant by AppAmora">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/appamora-icon-512.png') }}">
    <meta name="twitter:card" content="summary">
    <meta name="theme-color" content="#13235b">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    @fonts
    @vite('resources/css/app.css')
    {{ $head ?? '' }}
</head>
<body class="flex min-h-full flex-col bg-white font-sans text-slate-700 antialiased">
    <header class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/90 backdrop-blur">
        <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-ink" aria-label="Accountant by AppAmora — home">
                <x-logo-mark :size="38" />
                <span class="flex flex-col leading-tight">
                    <span class="text-xl font-extrabold tracking-tight">accountant</span>
                    <span class="text-xs font-medium text-slate-500">by <span class="font-bold text-brand-600">app</span>amora</span>
                </span>
            </a>
            <div class="hidden items-center gap-7 text-sm font-medium text-slate-600 md:flex">
                <a href="{{ route('features') }}" class="hover:text-ink">Features</a>
                <a href="{{ route('pricing') }}" class="hover:text-ink">Pricing</a>
                <a href="{{ route('home') }}#platforms" class="hover:text-ink">Platforms</a>
            </div>
            <div class="flex items-center gap-2 text-sm font-medium">
                @auth
                    <a href="{{ route('app') }}" class="rounded-lg bg-ink px-4 py-2.5 font-semibold text-white hover:bg-ink/90">Open app</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 hover:text-ink">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-brand-500 px-4 py-2.5 font-semibold text-ink hover:bg-brand-400">Start free</a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 bg-slate-50">
        <div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-10 text-sm sm:px-6 md:flex-row md:items-start md:justify-between">
            <div class="max-w-sm">
                <p class="flex items-center gap-2 font-semibold text-ink"><x-logo-mark :size="24" /> Accountant by AppAmora</p>
                <p class="mt-2 text-slate-500">Management reporting and money organisation for people and small businesses. Not a tax, audit or investment advice service.</p>
            </div>
            <div class="flex gap-10">
                <div class="flex flex-col gap-2">
                    <a href="{{ route('features') }}" class="hover:text-ink">Features</a>
                    <a href="{{ route('pricing') }}" class="hover:text-ink">Pricing</a>
                </div>
                <div class="flex flex-col gap-2">
                    <a href="{{ route('login') }}" class="hover:text-ink">Log in</a>
                    <a href="{{ route('register') }}" class="hover:text-ink">Create account</a>
                    <a href="https://appamora.com" class="hover:text-ink">AppAmora</a>
                </div>
            </div>
        </div>
        <p class="pb-8 text-center text-xs text-slate-400">© {{ date('Y') }} AppAmora</p>
    </footer>
</body>
</html>
