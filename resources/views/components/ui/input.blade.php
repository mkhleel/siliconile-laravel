@props([
    'type' => 'text',
    'label' => null,
    'error' => null,
    'required' => false,
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
    
    @if($type === 'date')
        <div class="relative">
            <input 
                type="{{ $type }}"
                {{ $attributes->merge([
                    'class' => 'flex h-11 w-full rounded-md border border-input bg-background pl-10 pr-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:opacity-0 [&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:inset-0 [&::-webkit-calendar-picker-indicator]:w-full [&::-webkit-calendar-picker-indicator]:h-full' . ($error ? ' border-destructive' : '')
                ]) }}
                @if($required) required @endif
            />
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
        </div>
    @else
        <input 
            type="{{ $type }}"
            {{ $attributes->merge([
                'class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50' . ($error ? ' border-destructive' : '')
            ]) }}
            @if($required) required @endif
        />
    @endif
    
    @if($error)
        <p class="text-sm text-destructive">{{ $error }}</p>
    @endif
</div>
