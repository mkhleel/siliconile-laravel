@props([
    'label' => null,
    'error' => null,
    'required' => false,
    'rows' => 4,
])

<div class="space-y-2">
    @if($label)
        <label for="{{ $attributes->get('id') ?? $attributes->get('name') }}" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
            {{ $label }}
            @if($required)
                <span class="text-destructive">*</span>
            @endif
        </label>
    @endif
    
    <textarea 
        rows="{{ $rows }}"
        {{ $attributes->merge([
            'class' => 'flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 min-h-[80px]' . ($error ? ' border-destructive' : '')
        ]) }}
        @if($required) required @endif
    >{{ $slot }}</textarea>
    
    @if($error)
        <p class="text-sm text-destructive">{{ $error }}</p>
    @endif
</div>
