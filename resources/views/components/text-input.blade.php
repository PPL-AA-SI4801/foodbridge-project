@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-md border border-input bg-card text-foreground focus:border-primary focus:ring-primary']) }}>
