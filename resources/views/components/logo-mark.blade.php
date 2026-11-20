@props(['size' => 36, 'inverted' => false])

<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 64 64" aria-hidden="true" {{ $attributes }}>
    <circle cx="32" cy="32" r="31" fill="{{ $inverted ? '#FFFFFF' : '#13235B' }}"/>
    <path d="M32 13 L50 49 H41 L32 31 L23 49 H14 Z" fill="{{ $inverted ? '#13235B' : '#FFFFFF' }}"/>
    <path d="M32 36 L38.5 49 H25.5 Z" fill="#A6E22E"/>
</svg>
