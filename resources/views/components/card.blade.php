@props(['padded' => true])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-line bg-paper shadow-sm ' . ($padded ? 'p-6 sm:p-8' : '')]) }}>
    {{ $slot }}
</div>
