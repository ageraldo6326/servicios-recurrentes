@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'input mt-1']) }}>
