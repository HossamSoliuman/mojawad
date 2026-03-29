<button wire:click="toggle"
class="flex items-center gap-2 px-4 py-2 rounded-lg border text-sm transition-colors
{{ $saved ? 'bg-blue-50 border-blue-300 text-blue-700' : 'border-gray-200 text-gray-500 hover:border-blue-300 hover:text-blue-700' }}">
<svg class="w-4 h-4" fill="{{ $saved ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
<path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
</svg>
{{ $count }}
</button>
