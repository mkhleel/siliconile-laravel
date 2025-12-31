@props([
    'label' => null,
    'error' => null,
    'required' => false,
    'options' => [],
    'placeholder' => 'Select an option',
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
    
    <select 
        {{ $attributes->merge([
            'class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50' . ($error ? ' border-destructive' : '')
        ]) }}
        @if($required) required @endif
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        
        @foreach($options as $value => $optionLabel)
            <option value="{{ $value }}" {{ $attributes->get('value') == $value ? 'selected' : '' }}>
                {{ $optionLabel }}
            </option>
        @endforeach
        
        {{ $slot }}
    </select>
    
    @if($error)
        <p class="text-sm text-destructive">{{ $error }}</p>
    @endif
</div>
