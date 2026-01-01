@if($content)
    @php
        $themeClasses = match ($theme) {
            'dark' => 'bg-gray-900 text-gray-100',
            'light' => 'bg-gray-50 text-gray-900',
            default => 'bg-gray-100 text-gray-900',
        };
    @endphp
    
    <div class="cms-code-block my-6">
        @if($filename)
            <div class="code-header bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 rounded-t-lg border-b border-gray-300">
                <span class="mr-2">📄</span>{{ $filename }}
            </div>
        @endif
        
        <div class="code-container relative {{ $themeClasses }} {{ $filename ? 'rounded-b-lg' : 'rounded-lg' }} border border-gray-300 overflow-hidden">
            <div class="absolute top-2 right-3 text-xs text-gray-500 uppercase font-mono">
                {{ $language }}
            </div>
            
            <pre class="p-4 overflow-x-auto"><code class="language-{{ $language }} font-mono text-sm leading-relaxed">{{ $content }}</code></pre>
            
            <button 
                class="copy-button absolute top-2 right-16 text-xs bg-gray-600 text-white px-2 py-1 rounded opacity-75 hover:opacity-100 transition-opacity"
                onclick="copyCode(this)"
                title="{{ __('Copy code') }}"
            >
                Copy
            </button>
        </div>
    </div>
@endif

<style>
.cms-code-block {
    position: relative;
}

.cms-code-block pre {
    margin: 0;
    white-space: pre;
    overflow-x: auto;
}

.cms-code-block code {
    font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', 'Courier New', monospace;
    line-height: 1.5;
}

.code-header {
    display: flex;
    align-items: center;
    border-bottom: 1px solid #d1d5db;
}

/* Scrollbar styling */
.cms-code-block pre::-webkit-scrollbar {
    height: 6px;
}

.cms-code-block pre::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
}

.cms-code-block pre::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.3);
    border-radius: 3px;
}

.cms-code-block pre::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.5);
}

/* Copy button styling */
.copy-button {
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    transition: opacity 0.2s ease;
}

.copy-button:hover {
    background-color: #4b5563;
}
</style>

<script>
function copyCode(button) {
    const codeBlock = button.closest('.code-container').querySelector('code');
    const text = codeBlock.textContent;
    
    navigator.clipboard.writeText(text).then(function() {
        const originalText = button.textContent;
        button.textContent = 'Copied!';
        button.style.backgroundColor = '#10b981';
        
        setTimeout(function() {
            button.textContent = originalText;
            button.style.backgroundColor = '#6b7280';
        }, 2000);
    }).catch(function(err) {
        console.error('Failed to copy code: ', err);
    });
}
</script>
