@props(['name', 'label', 'type' => 'text', 'value' => null])

<div class="flex flex-col gap-1.5">
    <label for="{{ $name }}" class="text-sm font-medium text-slate-700">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $type === 'password' ? '' : old($name, $value) }}"
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        {{ $attributes->class([
            'rounded-lg border px-3 py-2 text-slate-900 shadow-sm outline-none focus:ring-2 focus:ring-brand-500/40',
            'border-red-400' => $errors->has($name),
            'border-slate-300 focus:border-brand-500' => ! $errors->has($name),
        ]) }}
    >
    @error($name)
        <p id="{{ $name }}-error" class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
